<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

use Throwable;
use Yii;

/**
 * Базовый класс для всех провайдеров метрик
 */
abstract class BaseMetricProvider implements MetricProviderInterface
{
    /**
     * @var bool Кеш статуса доступности (null = не проверялось)
     */
    private ?bool $availabilityCache = null;

    /**
     * Проверяет доступность источника метрик с кешированием результата
     *
     * @return bool
     */
    final public function isAvailable(): bool
    {
        if ($this->availabilityCache === null) {
            $this->availabilityCache = $this->checkAvailability();
        }

        return $this->availabilityCache;
    }

    /**
     * Получает метрики из источника с обработкой ошибок
     *
     * @return array
     */
    final public function getMetrics(): array
    {
        if (!$this->isAvailable()) {
            return [
                'available' => false,
                'message' => $this->getUnavailableMessage(),
                'provider' => $this->getName(),
                'category' => $this->getCategory(),
            ];
        }

        try {
            $metrics = $this->collectMetrics();

            return array_merge([
                'available' => true,
                'provider' => $this->getName(),
                'category' => $this->getCategory(),
            ], $metrics);
        } catch (Throwable $e) {
            Yii::error(sprintf(
                'Error collecting metrics from %s: %s',
                $this->getName(),
                $e->getMessage()
            ), __METHOD__);

            return [
                'available' => false,
                'error' => true,
                'message' => 'Failed to collect metrics: ' . $e->getMessage(),
                'provider' => $this->getName(),
                'category' => $this->getCategory(),
            ];
        }
    }

    /**
     * Проверяет доступность источника метрик (переопределяется в наследниках)
     *
     * @return bool
     */
    abstract protected function checkAvailability(): bool;

    /**
     * Собирает метрики из источника (переопределяется в наследниках)
     *
     * @return array
     * @throws Throwable
     */
    abstract protected function collectMetrics(): array;

    /**
     * Возвращает сообщение о недоступности источника
     *
     * @return string
     */
    protected function getUnavailableMessage(): string
    {
        return sprintf('%s metrics are not available', $this->getName());
    }

    /**
     * Безопасное чтение файла с обработкой ошибок
     *
     * @param string $path Путь к файлу
     * @return string|null Содержимое файла или null при ошибке
     */
    protected function safeFileRead(string $path): ?string
    {
        if (!is_readable($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        return $content !== false ? $content : null;
    }

    /**
     * Безопасное чтение файла построчно
     *
     * @param string $path Путь к файлу
     * @return array|null Массив строк или null при ошибке
     */
    protected function safeFileReadLines(string $path): ?array
    {
        if (!is_readable($path)) {
            return null;
        }

        $lines = @file($path);
        return $lines !== false ? $lines : null;
    }

    /**
     * Безопасное выполнение shell команды
     *
     * @param string $command Команда для выполнения
     * @param int $timeout Таймаут в секундах (по умолчанию 5)
     * @return string|null Вывод команды или null при ошибке
     */
    protected function safeShellExec(string $command, int $timeout = 5): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        $output = @shell_exec("timeout {$timeout} {$command} 2>/dev/null");
        return $output !== null ? trim($output) : null;
    }

    /**
     * Форматирование размера файла в человекочитаемый формат
     *
     * @param int $bytes Размер в байтах
     * @param int $precision Точность (знаков после запятой)
     * @return string Отформатированный размер
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Форматирование uptime в человекочитаемый формат
     *
     * @param int $seconds Время в секундах
     * @return string Отформатированное время
     */
    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days}d";
        }
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }

        return implode(' ', $parts) ?: '0m';
    }
}
