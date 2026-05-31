<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

use Yii;

/**
 * Провайдер метрик Yii2 приложения
 * Собирает информацию о версии, окружении, сессиях, очереди, кеше
 */
class ApplicationMetricProvider extends BaseMetricProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Application';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'application';
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAvailability(): bool
    {
        return true; // Приложение всегда доступно
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMetrics(): array
    {
        return [
            'yii' => $this->getYiiInfo(),
            'environment' => $this->getEnvironmentInfo(),
            'cache' => $this->getCacheInfo(),
            'session' => $this->getSessionInfo(),
            'queue' => $this->getQueueInfo(),
        ];
    }

    /**
     * Получает информацию о Yii2
     *
     * @return array
     */
    private function getYiiInfo(): array
    {
        return [
            'version' => Yii::getVersion(),
            'name' => Yii::$app->name ?? 'N/A',
            'id' => Yii::$app->id ?? 'N/A',
        ];
    }

    /**
     * Получает информацию об окружении
     *
     * @return array
     */
    private function getEnvironmentInfo(): array
    {
        return [
            'environment' => YII_ENV,
            'debug' => YII_DEBUG,
            'timezone' => Yii::$app->timeZone ?? date_default_timezone_get(),
            'language' => Yii::$app->language ?? 'N/A',
        ];
    }

    /**
     * Получает информацию о кеше
     *
     * @return array
     */
    private function getCacheInfo(): array
    {
        if (!Yii::$app->has('cache')) {
            return [
                'enabled' => false,
                'message' => 'Cache component not configured',
            ];
        }

        $cache = Yii::$app->cache;

        return [
            'enabled' => true,
            'class' => get_class($cache),
            'keyPrefix' => $cache->keyPrefix ?? '',
        ];
    }

    /**
     * Получает информацию о сессии
     *
     * @return array
     */
    private function getSessionInfo(): array
    {
        if (!Yii::$app->has('session')) {
            return [
                'available' => false,
                'message' => 'Session component not configured',
            ];
        }

        $session = Yii::$app->session;

        return [
            'available' => true,
            'isActive' => $session->getIsActive(),
            'id' => $session->getIsActive() ? $session->getId() : null,
            'name' => $session->getName(),
            'timeout' => $session->getTimeout(),
            'class' => get_class($session),
        ];
    }

    /**
     * Получает информацию об очереди
     *
     * @return array
     */
    private function getQueueInfo(): array
    {
        if (!Yii::$app->has('queue')) {
            return [
                'enabled' => false,
                'message' => 'Queue component not configured',
            ];
        }

        $queue = Yii::$app->queue;

        return [
            'enabled' => true,
            'class' => get_class($queue),
            'driver' => $this->getQueueDriver($queue),
        ];
    }

    /**
     * Определяет драйвер очереди
     *
     * @param mixed $queue
     * @return string
     */
    private function getQueueDriver($queue): string
    {
        $class = get_class($queue);

        if (str_contains($class, 'RedisQueue')) {
            return 'Redis';
        } elseif (str_contains($class, 'DbQueue')) {
            return 'Database';
        } elseif (str_contains($class, 'FileQueue')) {
            return 'File';
        }

        return 'Unknown';
    }
}
