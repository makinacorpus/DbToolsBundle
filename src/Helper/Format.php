<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper;

class Format
{
    /**
     * Format memory string.
     */
    public static function memory(int $bytes): string
    {
        $prefix = '';
        if ($bytes < 0) {
            $prefix = '-';
            $bytes = \abs($bytes);
        }
        if ($bytes >= 1024 * 1024 * 1024) {
            return $prefix . \round($bytes / 1024 / 1024 / 1024, 2) . ' GiB';
        }
        if ($bytes >= 1024 * 1024) {
            return $prefix . \round($bytes / 1024 / 1024, 2) . ' MiB';
        }
        if ($bytes >= 1024) {
            return $prefix . \round($bytes / 1024, 2) . ' KiB';
        }
        return $prefix . $bytes . ' B';
    }

    /**
     * Format time string.
     */
    public static function time(float $msec): string
    {
        if ($msec > 1000 * 60) {
            return \sprintf("%d mn %d sec", $msec / 60000, \round(($msec % 60000) / 60000 * 60));
        }
        if ($msec > 1000) {
            return \sprintf('%d sec %d ms', $msec / 1000, \round($msec % 1000));
        }
        return \round($msec) . ' ms';
    }
}
