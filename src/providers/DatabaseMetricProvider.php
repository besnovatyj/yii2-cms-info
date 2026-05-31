<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

use Yii;
use yii\db\Exception;

/**
 * Провайдер метрик базы данных MySQL.
 * Собирает информацию о версии, размере БД, таблицах, соединениях
 */
class DatabaseMetricProvider extends BaseMetricProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Database';
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
        if (!Yii::$app->has('db')) {
            return false;
        }

        try {
            Yii::$app->db->open();
            return Yii::$app->db->isActive;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMetrics(): array
    {
        $db = Yii::$app->db;

        return [
            'driver' => $this->getDriverName(),
            'version' => $this->getVersion(),
            'database' => $this->getDatabaseName(),
            'charset' => $db->charset ?? 'N/A',
            'size' => $this->getDatabaseSize(),
            'tables' => $this->getTablesInfo(),
            'connections' => $this->getConnectionsInfo(),
        ];
    }

    /**
     * Получает название драйвера БД
     *
     * @return string
     */
    private function getDriverName(): string
    {
        return Yii::$app->db->driverName ?? 'Unknown';
    }

    /**
     * Получает версию MySQL
     *
     * @return string
     */
    private function getVersion(): string
    {
        try {
            $version = Yii::$app->db->createCommand('SELECT VERSION()')->queryScalar();
            return $version ?: 'Unknown';
        } catch (\Throwable $e) {
            return 'Unknown';
        }
    }

    /**
     * Получает имя текущей БД
     *
     * @return string
     */
    private function getDatabaseName(): string
    {
        try {
            $dbName = Yii::$app->db->createCommand('SELECT DATABASE()')->queryScalar();
            return $dbName ?: 'Unknown';
        } catch (\Throwable $e) {
            return 'Unknown';
        }
    }

    /**
     * Получает размер БД в байтах и форматированный размер
     *
     * @return array
     */
    private function getDatabaseSize(): array
    {
        try {
            $dbName = $this->getDatabaseName();
            $sql = "
                SELECT
                    SUM(data_length + index_length) as size
                FROM information_schema.TABLES
                WHERE table_schema = :dbName
            ";

            $sizeBytes = Yii::$app->db->createCommand($sql, [':dbName' => $dbName])->queryScalar();
            $sizeBytes = (int)$sizeBytes;

            return [
                'bytes' => $sizeBytes,
                'formatted' => $this->formatBytes($sizeBytes),
            ];
        } catch (\Throwable $e) {
            return [
                'bytes' => 0,
                'formatted' => 'Unknown',
            ];
        }
    }

    /**
     * Получает информацию о таблицах
     *
     * @return array
     */
    private function getTablesInfo(): array
    {
        try {
            $dbName = $this->getDatabaseName();
            $sql = "
                SELECT
                    COUNT(*) as count,
                    SUM(table_rows) as total_rows,
                    SUM(data_length) as data_size,
                    SUM(index_length) as index_size
                FROM information_schema.TABLES
                WHERE table_schema = :dbName
            ";

            $info = Yii::$app->db->createCommand($sql, [':dbName' => $dbName])->queryOne();

            return [
                'count' => (int)($info['count'] ?? 0),
                'totalRows' => (int)($info['total_rows'] ?? 0),
                'dataSize' => [
                    'bytes' => (int)($info['data_size'] ?? 0),
                    'formatted' => $this->formatBytes((int)($info['data_size'] ?? 0)),
                ],
                'indexSize' => [
                    'bytes' => (int)($info['index_size'] ?? 0),
                    'formatted' => $this->formatBytes((int)($info['index_size'] ?? 0)),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'count' => 0,
                'totalRows' => 0,
                'dataSize' => ['bytes' => 0, 'formatted' => 'Unknown'],
                'indexSize' => ['bytes' => 0, 'formatted' => 'Unknown'],
            ];
        }
    }

    /**
     * Получает информацию о соединениях
     *
     * @return array
     */
    private function getConnectionsInfo(): array
    {
        try {
            $status = Yii::$app->db->createCommand('SHOW STATUS')->queryAll();
            $statusMap = [];
            foreach ($status as $row) {
                $statusMap[$row['Variable_name']] = $row['Value'];
            }

            return [
                'current' => (int)($statusMap['Threads_connected'] ?? 0),
                'running' => (int)($statusMap['Threads_running'] ?? 0),
                'cached' => (int)($statusMap['Threads_cached'] ?? 0),
                'created' => (int)($statusMap['Threads_created'] ?? 0),
                'maxUsed' => (int)($statusMap['Max_used_connections'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return [
                'current' => 0,
                'running' => 0,
                'cached' => 0,
                'created' => 0,
                'maxUsed' => 0,
            ];
        }
    }
}
