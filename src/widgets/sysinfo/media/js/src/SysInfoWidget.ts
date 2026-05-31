/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

import { ApiService } from './ApiService';
import { MetricsRenderer } from './MetricsRenderer';
import { RealtimeUpdater } from './RealtimeUpdater';
import { ChartManager } from './ChartManager';
import { DockerLogsModal } from './DockerLogsModal';
import { SettingsManager } from './SettingsManager';
import { ErrorHandler } from './ErrorHandler';

/**
 * Главный класс виджета системной информации
 * Entry point приложения
 */
export class SysInfoWidget {
    private config: any;
    private apiService: ApiService;
    private metricsRenderer: MetricsRenderer;
    private realtimeUpdater: RealtimeUpdater;
    private chartManager: ChartManager;
    private dockerLogsModal: DockerLogsModal;
    private settingsManager: SettingsManager;
    private errorHandler: ErrorHandler;
    private container: HTMLElement;

    constructor(containerId: string) {
        const container = document.getElementById(containerId);
        if (!container) {
            throw new Error(`Container with id "${containerId}" not found`);
        }

        this.container = container;

        // Парсим конфигурацию из data-config attribute
        const configAttr = container.getAttribute('data-config');
        if (!configAttr) {
            throw new Error('Widget configuration not found in data-config attribute');
        }

        try {
            this.config = JSON.parse(configAttr);
        } catch (e) {
            throw new Error('Failed to parse widget configuration');
        }

        // Инициализация компонентов
        this.errorHandler = new ErrorHandler();
        this.apiService = new ApiService(this.config.endpoints, this.config.csrfToken);
        this.metricsRenderer = new MetricsRenderer();
        this.chartManager = new ChartManager();
        this.dockerLogsModal = new DockerLogsModal(this.apiService, this.errorHandler);
        this.settingsManager = new SettingsManager({
            updateInterval: this.config.updateInterval,
            autoRefresh: this.config.autoRefresh,
        });

        this.realtimeUpdater = new RealtimeUpdater(
            () => this.updateRealtimeMetrics(),
            this.settingsManager.getUpdateInterval()
        );

        console.log('[SysInfoWidget] Initialized', this.config);
    }

    /**
     * Инициализация виджета
     */
    async initialize(): Promise<void> {
        try {
            // Установить обработчики событий
            this.setupEventHandlers();

            // Загрузить начальные метрики
            await this.loadInitialMetrics();

            // Запустить авто-обновление если включено
            if (this.settingsManager.isAutoRefreshEnabled()) {
                this.realtimeUpdater.start();
            }

            console.log('[SysInfoWidget] Ready');
        } catch (error) {
            this.errorHandler.handle(error as Error, 'Initialization failed');
        }
    }

    /**
     * Загрузить начальные метрики (полные)
     */
    private async loadInitialMetrics(): Promise<void> {
        try {
            const metrics = await this.apiService.getAllMetrics();
            this.updateUI(metrics);
            this.errorHandler.success('Метрики загружены');
        } catch (error) {
            this.errorHandler.handle(error as Error, 'Failed to load initial metrics');
            this.metricsRenderer.updateStatus(false, 'Ошибка загрузки');
        }
    }

    /**
     * Обновить метрики в режиме real-time (легковесные)
     */
    private async updateRealtimeMetrics(): Promise<void> {
        try {
            const metrics = await this.apiService.getRealtimeMetrics();
            this.updateUI(metrics);
        } catch (error) {
            console.error('[SysInfoWidget] Realtime update failed', error);
            this.metricsRenderer.updateStatus(false, 'Ошибка обновления');
        }
    }

    /**
     * Обновить UI с новыми метриками
     */
    private updateUI(metrics: any): void {
        // Обновить timestamp
        this.metricsRenderer.updateTimestamp(metrics.timestampFormatted || '-');
        this.metricsRenderer.updateStatus(true);

        // Обновить Overview вкладку
        this.metricsRenderer.renderOverview(metrics);

        // Обновить System вкладку
        this.metricsRenderer.renderSystem(metrics);

        // Обновить Resources вкладку
        this.metricsRenderer.renderResources(metrics);

        // Обновить Services вкладку
        this.metricsRenderer.renderServices(metrics);

        // Обновить Docker вкладку
        if (metrics.docker) {
            this.metricsRenderer.renderDocker(metrics.docker);
            this.attachDockerLogsHandlers();
        }

        // Обновить графики Chart.js
        if (metrics.system?.cpuUsage && metrics.system?.memory) {
            const cpuPercent = this.chartManager.calculateCpuPercent(metrics.system.cpuUsage);
            const memPercent = metrics.system.memory.usedPercent || 0;
            this.chartManager.addDataPoint(cpuPercent, memPercent);
        }
    }

    /**
     * Настроить обработчики событий
     */
    private setupEventHandlers(): void {
        // Кнопка обновления
        const refreshBtn = document.getElementById('sysinfo-btn-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.handleRefreshClick());
        }

        // Кнопка паузы/возобновления
        const pauseBtn = document.getElementById('sysinfo-btn-pause');
        if (pauseBtn) {
            pauseBtn.addEventListener('click', () => this.handlePauseClick(pauseBtn));
        }

        // Кнопка настроек
        const settingsBtn = document.getElementById('sysinfo-btn-settings');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => this.handleSettingsClick());
        }

        // Экспорт JSON
        const exportJsonBtn = document.getElementById('sysinfo-export-json');
        if (exportJsonBtn) {
            exportJsonBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = this.apiService.getExportJsonUrl();
            });
        }

        // Экспорт CSV
        const exportCsvBtn = document.getElementById('sysinfo-export-csv');
        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = this.apiService.getExportCsvUrl();
            });
        }

        // Сохранение настроек
        const saveSettingsBtn = document.getElementById('settings-save');
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', () => this.handleSaveSettings());
        }
    }

    /**
     * Прикрепить обработчики к кнопкам Docker логов
     */
    private attachDockerLogsHandlers(): void {
        const logsBtns = document.querySelectorAll('.docker-logs-btn');
        logsBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const target = e.currentTarget as HTMLElement;
                const container = target.getAttribute('data-container');
                if (container) {
                    this.dockerLogsModal.show(container, 100);
                }
            });
        });
    }

    /**
     * Обработчик клика по кнопке обновления
     */
    private async handleRefreshClick(): Promise<void> {
        this.errorHandler.info('Обновление метрик...');
        await this.loadInitialMetrics();
    }

    /**
     * Обработчик клика по кнопке паузы
     */
    private handlePauseClick(button: HTMLElement): void {
        const isRunning = this.realtimeUpdater.toggle();

        if (isRunning) {
            button.innerHTML = '<i class="bi bi-pause-fill"></i> Пауза';
            this.errorHandler.info('Авто-обновление возобновлено');
        } else {
            button.innerHTML = '<i class="bi bi-play-fill"></i> Возобновить';
            this.errorHandler.info('Авто-обновление приостановлено');
        }
    }

    /**
     * Обработчик клика по кнопке настроек
     */
    private handleSettingsClick(): void {
        // Загрузить текущие настройки в модалку
        const settings = this.settingsManager.get();
        const intervalInput = document.getElementById('settings-update-interval') as HTMLInputElement;
        const autoRefreshCheckbox = document.getElementById('settings-auto-refresh') as HTMLInputElement;

        if (intervalInput) {
            intervalInput.value = (settings.updateInterval / 1000).toString();
        }
        if (autoRefreshCheckbox) {
            autoRefreshCheckbox.checked = settings.autoRefresh;
        }

        // Показать модалку (Bootstrap)
        const modalElement = document.getElementById('settingsModal');
        if (modalElement && typeof (window as any).bootstrap !== 'undefined') {
            const modal = new (window as any).bootstrap.Modal(modalElement);
            modal.show();
        }
    }

    /**
     * Обработчик сохранения настроек
     */
    private handleSaveSettings(): void {
        const intervalInput = document.getElementById('settings-update-interval') as HTMLInputElement;
        const autoRefreshCheckbox = document.getElementById('settings-auto-refresh') as HTMLInputElement;

        if (intervalInput && autoRefreshCheckbox) {
            const newInterval = parseInt(intervalInput.value) * 1000; // конвертируем в мс
            const newAutoRefresh = autoRefreshCheckbox.checked;

            this.settingsManager.setUpdateInterval(newInterval);
            this.settingsManager.setAutoRefresh(newAutoRefresh);

            // Обновить updater
            this.realtimeUpdater.setInterval(newInterval);
            if (newAutoRefresh && !this.realtimeUpdater.isActive()) {
                this.realtimeUpdater.start();
            } else if (!newAutoRefresh && this.realtimeUpdater.isActive()) {
                this.realtimeUpdater.stop();
            }

            this.errorHandler.success('Настройки сохранены');

            // Закрыть модалку
            const modalElement = document.getElementById('settingsModal');
            if (modalElement && typeof (window as any).bootstrap !== 'undefined') {
                const modal = (window as any).bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        }
    }

    /**
     * Уничтожить виджет
     */
    destroy(): void {
        this.realtimeUpdater.stop();
        this.chartManager.destroy();
        console.log('[SysInfoWidget] Destroyed');
    }
}

// Автоматическая инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
    const widgets = document.querySelectorAll('.sysinfo-widget');
    widgets.forEach(widgetElement => {
        const widget = new SysInfoWidget(widgetElement.id);
        widget.initialize().catch(console.error);
    });
});
