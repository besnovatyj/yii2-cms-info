<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

/**
 * Провайдер метрик PHP.
 * Собирает информацию о PHP, OPcache, расширениях, настройках
 */
class PhpMetricProvider extends BaseMetricProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'PHP';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'services';
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAvailability(): bool
    {
        return true; // PHP всегда доступен
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMetrics(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'architecture' => PHP_INT_SIZE * 8 . '-bit',
            'memory' => $this->getMemoryInfo(),
            'limits' => $this->getLimits(),
            'opcache' => $this->getOpcacheInfo(),
            'extensions' => $this->getExtensions(),
        ];
    }

    /**
     * Получает информацию о памяти PHP
     *
     * @return array
     */
    private function getMemoryInfo(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'current' => $current,
            'currentFormatted' => $this->formatBytes($current),
            'peak' => $peak,
            'peakFormatted' => $this->formatBytes($peak),
            'limit' => $limit,
            'limitFormatted' => $limit > 0 ? $this->formatBytes($limit) : 'Unlimited',
            'usagePercent' => $limit > 0 ? round(($current / $limit) * 100, 2) : 0,
        ];
    }

    /**
     * Парсит memory_limit в байты
     *
     * @param string $value
     * @return int
     */
    private function parseMemoryLimit(string $value): int
    {
        if ($value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $value = (int)$value;

        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Получает лимиты PHP
     *
     * @return array
     */
    private function getLimits(): array
    {
        return [
            'maxExecutionTime' => (int)ini_get('max_execution_time'),
            'maxInputTime' => (int)ini_get('max_input_time'),
            'postMaxSize' => ini_get('post_max_size'),
            'uploadMaxFilesize' => ini_get('upload_max_filesize'),
            'maxFileUploads' => (int)ini_get('max_file_uploads'),
            'defaultSocketTimeout' => (int)ini_get('default_socket_timeout'),
        ];
    }

    /**
     * Получает информацию о OPcache
     *
     * @return array
     */
    private function getOpcacheInfo(): array
    {
        if (!function_exists('opcache_get_status')) {
            return [
                'enabled' => false,
                'message' => 'OPcache extension not loaded',
            ];
        }

        $status = @opcache_get_status(false);
        if ($status === false) {
            return [
                'enabled' => false,
                'message' => 'OPcache is disabled',
            ];
        }

        $config = @opcache_get_configuration();

        return [
            'enabled' => true,
            'version' => $config['version']['version'] ?? 'N/A',
            'memory' => [
                'used' => $status['memory_usage']['used_memory'] ?? 0,
                'usedFormatted' => $this->formatBytes($status['memory_usage']['used_memory'] ?? 0),
                'free' => $status['memory_usage']['free_memory'] ?? 0,
                'freeFormatted' => $this->formatBytes($status['memory_usage']['free_memory'] ?? 0),
                'wasted' => $status['memory_usage']['wasted_memory'] ?? 0,
                'wastedFormatted' => $this->formatBytes($status['memory_usage']['wasted_memory'] ?? 0),
                'usagePercent' => $status['memory_usage']['current_wasted_percentage'] ?? 0,
            ],
            'statistics' => [
                'numCachedScripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
                'numCachedKeys' => $status['opcache_statistics']['num_cached_keys'] ?? 0,
                'maxCachedKeys' => $status['opcache_statistics']['max_cached_keys'] ?? 0,
                'hits' => $status['opcache_statistics']['hits'] ?? 0,
                'misses' => $status['opcache_statistics']['misses'] ?? 0,
                'hitRate' => $this->calculateHitRate(
                    $status['opcache_statistics']['hits'] ?? 0,
                    $status['opcache_statistics']['misses'] ?? 0
                ),
            ],
        ];
    }

    /**
     * Вычисляет процент попаданий в кеш
     *
     * @param int $hits
     * @param int $misses
     * @return float
     */
    private function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Получает список загруженных расширений
     *
     * @return array
     */
    private function getExtensions(): array
    {
        $allExtensions = get_loaded_extensions();
        sort($allExtensions);

        // Важные расширения для проверки
        $importantExtensions = [
            'curl', 'gd', 'mbstring', 'pdo', 'pdo_mysql', 'redis',
            'zip', 'opcache', 'intl', 'json', 'xml', 'simplexml',
            'fileinfo', 'openssl', 'bcmath', 'imagick', 'calendar'
        ];

        $extensionsStatus = [];
        foreach ($importantExtensions as $ext) {
            $extensionsStatus[$ext] = extension_loaded($ext);
        }

        return [
            'all' => $allExtensions,
            'count' => count($allExtensions),
            'important' => $extensionsStatus,
        ];
    }
}
