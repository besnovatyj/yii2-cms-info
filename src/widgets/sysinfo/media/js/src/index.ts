/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Entry point для TypeScript бандла
 * Экспортирует главный класс для использования
 */

export { SysInfoWidget } from './SysInfoWidget';
export { ApiService } from './ApiService';
export { MetricsRenderer } from './MetricsRenderer';
export { ChartManager } from './ChartManager';
export { RealtimeUpdater } from './RealtimeUpdater';
export { DockerLogsModal } from './DockerLogsModal';
export { SettingsManager, type SysInfoSettings } from './SettingsManager';
export { ErrorHandler } from './ErrorHandler';

// Инициализация выполняется автоматически в SysInfoWidget.ts
