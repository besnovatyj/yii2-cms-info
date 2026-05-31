/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Обработчик ошибок
 * Показывает уведомления пользователю через глобальную функцию showAlert
 */
export class ErrorHandler {
    /**
     * Обработать ошибку
     */
    handle(error: Error | string, context?: string): void {
        const message = error instanceof Error ? error.message : error;
        const fullMessage = context ? `${context}: ${message}` : message;

        console.error('[SysInfo Error]', fullMessage, error);

        // Используем глобальную функцию showAlert (из simpleAlert.js)
        if (typeof (window as any).showAlert === 'function') {
            (window as any).showAlert({
                message: fullMessage,
                type: 'error',
                duration: 5000,
            });
        } else {
            // Fallback на alert если showAlert недоступен
            alert(`Error: ${fullMessage}`);
        }
    }

    /**
     * Обработать предупреждение
     */
    warn(message: string): void {
        console.warn('[SysInfo Warning]', message);

        if (typeof (window as any).showAlert === 'function') {
            (window as any).showAlert({
                message,
                type: 'warning',
                duration: 4000,
            });
        }
    }

    /**
     * Показать успешное сообщение
     */
    success(message: string): void {
        if (typeof (window as any).showAlert === 'function') {
            (window as any).showAlert({
                message,
                type: 'success',
                duration: 3000,
            });
        }
    }

    /**
     * Показать информационное сообщение
     */
    info(message: string): void {
        if (typeof (window as any).showAlert === 'function') {
            (window as any).showAlert({
                message,
                type: 'info',
                duration: 3000,
            });
        }
    }
}
