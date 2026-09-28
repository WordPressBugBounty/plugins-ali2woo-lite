<?php
// phpcs:ignoreFile WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/**
 * Description of BaseJobWithWorkers
 *
 * Base class for background jobs whose queue is drained by a bounded number of
 * parallel workers.
 *
 * Correctness rests on two levels of MySQL advisory locks:
 *  - worker slots (acquire_worker_slot) cap how many workers run at once;
 *  - batch locks (get_batch) make sure no two workers process the same batch.
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use wpdb;

abstract class BaseJobWithWorkers extends BaseJob
{
    /**
     * Default number of allowed concurrent workers for this job.
     *
     * Subclasses may override this constant (or hook the per-job filter) to
     * use a different cap.
     */
    public const MAX_WORKERS = 6;

    /**
     * Worker slot currently held by this request, 0 when none.
     *
     * @var int
     */
    protected int $worker_slot = 0;

    /**
     * Maybe process a batch of queued items.
     *
     * Runs the drain loop. Parallel workers are safe because get_batch()
     * (overridden below) atomically claims a batch via a MySQL advisory lock:
     * different workers process DIFFERENT batches in parallel, while the same
     * batch is never handled by two workers at once.
     *
     * The base library gates worker startup on the transient "process lock"
     * (is_processing()), which collapses concurrency to the 1-2 workers that
     * slip through the check-then-act window. Since per-batch advisory locks
     * already guarantee correctness, that gate is bypassed here and parallelism
     * is bounded instead by the worker slots (acquire_worker_slot()).
     *
     * @return void
     */
    public function maybe_handle()
    {
        session_write_close();

        if ($this->is_cancelled()) {
            $this->clear_scheduled_event();
            $this->delete_all();

            return $this->maybe_wp_die();
        }

        if ($this->is_paused()) {
            $this->clear_scheduled_event();
            $this->paused();

            return $this->maybe_wp_die();
        }

        check_ajax_referer($this->identifier, 'nonce');

        if (!$this->acquire_worker_slot()) {
            return $this->maybe_wp_die();
        }

        try {
            // handle() claims a free batch itself. When all remaining batches are
            // currently claimed by parallel workers it keeps the chain alive by
            // dispatching a successor instead of treating the queue as empty.
            $this->handle();
        } finally {
            $this->release_worker_slot();
        }

        return $this->maybe_wp_die();
    }

    /**
     * Dispatch the async request.
     *
     * Replaces WP_Background_Process::dispatch() so that a new worker is
     * spawned even while another worker is active. The base implementation
     * refuses to dispatch while the transient "process lock" is set, which
     * serialized the queue; concurrency is now capped by the worker slots
     * instead, so spawning is always safe.
     *
     * @return array|\WP_Error|false HTTP response array, WP_Error on failure, or false if not attempted.
     */
    public function dispatch()
    {
        $this->schedule_event();

        $url  = add_query_arg($this->get_query_args(), $this->get_query_url());
        $args = $this->get_post_args();

        return wp_remote_post(esc_url_raw($url), $args);
    }

    /**
     * Acquire one of the limited worker slots.
     *
     * A slot is a MySQL advisory lock held for the whole request, released
     * explicitly in release_worker_slot() or automatically when the connection
     * closes (e.g. a crashed worker). The number of slots caps how many jobs
     * can be processed at once. Slots are scoped per job (via the identifier),
     * so different jobs never compete for the same slots.
     *
     * @return bool True when a free slot was acquired, false otherwise.
     */
    protected function acquire_worker_slot(): bool
    {
        global $wpdb;

        for ($i = 1; $i <= $this->worker_slot_limit(); $i++) {
            $lock_name = 'a2wl_slot_' . md5($wpdb->dbname . '_' . $this->identifier . '_' . $i);

            $claimed = $wpdb->get_var(
                $wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lock_name)
            );

            if ($claimed === '1') {
                $this->worker_slot = $i;

                return true;
            }
        }

        return false;
    }

    /**
     * Number of allowed concurrent workers.
     *
     * @return int Clamped to the [1, 20] range.
     */
    protected function worker_slot_limit(): int
    {
        $max_workers = (int) apply_filters($this->action . '_max_workers', static::MAX_WORKERS);

        return max(1, min(20, $max_workers));
    }

    /**
     * Release the worker slot acquired by acquire_worker_slot().
     *
     * @return void
     */
    protected function release_worker_slot(): void
    {
        global $wpdb;

        if (!$this->worker_slot) {
            return;
        }

        $lock_name = 'a2wl_slot_' . md5($wpdb->dbname . '_' . $this->identifier . '_' . $this->worker_slot);

        $wpdb->query(
            $wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name)
        );

        $this->worker_slot = 0;
    }

    /**
     * Atomically claim the first FREE batch.
     *
     * Overrides WP_Background_Process::get_batch() so concurrent workers never
     * pick the same batch. Before this fix, several workers read the SAME batch
     * option and processed the same product concurrently, which created
     * duplicate product variations and "Duplicate entry ... for key
     * 'wp_term_relationships.PRIMARY'" errors.
     *
     * Each batch gets its own MySQL advisory lock (GET_LOCK). The lock lives on
     * this connection and is auto-released when the request ends, so a crashed
     * worker never strands a batch. Within one request GET_LOCK is re-entrant,
     * so repeated calls keep returning the same already-claimed batch while it
     * is being processed.
     *
     * @return stdClass|array The claimed batch, or an empty array if none free.
     */
    protected function get_batch()
    {
        global $wpdb;

        foreach ($this->get_batches(50) as $batch) {
            $lock_name = 'a2wl_batch_' . md5($wpdb->dbname . '_' . $batch->key);

            $claimed = $wpdb->get_var(
                $wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lock_name)
            );

            if ($claimed === '1') {
                return $batch;
            }
        }

        return array();
    }

    /**
     * Is the queue empty?
     *
     * Counts batch rows directly, ignoring advisory locks. The base
     * implementation (and the previously used lock-aware get_batch()) cannot
     * tell "no batches exist" apart from "all remaining batches are claimed by
     * parallel workers right now"; both returned empty. That made handle() call
     * complete() prematurely, killing the whole queue chain while batches were
     * still pending. A raw row count keeps the semantics correct everywhere the
     * queue state is checked (is_queued, cron healthcheck, handle loop).
     *
     * @return bool
     */
    protected function is_queue_empty(): bool
    {
        return 0 === $this->getSize();
    }

    /**
     * Handle a dispatched request.
     *
     * Lock-aware drain loop: claims one batch at a time via get_batch().
     * When every remaining batch is currently claimed by a parallel worker,
     * get_batch() returns an empty array - that is NOT "queue empty", so the
     * loop stops gracefully and the final decision dispatches a successor
     * worker instead of calling complete(). complete() runs only when no batch
     * rows remain at all, which keeps the queue chain alive until it is truly
     * drained and lets concurrency reach the worker-slot cap.
     *
     * @return void
     */
    protected function handle(): void
    {
        $this->start_time = time();

        do {
            $batch = $this->get_batch();

            // All remaining batches are claimed by other parallel workers.
            // Keep the chain alive; never treat this as end of queue.
            if (empty($batch)) {
                break;
            }

            foreach ($batch->data as $key => $value) {
                $task = $this->task($value);

                if (false !== $task) {
                    $batch->data[$key] = $task;
                } else {
                    unset($batch->data[$key]);
                }

                // Keep the batch up to date while processing it.
                if (!empty($batch->data)) {
                    $this->update($batch->key, $batch->data);
                }

                // Batch limits reached, or pause or cancel request.
                if ($this->time_exceeded() || $this->memory_exceeded() || $this->is_paused() || $this->is_cancelled()) {
                    break;
                }
            }

            // Delete current batch if fully processed.
            if (empty($batch->data)) {
                $this->delete($batch->key);
            }
        } while (!$this->time_exceeded() && !$this->memory_exceeded() && !$this->is_queue_empty() && !$this->is_paused() && !$this->is_cancelled());

        // Start next batch or complete process.
        if (!$this->is_queue_empty()) {
            $this->dispatch();
        } else {
            $this->complete();
        }
    }
}