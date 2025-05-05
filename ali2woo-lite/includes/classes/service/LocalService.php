<?php

/**
 * Description of LocalService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Exception;

class LocalService
{
    public function getNumberOfProcessorCores(): int
    {
        $ncpu = 1; // Default to 1 processor

        // Check for Linux
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $ncpu = count($matches[0]);
        }
        // Check for Windows
        elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                // Create a COM object for WMI
                $wmi = new \COM('winmgmts://./root/cimv2');
                $processors = $wmi->ExecQuery("SELECT NumberOfLogicalProcessors FROM Win32_Processor");
                foreach ($processors as $processor) {
                    $ncpu = (int)$processor->NumberOfLogicalProcessors;
                }
            } catch (Exception $e) {
                // Handle any exceptions (e.g., COM not enabled)
                a2wl_error_log(
                    "LocalService::getNumberOfProcessors - could not retrieve processor count: " . $e->getMessage()
                );
            }
        }

        return $ncpu;
    }

    public function getSystemLoadAverage(): array
    {
        $load = sys_getloadavg();

        if ($load === false) {
            return [];
        }

        foreach ($load as &$item) {
            $item = number_format((float)$item, 2, '.', '');
        }

        return $load;
    }

    public function getMemoryUsageInBytes(): int
    {
        return memory_get_usage(true);
    }

}