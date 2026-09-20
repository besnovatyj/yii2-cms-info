/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

import { ApiService } from './ApiService';
import { ErrorHandler } from './ErrorHandler';

/**
 * Менеджер модального окна Docker логов
 */
export class DockerLogsModal {
    private apiService: ApiService;
    private errorHandler: ErrorHandler;
    private modal: any; // Bootstrap Modal instance
    private modalElement: HTMLElement;

    constructor(apiService: ApiService, errorHandler: ErrorHandler) {
        this.apiService = apiService;
        this.errorHandler = errorHandler;
        this.modalElement = document.getElementById('dockerLogsModal')!;

        // Инициализация Bootstrap Modal
        if (this.modalElement && typeof (window as any).bootstrap !== 'undefined') {
            this.modal = new (window as any).bootstrap.Modal(this.modalElement);
        }
    }

    /**
     * Показать логи контейнера
     */
    async show(containerName: string, lines: number = 100): Promise<void> {
        if (!this.modal) {
            this.errorHandler.handle('Bootstrap Modal is not available');
            return;
        }

        // Установить название контейнера
        const titleElement = document.getElementById('docker-logs-container-name');
        if (titleElement) {
            titleElement.textContent = containerName;
        }

        // Показать loading
        const contentElement = document.getElementById('docker-logs-content');
        if (contentElement) {
            contentElement.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
        }

        // Показать модалку
        this.modal.show();

        try {
            // Загрузить логи
            const response = await this.apiService.getDockerLogs(containerName, lines);

            if (response.success) {
                this.displayLogs(response.logs);
            } else {
                this.displayError(response.message || 'Failed to load logs');
            }
        } catch (error) {
            this.displayError(error instanceof Error ? error.message : 'Unknown error');
            this.errorHandler.handle(error as Error, 'Failed to load Docker logs');
        }
    }

    /**
     * Отобразить логи в модальном окне
     */
    private displayLogs(logs: string): void {
        this.setContent(logs || 'No logs available', false);
    }

    /**
     * Отобразить ошибку
     */
    private displayError(message: string): void {
        this.setContent(`Error: ${message}`, true);
    }

    /**
     * Записать текст в тело модального окна.
     *
     * Элемент `<code>` создаётся заново, а не ищется в DOM: спиннер загрузки подменяет
     * собой всё содержимое `<pre>` вместе с `<code>` из разметки, и поиск возвращал бы
     * `null` — ответ сервера было бы некуда писать, а в окне навсегда оставался бы
     * крутящийся спиннер. `textContent` заодно экранирует содержимое логов.
     */
    private setContent(text: string, isError: boolean): void {
        const contentElement = document.getElementById('docker-logs-content');
        if (!contentElement) {
            return;
        }

        const code = document.createElement('code');
        code.textContent = text;
        contentElement.replaceChildren(code);

        // Класс ошибки снимается явно, иначе он пережил бы следующую успешную загрузку.
        contentElement.classList.toggle('text-danger', isError);

        // Прокрутка вниз
        contentElement.scrollTop = contentElement.scrollHeight;
    }

    /**
     * Скрыть модальное окно
     */
    hide(): void {
        if (this.modal) {
            this.modal.hide();
        }
    }
}
