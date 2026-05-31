<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

/**
 * Интерфейс для провайдеров метрик системной информации
 */
interface MetricProviderInterface
{
    /**
     * Проверяет доступность источника метрик
     *
     * @return bool true если источник доступен, false в противном случае
     */
    public function isAvailable(): bool;

    /**
     * Получает метрики из источника
     *
     * Возвращает ассоциативный массив с метриками.
     * Если источник недоступен, возвращает массив с ключом 'available' => false
     *
     * @return array Массив метрик
     */
    public function getMetrics(): array;

    /**
     * Возвращает имя провайдера
     *
     * @return string Человекочитаемое имя провайдера (например, "System", "Docker", "MySQL")
     */
    public function getName(): string;

    /**
     * Возвращает категорию метрик
     *
     * @return string Категория (system, application, database, services, docker)
     */
    public function getCategory(): string;
}
