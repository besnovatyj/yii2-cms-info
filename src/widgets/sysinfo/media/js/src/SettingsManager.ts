/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Менеджер настроек пользователя
 * Сохраняет настройки в localStorage
 */
export interface SysInfoSettings {
    updateInterval: number; // в миллисекундах
    autoRefresh: boolean;
}

export class SettingsManager {
    private static STORAGE_KEY = 'sysinfo_settings';
    private settings: SysInfoSettings;

    constructor(defaults: SysInfoSettings) {
        this.settings = this.load(defaults);
    }

    /**
     * Загрузить настройки из localStorage
     */
    private load(defaults: SysInfoSettings): SysInfoSettings {
        try {
            const stored = localStorage.getItem(SettingsManager.STORAGE_KEY);
            if (stored) {
                return { ...defaults, ...JSON.parse(stored) };
            }
        } catch (e) {
            console.warn('Failed to load settings from localStorage', e);
        }
        return defaults;
    }

    /**
     * Сохранить настройки в localStorage
     */
    save(settings: Partial<SysInfoSettings>): void {
        this.settings = { ...this.settings, ...settings };
        try {
            localStorage.setItem(SettingsManager.STORAGE_KEY, JSON.stringify(this.settings));
        } catch (e) {
            console.warn('Failed to save settings to localStorage', e);
        }
    }

    /**
     * Получить текущие настройки
     */
    get(): SysInfoSettings {
        return { ...this.settings };
    }

    /**
     * Получить интервал обновления
     */
    getUpdateInterval(): number {
        return this.settings.updateInterval;
    }

    /**
     * Установить интервал обновления
     */
    setUpdateInterval(interval: number): void {
        this.save({ updateInterval: interval });
    }

    /**
     * Проверить, включено ли авто-обновление
     */
    isAutoRefreshEnabled(): boolean {
        return this.settings.autoRefresh;
    }

    /**
     * Установить авто-обновление
     */
    setAutoRefresh(enabled: boolean): void {
        this.save({ autoRefresh: enabled });
    }
}
