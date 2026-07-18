/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Рендерер метрик в UI
 * Обновляет DOM элементы с данными из API
 */
export class MetricsRenderer {
    /**
     * Обновить время последнего обновления
     */
    updateTimestamp(timestamp: string): void {
        const element = document.getElementById('sysinfo-last-update');
        if (element) {
            element.textContent = timestamp;
        }
    }

    /**
     * Обновить статус подключения
     */
    updateStatus(connected: boolean, message: string = ''): void {
        const badge = document.getElementById('sysinfo-status-badge');
        if (badge) {
            if (connected) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Подключено';
            } else {
                badge.className = 'badge bg-danger';
                badge.textContent = message || 'Отключено';
            }
        }
    }

    /**
     * Обновить вкладку Overview
     */
    renderOverview(metrics: any): void {
        // Server (только при полной загрузке)
        if (metrics.system?.available && metrics.system.server) {
            const server = metrics.system.server;
            this.renderHtml('overview-server-content', `
                <p class="mb-1"><strong>${server.hostname}</strong></p>
                <p class="text-muted small mb-0">${server.os}</p>
                <p class="text-muted small mb-0">IP: ${server.serverAddr}</p>
            `);
        }

        // CPU (только при полной загрузке)
        if (metrics.system?.available && metrics.system.cpu) {
            const cpu = metrics.system.cpu;
            this.renderHtml('overview-cpu-content', `
                <p class="mb-1"><strong>${cpu.cores} cores</strong></p>
                <p class="text-muted small mb-0">${cpu.model || 'N/A'}</p>
                <p class="text-muted small mb-0">${cpu.frequency || 'N/A'}</p>
            `);
        }

        // Memory (обновляется всегда)
        if (metrics.system?.available && metrics.system.memory) {
            const mem = metrics.system.memory;
            this.renderHtml('overview-memory-content', `
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Used: ${mem.usedFormatted}</span>
                        <span>${mem.usedPercent}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar ${this.getProgressColor(mem.usedPercent)}"
                             style="width: ${mem.usedPercent}%"></div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Total: ${mem.totalFormatted}</p>
            `);
        }

        // Disk (обновляется всегда)
        if (metrics.system?.available && metrics.system.disk) {
            const disk = metrics.system.disk;
            this.renderHtml('overview-disk-content', `
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Used: ${disk.usedFormatted}</span>
                        <span>${disk.percent}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar ${this.getProgressColor(disk.percent)}"
                             style="width: ${disk.percent}%"></div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Total: ${disk.totalFormatted}</p>
            `);
        }

        // Docker (только при полной загрузке с version)
        if (metrics.docker?.available && metrics.docker.version !== undefined) {
            const docker = metrics.docker;
            const running = docker.containers?.filter((c: any) => c.state === 'running').length || 0;
            this.renderHtml('overview-docker-content', `
                <p class="mb-1"><strong>${docker.containers?.length || 0} контейнеров</strong></p>
                <p class="text-muted small mb-0">Running: ${running}</p>
                <p class="text-muted small mb-0">Version: ${docker.version || 'N/A'}</p>
            `);
        } else if (metrics.docker?.available === false) {
            this.renderHtml('overview-docker-content', `
                <p class="text-muted">Docker не доступен</p>
            `);
        }

        // Database (только при полной загрузке с driver и version)
        if (metrics.database?.available && metrics.database.driver !== undefined) {
            const db = metrics.database;
            this.renderHtml('overview-database-content', `
                <p class="mb-1"><strong>${db.driver || 'MySQL'}</strong></p>
                <p class="text-muted small mb-0">Version: ${db.version || 'N/A'}</p>
                <p class="text-muted small mb-0">Size: ${db.size?.formatted || 'N/A'}</p>
            `);
        } else if (metrics.database?.available === false) {
            this.renderHtml('overview-database-content', `
                <p class="text-muted">База данных не доступна</p>
            `);
        }
    }

    /**
     * Обновить вкладку Docker
     */
    renderDocker(dockerMetrics: any): void {
        if (!dockerMetrics?.available) {
            this.renderHtml('docker-containers', `
                <div class="alert alert-warning">Docker недоступен</div>
            `);
            return;
        }

        const containers = dockerMetrics.containers || [];
        const countElement = document.getElementById('docker-container-count');
        if (countElement) {
            countElement.textContent = containers.length.toString();
        }

        if (containers.length === 0) {
            this.renderHtml('docker-containers', `
                <div class="alert alert-info">Нет контейнеров</div>
            `);
            return;
        }

        let html = `
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Состояние</th>
                        <th>Image</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
        `;

        containers.forEach((container: any) => {
            const stateBadge = container.state === 'running'
                ? '<span class="badge bg-success">Running</span>'
                : '<span class="badge bg-secondary">Stopped</span>';

            html += `
                <tr>
                    <td><code>${container.name}</code></td>
                    <td>${stateBadge}</td>
                    <td><small class="text-muted">${container.image}</small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary docker-logs-btn"
                                data-container="${container.name}">
                            <i class="bi bi-file-text"></i> Логи
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        this.renderHtml('docker-containers', html);
    }

    /**
     * Вспомогательный метод для рендеринга HTML
     */
    private renderHtml(elementId: string, html: string): void {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = html;
        }
    }

    /**
     * Получить цвет прогресс-бара по проценту
     */
    private getProgressColor(percent: number): string {
        if (percent >= 90) return 'bg-danger';
        if (percent >= 75) return 'bg-warning';
        if (percent >= 50) return 'bg-info';
        return 'bg-success';
    }

    /**
     * Обновить вкладку System
     */
    renderSystem(metrics: any): void {
        if (!metrics.system?.available) return;

        const system = metrics.system;

        // Server Info
        if (system.server) {
            this.renderHtml('system-server-info', `
                <dl class="row mb-0">
                    <dt class="col-sm-3">Hostname:</dt>
                    <dd class="col-sm-9"><code>${system.server.hostname || 'N/A'}</code></dd>
                    <dt class="col-sm-3">OS:</dt>
                    <dd class="col-sm-9">${system.server.os || 'N/A'}</dd>
                    <dt class="col-sm-3">Kernel:</dt>
                    <dd class="col-sm-9">${system.server.kernel || 'N/A'}</dd>
                    <dt class="col-sm-3">Uptime:</dt>
                    <dd class="col-sm-9">${system.uptime?.formatted || 'N/A'}</dd>
                    <dt class="col-sm-3">Server IP:</dt>
                    <dd class="col-sm-9"><code>${system.server.serverAddr || 'N/A'}</code></dd>
                    <dt class="col-sm-3">Remote IP:</dt>
                    <dd class="col-sm-9"><code>${system.server.remoteAddr || 'N/A'}</code></dd>
                </dl>
            `);
        }

        // CPU Info
        if (system.cpu) {
            const cpu = system.cpu;
            this.renderHtml('system-cpu-info', `
                <dl class="row mb-0">
                    <dt class="col-sm-3">Model:</dt>
                    <dd class="col-sm-9">${cpu.model || 'N/A'}</dd>
                    <dt class="col-sm-3">Cores:</dt>
                    <dd class="col-sm-9">${cpu.cores || 'N/A'}</dd>
                    <dt class="col-sm-3">Frequency:</dt>
                    <dd class="col-sm-9">${cpu.frequency || 'N/A'}</dd>
                    <dt class="col-sm-3">Cache:</dt>
                    <dd class="col-sm-9">${cpu.cache || 'N/A'}</dd>
                    <dt class="col-sm-3">BogoMIPS:</dt>
                    <dd class="col-sm-9">${cpu.bogomips || 'N/A'}</dd>
                </dl>
            `);
        }
    }

    /**
     * Обновить вкладку Resources
     */
    renderResources(metrics: any): void {
        if (!metrics.system?.available) return;

        const system = metrics.system;

        // Memory Details
        if (system.memory) {
            const mem = system.memory;
            this.renderHtml('resources-memory-details', `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Total:</dt>
                    <dd class="col-sm-8"><strong>${mem.totalFormatted || 'N/A'}</strong></dd>
                    <dt class="col-sm-4">Used:</dt>
                    <dd class="col-sm-8">${mem.usedFormatted || 'N/A'} (${mem.usedPercent || 0}%)</dd>
                    <dt class="col-sm-4">Free:</dt>
                    <dd class="col-sm-8">${mem.freeFormatted || 'N/A'}</dd>
                    <dt class="col-sm-4">Buffers:</dt>
                    <dd class="col-sm-8">${mem.buffersFormatted || 'N/A'}</dd>
                    <dt class="col-sm-4">Cached:</dt>
                    <dd class="col-sm-8">${mem.cachedFormatted || 'N/A'}</dd>
                </dl>
            `);
        }

        // Disk Details
        if (system.disk) {
            const disk = system.disk;
            this.renderHtml('resources-disk-details', `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Total:</dt>
                    <dd class="col-sm-8"><strong>${disk.totalFormatted || 'N/A'}</strong></dd>
                    <dt class="col-sm-4">Used:</dt>
                    <dd class="col-sm-8">${disk.usedFormatted || 'N/A'} (${disk.percent || 0}%)</dd>
                    <dt class="col-sm-4">Free:</dt>
                    <dd class="col-sm-8">${disk.freeFormatted || 'N/A'}</dd>
                    <dt class="col-sm-4">Mount:</dt>
                    <dd class="col-sm-8"><code>${disk.mount || '/'}</code></dd>
                </dl>
            `);
        }

        // Load Average (loadavg - нижний регистр!)
        if (system.loadavg) {
            const load = system.loadavg;
            this.renderHtml('resources-loadavg', `
                <dl class="row mb-0">
                    <dt class="col-sm-4">1 minute:</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">${load['1min'] || 'N/A'}</span></dd>
                    <dt class="col-sm-4">5 minutes:</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">${load['5min'] || 'N/A'}</span></dd>
                    <dt class="col-sm-4">15 minutes:</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">${load['15min'] || 'N/A'}</span></dd>
                </dl>
            `);
        }

        // Network Interfaces (это объект, не массив!)
        if (system.network && typeof system.network === 'object') {
            let html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Interface</th><th>RX</th><th>TX</th></tr></thead><tbody>';
            for (const [ifaceName, ifaceData] of Object.entries(system.network)) {
                const iface = ifaceData as any;
                html += `
                    <tr>
                        <td><code>${ifaceName}</code></td>
                        <td>${iface.rxFormatted || 'N/A'}</td>
                        <td>${iface.txFormatted || 'N/A'}</td>
                    </tr>
                `;
            }
            html += '</tbody></table></div>';
            this.renderHtml('resources-network', html);
        }
    }

    /**
     * Обновить вкладку Services
     * ВАЖНО: данные сервисов статические, обновляются только при полной загрузке
     */
    renderServices(metrics: any): void {
        // PHP - проверяем наличие всех вложенных данных
        if (metrics.php?.available && metrics.php.version) {
            const php = metrics.php;
            this.renderHtml('services-php', `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Version:</dt>
                    <dd class="col-sm-8"><strong>${php.version || 'N/A'}</strong></dd>
                    <dt class="col-sm-4">SAPI:</dt>
                    <dd class="col-sm-8">${php.sapi || 'N/A'}</dd>
                    <dt class="col-sm-4">Memory Limit:</dt>
                    <dd class="col-sm-8">${php.memory?.limitFormatted || 'N/A'}</dd>
                    <dt class="col-sm-4">Max Execution:</dt>
                    <dd class="col-sm-8">${php.limits?.maxExecutionTime || 'N/A'}s</dd>
                    <dt class="col-sm-4">OPcache:</dt>
                    <dd class="col-sm-8">${php.opcache?.enabled ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-secondary">Disabled</span>'}</dd>
                </dl>
            `);
        } else if (metrics.php?.available === false) {
            this.renderHtml('services-php', '<p class="text-muted">PHP информация недоступна</p>');
        }
        // Если данных нет вообще - не обновляем (пропускаем)

        // Nginx - проверяем полные данные (version приходит только при полной загрузке)
        if (metrics.nginx?.available && metrics.nginx.version !== undefined) {
            const nginx = metrics.nginx;
            const protocol = nginx.protocol || 'N/A';
            const protoBadge = nginx.http2
                ? '<span class="badge bg-success">HTTP/2</span>'
                : `<span class="badge bg-warning text-dark">${protocol}</span>`;
            const httpsBadge = nginx.https
                ? '<span class="badge bg-success">HTTPS</span>'
                : '<span class="badge bg-secondary">нет</span>';

            let rows = `
                <dt class="col-sm-4">Version:</dt>
                <dd class="col-sm-8"><strong>${nginx.version || 'N/A'}</strong></dd>
                <dt class="col-sm-4">Software:</dt>
                <dd class="col-sm-8"><code>${nginx.serverSoftware || 'N/A'}</code></dd>
                <dt class="col-sm-4">Protocol:</dt>
                <dd class="col-sm-8">${protoBadge}</dd>
                <dt class="col-sm-4">HTTPS:</dt>
                <dd class="col-sm-8">${httpsBadge}</dd>
                <dt class="col-sm-4">Gateway:</dt>
                <dd class="col-sm-8"><code>${nginx.gateway || 'N/A'}</code></dd>
            `;

            if (nginx.tls) {
                let tlsValue: string;
                if (nginx.tls.state === 'ok') {
                    tlsValue = `${nginx.tls.protocol} / <code>${nginx.tls.cipher || 'N/A'}</code>`;
                } else if (nginx.tls.state === 'plain') {
                    tlsValue = '<span class="text-muted">запрос к nginx без TLS (dev :80 / терминация выше)</span>';
                } else {
                    tlsValue = '<span class="text-warning">fastcgi_param SSL_* не проброшен в этот vhost</span>';
                }
                rows += `
                    <dt class="col-sm-4">TLS:</dt>
                    <dd class="col-sm-8">${tlsValue}</dd>
                `;
            }

            if (nginx.build && nginx.build.tlsLibrary) {
                rows += `
                    <dt class="col-sm-4">TLS lib:</dt>
                    <dd class="col-sm-8"><small>${nginx.build.tlsLibrary}</small></dd>
                `;
            }

            this.renderHtml('services-nginx', `<dl class="row mb-0">${rows}</dl>`);
        } else if (metrics.nginx?.available === false) {
            this.renderHtml('services-nginx', '<p class="text-muted">Nginx информация недоступна</p>');
        }

        // MySQL/Database - проверяем полные данные
        if (metrics.database?.available && metrics.database.driver) {
            const db = metrics.database;
            this.renderHtml('services-mysql', `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Driver:</dt>
                    <dd class="col-sm-8"><strong>${db.driver || 'MySQL'}</strong></dd>
                    <dt class="col-sm-4">Version:</dt>
                    <dd class="col-sm-8">${db.version || 'N/A'}</dd>
                    <dt class="col-sm-4">Database Size:</dt>
                    <dd class="col-sm-8">${db.size?.formatted || 'N/A'}</dd>
                    <dt class="col-sm-4">Tables:</dt>
                    <dd class="col-sm-8">${db.tables?.count || 'N/A'}</dd>
                    <dt class="col-sm-4">Connections:</dt>
                    <dd class="col-sm-8">${db.connections?.current || 'N/A'}</dd>
                </dl>
            `);
        } else if (metrics.database?.available === false) {
            this.renderHtml('services-mysql', '<p class="text-muted">База данных недоступна</p>');
        }

        // Redis - проверяем полные данные
        if (metrics.redis?.available && metrics.redis.version) {
            const redis = metrics.redis;
            this.renderHtml('services-redis', `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Version:</dt>
                    <dd class="col-sm-8"><strong>${redis.version || 'N/A'}</strong></dd>
                    <dt class="col-sm-4">Uptime:</dt>
                    <dd class="col-sm-8">${redis.uptime?.formatted || 'N/A'}</dd>
                    <dt class="col-sm-4">Clients:</dt>
                    <dd class="col-sm-8">${redis.clients?.connected || 'N/A'}</dd>
                    <dt class="col-sm-4">Memory:</dt>
                    <dd class="col-sm-8">${redis.memory?.usedFormatted || 'N/A'}</dd>
                    <dt class="col-sm-4">Keys:</dt>
                    <dd class="col-sm-8">${redis.keyspace?.totalKeys || 'N/A'}</dd>
                </dl>
            `);
        } else if (metrics.redis?.available === false) {
            this.renderHtml('services-redis', '<p class="text-muted">Redis недоступен</p>');
        }

        // Yii2 Application - проверяем полные данные
        if (metrics.application?.available && metrics.application.yii) {
            const app = metrics.application;
            const env = app.environment?.environment || 'N/A';
            const debug = app.environment?.debug ?? false;
            this.renderHtml('services-application', `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Yii Version:</dt>
                    <dd class="col-sm-8"><strong>${app.yii?.version || 'N/A'}</strong></dd>
                    <dt class="col-sm-4">Environment:</dt>
                    <dd class="col-sm-8"><span class="badge ${env === 'prod' ? 'bg-success' : 'bg-warning'}">${env}</span></dd>
                    <dt class="col-sm-4">Debug:</dt>
                    <dd class="col-sm-8">${debug ? '<span class="badge bg-warning">ON</span>' : '<span class="badge bg-success">OFF</span>'}</dd>
                    <dt class="col-sm-4">Cache:</dt>
                    <dd class="col-sm-8"><code>${app.cache?.class || 'N/A'}</code></dd>
                    <dt class="col-sm-4">Queue:</dt>
                    <dd class="col-sm-8"><code>${app.queue?.class || 'N/A'}</code></dd>
                </dl>
            `);
        } else if (metrics.application?.available === false) {
            this.renderHtml('services-application', '<p class="text-muted">Информация о приложении недоступна</p>');
        }
    }

    /**
     * Форматировать объект в HTML список
     */
    private objectToHtml(obj: any, indent: number = 0): string {
        let html = '<dl class="mb-0">';
        for (const [key, value] of Object.entries(obj)) {
            html += `<dt class="text-muted small">${key}:</dt>`;
            if (typeof value === 'object' && value !== null) {
                html += `<dd>${this.objectToHtml(value, indent + 1)}</dd>`;
            } else {
                html += `<dd><code>${value}</code></dd>`;
            }
        }
        html += '</dl>';
        return html;
    }
}
