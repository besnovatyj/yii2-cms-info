<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/**
 * @var View $this
 * @var string $widgetId
 * @var array $config
 */

?>

<div id="<?= Html::encode($widgetId) ?>" class="sysinfo-widget" data-config='<?= Json::htmlEncode($config) ?>'>

    <!-- Header с кнопками управления -->
    <div class="sysinfo-header mb-3 d-flex justify-content-between align-items-center">
        <div class="sysinfo-status">
            <span class="badge bg-success" id="sysinfo-status-badge">Подключено</span>
            <small class="text-muted ms-2">Обновлено: <span id="sysinfo-last-update">-</span></small>
            <small class="text-muted ms-2">Авто-обновление: <span id="sysinfo-auto-refresh">ВКЛ (каждые 3 сек)</span></small>
        </div>

        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary" id="sysinfo-btn-refresh">
                <i class="bi bi-arrow-clockwise"></i> Обновить
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sysinfo-btn-pause">
                <i class="bi bi-pause-fill"></i> Пауза
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sysinfo-btn-settings">
                <i class="bi bi-gear"></i> Настройки
            </button>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i> Экспорт
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" id="sysinfo-export-json">
                        <i class="bi bi-file-earmark-code"></i> JSON
                    </a></li>
                    <li><a class="dropdown-item" href="#" id="sysinfo-export-csv">
                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Навигационные вкладки -->
    <ul class="nav nav-tabs" id="sysinfo-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                <i class="bi bi-speedometer2"></i> Обзор
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                <i class="bi bi-hdd"></i> Система
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab">
                <i class="bi bi-graph-up"></i> Ресурсы
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab">
                <i class="bi bi-server"></i> Сервисы
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="docker-tab" data-bs-toggle="tab" data-bs-target="#docker" type="button" role="tab">
                <i class="bi bi-boxes"></i> Docker
            </button>
        </li>
    </ul>

    <!-- Содержимое вкладок -->
    <div class="tab-content mt-3" id="sysinfo-tab-content">

        <!-- Вкладка: Обзор -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row g-3">
                <!-- Карточка: Сервер -->
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-hdd-fill"></i> Сервер
                        </div>
                        <div class="card-body">
                            <div id="overview-server-content" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                                <span class="placeholder col-8"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Карточка: CPU -->
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <i class="bi bi-cpu"></i> CPU
                        </div>
                        <div class="card-body">
                            <div id="overview-cpu-content" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                                <span class="placeholder col-8"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Карточка: Память -->
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <i class="bi bi-memory"></i> Память
                        </div>
                        <div class="card-body">
                            <div id="overview-memory-content" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                                <span class="placeholder col-8"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Карточка: Диск -->
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <i class="bi bi-device-hdd"></i> Диск
                        </div>
                        <div class="card-body">
                            <div id="overview-disk-content" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                                <span class="placeholder col-8"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Карточка: Docker -->
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header bg-dark text-white">
                            <i class="bi bi-boxes"></i> Docker
                        </div>
                        <div class="card-body">
                            <div id="overview-docker-content" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                                <span class="placeholder col-8"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Карточка: База данных -->
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-database"></i> База данных
                        </div>
                        <div class="card-body">
                            <div id="overview-database-content" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                                <span class="placeholder col-8"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Вкладка: Система -->
        <div class="tab-pane fade" id="system" role="tabpanel">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-info-circle"></i> Информация о сервере</div>
                        <div class="card-body">
                            <div id="system-server-info" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-cpu"></i> Информация о процессоре</div>
                        <div class="card-body">
                            <div id="system-cpu-info" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Вкладка: Ресурсы -->
        <div class="tab-pane fade" id="resources" role="tabpanel">
            <div class="row g-3">
                <!-- Графики Chart.js -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-graph-up"></i> CPU Usage (3 мин)</div>
                        <div class="card-body">
                            <canvas id="chart-cpu" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-graph-up"></i> Memory Usage (3 мин)</div>
                        <div class="card-body">
                            <canvas id="chart-memory" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Детальная информация -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-memory"></i> Память</div>
                        <div class="card-body">
                            <div id="resources-memory-details" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-device-hdd"></i> Диск</div>
                        <div class="card-body">
                            <div id="resources-disk-details" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-speedometer"></i> Load Average</div>
                        <div class="card-body">
                            <div id="resources-loadavg" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-ethernet"></i> Сетевые интерфейсы</div>
                        <div class="card-body">
                            <div id="resources-network" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Вкладка: Сервисы -->
        <div class="tab-pane fade" id="services" role="tabpanel">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-file-code"></i> PHP</div>
                        <div class="card-body">
                            <div id="services-php" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-database"></i> MySQL</div>
                        <div class="card-body">
                            <div id="services-mysql" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-lightning"></i> Redis</div>
                        <div class="card-body">
                            <div id="services-redis" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-code-square"></i> Yii2 Application</div>
                        <div class="card-body">
                            <div id="services-application" class="placeholder-glow">
                                <span class="placeholder col-12"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Вкладка: Docker -->
        <div class="tab-pane fade" id="docker" role="tabpanel">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-boxes"></i> Контейнеры Docker
                            <span class="badge bg-secondary" id="docker-container-count">0</span>
                        </div>
                        <div class="card-body">
                            <div id="docker-containers" class="table-responsive">
                                <div class="placeholder-glow">
                                    <span class="placeholder col-12"></span>
                                    <span class="placeholder col-10"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Модальное окно: Docker логи -->
<div class="modal fade" id="dockerLogsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-text"></i> Логи контейнера: <span id="docker-logs-container-name"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="docker-logs-content" class="bg-dark text-white p-3" style="max-height: 600px; overflow-y: auto;"><code></code></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно: Настройки -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear"></i> Настройки</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="settings-update-interval" class="form-label">Интервал обновления (сек)</label>
                    <input type="number" class="form-control" id="settings-update-interval" min="1" max="60" value="3">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="settings-auto-refresh" checked>
                    <label class="form-check-label" for="settings-auto-refresh">Автоматическое обновление</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="settings-save">Сохранить</button>
            </div>
        </div>
    </div>
</div>
