<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\widgets\sysinfo;

use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Виджет системной информации.
 * Отображает метрики системы с real-time обновлением.
 */
class SysInfoWidget extends Widget
{
    /**
     * @var int Интервал обновления в миллисекундах (по умолчанию 3 секунды)
     */
    public int $updateInterval = 3000;

    /**
     * @var bool Автоматическое обновление (по умолчанию включено)
     */
    public bool $autoRefresh = true;

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        $this->registerAssets();

        return $this->render('index', [
            'widgetId' => $this->getId(),
            'config' => $this->getClientConfig(),
        ]);
    }

    /**
     * Регистрирует assets (CSS, JS)
     */
    private function registerAssets(): void
    {
        SysInfoAsset::register($this->view);
    }

    /**
     * Получает конфигурацию для JavaScript
     *
     * @return array
     */
    private function getClientConfig(): array
    {
        return [
            'widgetId' => $this->getId(),
            'updateInterval' => $this->updateInterval,
            'autoRefresh' => $this->autoRefresh,
            'endpoints' => [
                'metrics' => Url::to(['/Info/backend/sys-info/api-metrics']),
                'realtime' => Url::to(['/Info/backend/sys-info/api-realtime']),
                'dockerLogs' => Url::to(['/Info/backend/sys-info/api-docker-logs']),
                'dockerStats' => Url::to(['/Info/backend/sys-info/api-docker-stats']),
                'exportJson' => Url::to(['/Info/backend/sys-info/export-json']),
                'exportCsv' => Url::to(['/Info/backend/sys-info/export-csv']),
            ],
            'csrfToken' => \Yii::$app->request->csrfToken,
        ];
    }

    /**
     * Получает JSON конфигурацию для встраивания в HTML
     *
     * @return string
     */
    public function getConfigJson(): string
    {
        return Json::htmlEncode($this->getClientConfig());
    }
}
