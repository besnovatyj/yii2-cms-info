/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * API Service для работы с backend endpoints
 * Использует Fetch API с CSRF токенами
 */
export class ApiService {
    private endpoints: Record<string, string>;
    private csrfToken: string;

    constructor(endpoints: Record<string, string>, csrfToken: string) {
        this.endpoints = endpoints;
        this.csrfToken = csrfToken;
    }

    /**
     * Получить все метрики
     */
    async getAllMetrics(): Promise<any> {
        return this.get(this.endpoints.metrics);
    }

    /**
     * Получить метрики для real-time обновления
     */
    async getRealtimeMetrics(): Promise<any> {
        return this.get(this.endpoints.realtime);
    }

    /**
     * Получить логи Docker контейнера
     */
    async getDockerLogs(container: string, lines: number = 100): Promise<any> {
        const url = `${this.endpoints.dockerLogs}?container=${encodeURIComponent(container)}&lines=${lines}`;
        return this.get(url);
    }

    /**
     * Получить статистику Docker контейнера
     */
    async getDockerStats(container: string): Promise<any> {
        const url = `${this.endpoints.dockerStats}?container=${encodeURIComponent(container)}`;
        return this.get(url);
    }

    /**
     * GET запрос с обработкой ошибок
     */
    private async get(url: string): Promise<any> {
        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders(),
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    /**
     * Получить заголовки для запроса
     */
    private getHeaders(): Headers {
        const headers = new Headers();
        headers.append('X-CSRF-Token', this.csrfToken);
        headers.append('X-Requested-With', 'XMLHttpRequest');
        headers.append('X-Requested-With-Fetch', 'true');
        headers.append('Accept', 'application/json');
        return headers;
    }

    /**
     * Получить URL для экспорта JSON
     */
    getExportJsonUrl(): string {
        return this.endpoints.exportJson;
    }

    /**
     * Получить URL для экспорта CSV
     */
    getExportCsvUrl(): string {
        return this.endpoints.exportCsv;
    }
}
