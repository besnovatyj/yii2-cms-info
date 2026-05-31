<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

use Yii;

/**
 * Провайдер метрик Redis.
 * Собирает информацию о версии, памяти, keyspace, клиентах
 */
class RedisMetricProvider extends BaseMetricProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Redis';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAvailability(): bool
    {
        if (!Yii::$app->has('redis')) {
            return false;
        }

        try {
            $pong = Yii::$app->redis->executeCommand('PING');
            return $pong === 'PONG' || $pong === true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMetrics(): array
    {
        $info = $this->getInfo();

        return [
            'version' => $this->parseValue($info, 'redis_version'),
            'uptime' => $this->getUptimeInfo($info),
            'clients' => $this->getClientsInfo($info),
            'memory' => $this->getMemoryInfo($info),
            'stats' => $this->getStatsInfo($info),
            'keyspace' => $this->getKeyspaceInfo(),
        ];
    }

    /**
     * Получает информацию через команду INFO
     *
     * @return array
     */
    private function getInfo(): array
    {
        try {
            $output = Yii::$app->redis->executeCommand('INFO');
            return $this->parseInfo($output);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Парсит вывод команды INFO
     *
     * @param string $output
     * @return array
     */
    private function parseInfo(string $output): array
    {
        $info = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $info[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $info;
    }

    /**
     * Парсит значение из массива info
     *
     * @param array $info
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function parseValue(array $info, string $key, $default = 'N/A')
    {
        return $info[$key] ?? $default;
    }

    /**
     * Получает информацию о времени работы
     *
     * @param array $info
     * @return array
     */
    private function getUptimeInfo(array $info): array
    {
        $seconds = (int)$this->parseValue($info, 'uptime_in_seconds', 0);

        return [
            'seconds' => $seconds,
            'formatted' => $this->formatUptime($seconds),
        ];
    }

    /**
     * Получает информацию о клиентах
     *
     * @param array $info
     * @return array
     */
    private function getClientsInfo(array $info): array
    {
        return [
            'connected' => (int)$this->parseValue($info, 'connected_clients', 0),
            'blocked' => (int)$this->parseValue($info, 'blocked_clients', 0),
        ];
    }

    /**
     * Получает информацию о памяти
     *
     * @param array $info
     * @return array
     */
    private function getMemoryInfo(array $info): array
    {
        $usedMemory = (int)$this->parseValue($info, 'used_memory', 0);
        $peakMemory = (int)$this->parseValue($info, 'used_memory_peak', 0);
        $maxMemory = (int)$this->parseValue($info, 'maxmemory', 0);

        return [
            'used' => $usedMemory,
            'usedFormatted' => $this->parseValue($info, 'used_memory_human'),
            'peak' => $peakMemory,
            'peakFormatted' => $this->parseValue($info, 'used_memory_peak_human'),
            'max' => $maxMemory,
            'maxFormatted' => $maxMemory > 0 ? $this->formatBytes($maxMemory) : 'Unlimited',
            'fragmentation' => (float)$this->parseValue($info, 'mem_fragmentation_ratio', 0),
        ];
    }

    /**
     * Получает статистику
     *
     * @param array $info
     * @return array
     */
    private function getStatsInfo(array $info): array
    {
        $hits = (int)$this->parseValue($info, 'keyspace_hits', 0);
        $misses = (int)$this->parseValue($info, 'keyspace_misses', 0);
        $total = $hits + $misses;

        return [
            'totalConnections' => (int)$this->parseValue($info, 'total_connections_received', 0),
            'totalCommands' => (int)$this->parseValue($info, 'total_commands_processed', 0),
            'keyspaceHits' => $hits,
            'keyspaceMisses' => $misses,
            'hitRate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Получает информацию о keyspace
     *
     * @return array
     */
    private function getKeyspaceInfo(): array
    {
        try {
            $dbSize = Yii::$app->redis->executeCommand('DBSIZE');
            $info = $this->getInfo();

            $databases = [];
            foreach ($info as $key => $value) {
                if (strpos($key, 'db') === 0) {
                    $databases[$key] = $this->parseKeyspaceDb($value);
                }
            }

            return [
                'totalKeys' => (int)$dbSize,
                'databases' => $databases,
            ];
        } catch (\Throwable $e) {
            return [
                'totalKeys' => 0,
                'databases' => [],
            ];
        }
    }

    /**
     * Парсит информацию о базе данных из keyspace
     *
     * @param string $value Например: "keys=123,expires=45,avg_ttl=3600"
     * @return array
     */
    private function parseKeyspaceDb(string $value): array
    {
        $result = [];
        $pairs = explode(',', $value);

        foreach ($pairs as $pair) {
            $parts = explode('=', $pair);
            if (count($parts) === 2) {
                $result[trim($parts[0])] = (int)trim($parts[1]);
            }
        }

        return $result;
    }
}
