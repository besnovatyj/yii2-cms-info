/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Менеджер real-time обновлений
 * Управляет таймером для автоматического обновления метрик
 */
export class RealtimeUpdater {
    private intervalId: number | null = null;
    private updateCallback: () => void;
    private intervalMs: number;
    private isRunning: boolean = false;

    constructor(updateCallback: () => void, intervalMs: number) {
        this.updateCallback = updateCallback;
        this.intervalMs = intervalMs;
    }

    /**
     * Запустить автообновление
     */
    start(): void {
        if (this.isRunning) {
            return;
        }

        this.isRunning = true;
        this.intervalId = window.setInterval(() => {
            this.updateCallback();
        }, this.intervalMs);

        console.log(`[RealtimeUpdater] Started with interval ${this.intervalMs}ms`);
    }

    /**
     * Остановить автообновление
     */
    stop(): void {
        if (!this.isRunning) {
            return;
        }

        if (this.intervalId !== null) {
            window.clearInterval(this.intervalId);
            this.intervalId = null;
        }

        this.isRunning = false;
        console.log('[RealtimeUpdater] Stopped');
    }

    /**
     * Проверить, запущено ли обновление
     */
    isActive(): boolean {
        return this.isRunning;
    }

    /**
     * Переключить состояние (пауза/возобновление)
     */
    toggle(): boolean {
        if (this.isRunning) {
            this.stop();
        } else {
            this.start();
        }
        return this.isRunning;
    }

    /**
     * Изменить интервал обновления
     */
    setInterval(intervalMs: number): void {
        this.intervalMs = intervalMs;

        // Перезапустить если был активен
        if (this.isRunning) {
            this.stop();
            this.start();
        }
    }

    /**
     * Получить текущий интервал
     */
    getInterval(): number {
        return this.intervalMs;
    }
}
