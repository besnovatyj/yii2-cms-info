<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\services;

use Besnovatyj\Info\providers\ApplicationMetricProvider;
use Besnovatyj\Info\providers\DatabaseMetricProvider;
use Besnovatyj\Info\providers\DockerMetricProvider;
use Besnovatyj\Info\providers\PhpMetricProvider;
use Besnovatyj\Info\providers\RedisMetricProvider;
use Besnovatyj\Info\providers\SystemMetricProvider;

/**
 * Сервис системной информации.
 * Координирует работу всех провайдеров метрик.
 */
class SysInfoService
{
    /**
     * @var SystemMetricProvider
     */
    private SystemMetricProvider $systemProvider;

    /**
     * @var PhpMetricProvider
     */
    private PhpMetricProvider $phpProvider;

    /**
     * @var DockerMetricProvider
     */
    private DockerMetricProvider $dockerProvider;

    /**
     * @var DatabaseMetricProvider
     */
    private DatabaseMetricProvider $databaseProvider;

    /**
     * @var RedisMetricProvider
     */
    private RedisMetricProvider $redisProvider;

    /**
     * @var ApplicationMetricProvider
     */
    private ApplicationMetricProvider $applicationProvider;

    /**
     * Конструктор с dependency injection всех провайдеров
     *
     * @param SystemMetricProvider $systemProvider
     * @param PhpMetricProvider $phpProvider
     * @param DockerMetricProvider $dockerProvider
     * @param DatabaseMetricProvider $databaseProvider
     * @param RedisMetricProvider $redisProvider
     * @param ApplicationMetricProvider $applicationProvider
     */
    public function __construct(
        SystemMetricProvider $systemProvider,
        PhpMetricProvider $phpProvider,
        DockerMetricProvider $dockerProvider,
        DatabaseMetricProvider $databaseProvider,
        RedisMetricProvider $redisProvider,
        ApplicationMetricProvider $applicationProvider
    ) {
        $this->systemProvider = $systemProvider;
        $this->phpProvider = $phpProvider;
        $this->dockerProvider = $dockerProvider;
        $this->databaseProvider = $databaseProvider;
        $this->redisProvider = $redisProvider;
        $this->applicationProvider = $applicationProvider;
    }

    /**
     * Получает все метрики от всех провайдеров
     *
     * @return array
     */
    public function getAllMetrics(): array
    {
        return [
            'timestamp' => time(),
            'timestampFormatted' => date('Y-m-d H:i:s'),
            'system' => $this->systemProvider->getMetrics(),
            'php' => $this->phpProvider->getMetrics(),
            'docker' => $this->dockerProvider->getMetrics(),
            'database' => $this->databaseProvider->getMetrics(),
            'redis' => $this->redisProvider->getMetrics(),
            'application' => $this->applicationProvider->getMetrics(),
        ];
    }

    /**
     * Получает только метрики для real-time обновления (легковесные)
     * Используется для автоматического обновления каждые 3 секунды
     *
     * @return array
     */
    public function getRealtimeMetrics(): array
    {
        $metrics = [
            'timestamp' => time(),
            'timestampFormatted' => date('Y-m-d H:i:s'),
        ];

        // System metrics (только важные для графиков)
        $systemMetrics = $this->systemProvider->getMetrics();
        if ($systemMetrics['available'] ?? false) {
            $metrics['system'] = [
                'available' => true,
                'cpuUsage' => $systemMetrics['cpuUsage'] ?? [],
                'memory' => $systemMetrics['memory'] ?? [],
                'disk' => $systemMetrics['disk'] ?? [],
                'loadavg' => $systemMetrics['loadavg'] ?? [],
                'temperature' => $systemMetrics['temperature'] ?? [],
            ];
        }

        // Docker containers status (только статусы)
        $dockerMetrics = $this->dockerProvider->getMetrics();
        if ($dockerMetrics['available'] ?? false) {
            $metrics['docker'] = [
                'available' => true,
                'containers' => $dockerMetrics['containers'] ?? [],
            ];
        }

        // Database connections
        $dbMetrics = $this->databaseProvider->getMetrics();
        if ($dbMetrics['available'] ?? false) {
            $metrics['database'] = [
                'available' => true,
                'connections' => $dbMetrics['connections'] ?? [],
            ];
        }

        return $metrics;
    }

    /**
     * Получает метрики по категории
     *
     * @param string $category Категория (system, database, docker, etc.)
     * @return array
     */
    public function getMetricsByCategory(string $category): array
    {
        $allMetrics = $this->getAllMetrics();

        if (!isset($allMetrics[$category])) {
            return [
                'error' => true,
                'message' => "Category '{$category}' not found",
            ];
        }

        return [
            'timestamp' => $allMetrics['timestamp'],
            'timestampFormatted' => $allMetrics['timestampFormatted'],
            'category' => $category,
            'metrics' => $allMetrics[$category],
        ];
    }

    /**
     * Экспортирует все метрики в JSON
     *
     * @param bool $prettyPrint Форматированный вывод
     * @return string
     */
    public function exportToJson(bool $prettyPrint = true): string
    {
        $metrics = $this->getAllMetrics();
        $flags = $prettyPrint ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE;

        return json_encode($metrics, $flags);
    }

    /**
     * Экспортирует метрики в CSV формат
     *
     * @return string
     */
    public function exportToCsv(): string
    {
        $metrics = $this->getAllMetrics();
        $rows = $this->flattenMetrics($metrics);

        $csv = "Category,Metric,Value\n";

        foreach ($rows as $row) {
            $csv .= implode(',', [
                $this->escapeCsv($row['category']),
                $this->escapeCsv($row['metric']),
                $this->escapeCsv($row['value']),
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Преобразует многомерный массив метрик в плоский для CSV
     *
     * @param array $metrics
     * @param string $prefix
     * @return array
     */
    private function flattenMetrics(array $metrics, string $prefix = ''): array
    {
        $result = [];

        foreach ($metrics as $key => $value) {
            $currentKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenMetrics($value, $currentKey));
            } else {
                $parts = explode('.', $currentKey, 2);
                $result[] = [
                    'category' => $parts[0] ?? '',
                    'metric' => $parts[1] ?? $currentKey,
                    'value' => (string)$value,
                ];
            }
        }

        return $result;
    }

    /**
     * Экранирует значение для CSV
     *
     * @param string $value
     * @return string
     */
    private function escapeCsv(string $value): string
    {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }

    /**
     * Получает логи Docker контейнера
     *
     * @param string $containerId ID или имя контейнера
     * @param int $lines Количество строк
     * @return array
     */
    public function getDockerLogs(string $containerId, int $lines = 100): array
    {
        return $this->dockerProvider->getContainerLogs($containerId, $lines);
    }

    /**
     * Получает статистику Docker контейнера
     *
     * @param string $containerId ID или имя контейнера
     * @return array
     */
    public function getDockerStats(string $containerId): array
    {
        return $this->dockerProvider->getContainerStats($containerId);
    }
}
