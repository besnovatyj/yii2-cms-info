<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\repositories;

/**
 * Repository для системной информации
 *
 * DEPRECATED: Большинство методов перенесены в Providers
 * Оставлены только вспомогательные методы
 */
class SystemInfoRepository
{
    /**
     * Форматирует размер файла в человекочитаемый формат
     *
     * @param int $bytes Размер в байтах
     * @return string Отформатированный размер
     *
     * @deprecated Используйте BaseMetricProvider::formatBytes()
     */
    public static function humanFileSize(int $bytes): string
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'K', 'M', 'G', 'T'];
        $size = '';

        while ($bytes > 0 && count($units) > 0) {
            $size = strval($bytes % 1024) . ' ' . array_shift($units) . ' ' . $size;
            $bytes = intval($bytes / 1024);
        }

        return $size;
    }
}
