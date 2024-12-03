<?php
// phpcs:ignoreFile WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/**
 * Description of BaseJob
 *
 * @author Ali2Woo Team
 *
 * @position: 1
 */

namespace AliNext_Lite;;

use AliNext_Lite\Library\BackgroundProcessing\WP_Background_Process;

abstract class BaseJob extends WP_Background_Process implements BaseJobInterface
{
    protected string $title = 'Base Job';
    private float $memoryLimit = 0.9;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSize(): int
    {
        global $wpdb;

        $table  = $wpdb->options;
        $column = 'option_name';

        if ( is_multisite() ) {
            $table  = $wpdb->sitemeta;
            $column = 'meta_key';
        }

        $key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

        $count = $wpdb->get_var( $wpdb->prepare( "
        SELECT COUNT(*)
        FROM {$table}
        WHERE {$column} LIKE %s
        ", $key ) );

        return $count;
    }

    public function getName(): string
    {
        return $this->action;
    }

    public function isQueued(): bool
    {
        return parent::is_queued();
    }

    public function isCancelled(): bool
    {
        return parent::is_cancelled();
    }

    public function cancel(): void
    {
        parent::cancel();
    }

    /**
     * Memory exceeded?
     *
     * Ensures the batch process never exceeds X%
     * of the maximum WordPress memory.
     *
     * @return bool
     */
    protected function memory_exceeded(): bool
    {
        if (a2wl_check_defined('A2WL_JOB_MEMORY_LIMIT')) {
            $this->memoryLimit = filter_var(
                A2WL_JOB_MEMORY_LIMIT,
                FILTER_VALIDATE_FLOAT,
                [
                    'options' => [
                        'min_range' => 0.1,
                        'max_range' => $this->memoryLimit,
                        'default' => $this->memoryLimit
                    ]
                ]
            );
        }

        $memory_limit   = $this->get_memory_limit() * $this->memoryLimit; // X% of max memory
        $current_memory = memory_get_usage(true);
        $return = false;

        if ( $current_memory >= $memory_limit ) {
            $return = true;
        }

        return apply_filters($this->identifier . '_memory_exceeded', $return);
    }
}
