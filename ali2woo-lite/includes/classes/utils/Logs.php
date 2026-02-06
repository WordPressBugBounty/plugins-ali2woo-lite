<?php

/**
 * Description of Logs
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Throwable;

class Logs{

    private static ?Logs $_instance = null;

    private string $a2wl_logs_file = '/ali2woo/a2wl_debug.txt';

    //old version log
    private string $a2wl_old_logs_file = '/ali2woo/a2wl_debug.log';

    protected function __construct() {
        if (get_setting('write_info_log')) {
            $upload_dir   = wp_upload_dir();
            $a2wl_logs_dir = $upload_dir['basedir'] . '/ali2woo';

            $old_file = $upload_dir['basedir'] . $this->a2wl_old_logs_file;
            $new_file = $upload_dir['basedir'] . $this->a2wl_logs_file;

            if (!file_exists($a2wl_logs_dir)) {
                mkdir($a2wl_logs_dir, 0755, true);
            }

            if (file_exists($old_file) && !file_exists($new_file)) {
                if (!rename($old_file, $new_file)) {
                    error_log("Failed to rename old log file");
                }
            } elseif (!file_exists($new_file)) {
                $fp = fopen($new_file, 'w');
                fclose($fp);
                chmod($new_file, 0644);
            }
        }
    }

    protected function __clone() {}

    static public function getInstance(): ?Logs
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function write($message): void
    {
        if (get_setting('write_info_log')) {
            try {
                $fp = fopen($this->log_path(), 'a');
                fwrite($fp, $message . "\r\n");
            } catch (Throwable $e) {
                error_log($e->getTraceAsString());
            } finally {
                if (!empty($fp)) {
                    fclose($fp);
                }
            }
        }
    }

    public function delete(): void
    {
        unlink($this->log_path());
    }

    public function log_path(): string
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . $this->a2wl_logs_file;
    }

    public function log_url(): string
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . $this->a2wl_logs_file;
    }
}
