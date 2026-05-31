<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\controllers\backend;


use Besnovatyj\Info\services\SysInfoService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Контроллер системной информации
 * Тонкий слой для обработки HTTP запросов
 */
class SysInfoController extends \yii\web\Controller
{
    /**
     * @var SysInfoService
     */
    private SysInfoService $service;

    /**
     * Конструктор с dependency injection сервиса
     *
     * @param string $id
     * @param mixed $module
     * @param SysInfoService $service
     * @param array $config
     */
    public function __construct(
        $id,
        $module,
        SysInfoService $service,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'api-metrics' => ['GET'],
                    'api-realtime' => ['GET'],
                    'api-docker-logs' => ['GET'],
                    'api-docker-stats' => ['GET'],
                    'export-json' => ['GET'],
                    'export-csv' => ['GET'],
                ],
            ],
        ]);
    }

    /**
     * Главная страница с виджетом системной информации
     *
     * @return string
     */
    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * API: Получить все метрики в JSON формате
     *
     * @return array
     */
    public function actionApiMetrics(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->service->getAllMetrics();
    }

    /**
     * API: Получить метрики для real-time обновления (легковесные)
     *
     * @return array
     */
    public function actionApiRealtime(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->service->getRealtimeMetrics();
    }

    /**
     * API: Получить метрики по категории
     *
     * @param string|null $category
     * @return array
     */
    public function actionApiCategory(?string $category = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$category) {
            return [
                'error' => true,
                'message' => 'Category parameter is required',
            ];
        }

        return $this->service->getMetricsByCategory($category);
    }

    /**
     * API: Получить логи Docker контейнера
     *
     * @param string|null $container
     * @param int $lines
     * @return array
     */
    public function actionApiDockerLogs(?string $container = null, int $lines = 100): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$container) {
            return [
                'success' => false,
                'message' => 'Container parameter is required',
            ];
        }

        return $this->service->getDockerLogs($container, $lines);
    }

    /**
     * API: Получить статистику Docker контейнера
     *
     * @param string|null $container
     * @return array
     */
    public function actionApiDockerStats(?string $container = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$container) {
            return [
                'success' => false,
                'message' => 'Container parameter is required',
            ];
        }

        return $this->service->getDockerStats($container);
    }

    /**
     * Экспорт всех метрик в JSON файл
     *
     * @return Response
     */
    public function actionExportJson(): Response
    {
        $json = $this->service->exportToJson(true);
        $filename = 'sysinfo_' . date('Y-m-d_H-i-s') . '.json';

        return Yii::$app->response->sendContentAsFile($json, $filename, [
            'mimeType' => 'application/json',
            'inline' => false,
        ]);
    }

    /**
     * Экспорт всех метрик в CSV файл
     *
     * @return Response
     */
    public function actionExportCsv(): Response
    {
        $csv = $this->service->exportToCsv();
        $filename = 'sysinfo_' . date('Y-m-d_H-i-s') . '.csv';

        return Yii::$app->response->sendContentAsFile($csv, $filename, [
            'mimeType' => 'text/csv',
            'inline' => false,
        ]);
    }
}
