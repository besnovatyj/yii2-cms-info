class c {
  constructor(e, t) {
    this.endpoints = e, this.csrfToken = t;
  }
  /**
   * Получить все метрики
   */
  async getAllMetrics() {
    return this.get(this.endpoints.metrics);
  }
  /**
   * Получить метрики для real-time обновления
   */
  async getRealtimeMetrics() {
    return this.get(this.endpoints.realtime);
  }
  /**
   * Получить логи Docker контейнера
   */
  async getDockerLogs(e, t = 100) {
    const s = `${this.endpoints.dockerLogs}?container=${encodeURIComponent(e)}&lines=${t}`;
    return this.get(s);
  }
  /**
   * Получить статистику Docker контейнера
   */
  async getDockerStats(e) {
    const t = `${this.endpoints.dockerStats}?container=${encodeURIComponent(e)}`;
    return this.get(t);
  }
  /**
   * GET запрос с обработкой ошибок
   */
  async get(e) {
    const t = await fetch(e, {
      method: "GET",
      headers: this.getHeaders(),
      credentials: "same-origin"
    });
    if (!t.ok)
      throw new Error(`HTTP error! status: ${t.status}`);
    return t.json();
  }
  /**
   * Получить заголовки для запроса
   */
  getHeaders() {
    const e = new Headers();
    return e.append("X-CSRF-Token", this.csrfToken), e.append("X-Requested-With", "XMLHttpRequest"), e.append("Accept", "application/json"), e;
  }
  /**
   * Получить URL для экспорта JSON
   */
  getExportJsonUrl() {
    return this.endpoints.exportJson;
  }
  /**
   * Получить URL для экспорта CSV
   */
  getExportCsvUrl() {
    return this.endpoints.exportCsv;
  }
}
class m {
  /**
   * Обновить время последнего обновления
   */
  updateTimestamp(e) {
    const t = document.getElementById("sysinfo-last-update");
    t && (t.textContent = e);
  }
  /**
   * Обновить статус подключения
   */
  updateStatus(e, t = "") {
    const s = document.getElementById("sysinfo-status-badge");
    s && (e ? (s.className = "badge bg-success", s.textContent = "Подключено") : (s.className = "badge bg-danger", s.textContent = t || "Отключено"));
  }
  /**
   * Обновить вкладку Overview
   */
  renderOverview(e) {
    if (e.system?.available && e.system.server) {
      const t = e.system.server;
      this.renderHtml("overview-server-content", `
                <p class="mb-1"><strong>${t.hostname}</strong></p>
                <p class="text-muted small mb-0">${t.os}</p>
                <p class="text-muted small mb-0">IP: ${t.serverAddr}</p>
            `);
    }
    if (e.system?.available && e.system.cpu) {
      const t = e.system.cpu;
      this.renderHtml("overview-cpu-content", `
                <p class="mb-1"><strong>${t.cores} cores</strong></p>
                <p class="text-muted small mb-0">${t.model || "N/A"}</p>
                <p class="text-muted small mb-0">${t.frequency || "N/A"}</p>
            `);
    }
    if (e.system?.available && e.system.memory) {
      const t = e.system.memory;
      this.renderHtml("overview-memory-content", `
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Used: ${t.usedFormatted}</span>
                        <span>${t.usedPercent}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar ${this.getProgressColor(t.usedPercent)}"
                             style="width: ${t.usedPercent}%"></div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Total: ${t.totalFormatted}</p>
            `);
    }
    if (e.system?.available && e.system.disk) {
      const t = e.system.disk;
      this.renderHtml("overview-disk-content", `
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Used: ${t.usedFormatted}</span>
                        <span>${t.percent}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar ${this.getProgressColor(t.percent)}"
                             style="width: ${t.percent}%"></div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Total: ${t.totalFormatted}</p>
            `);
    }
    if (e.docker?.available && e.docker.version !== void 0) {
      const t = e.docker, s = t.containers?.filter((a) => a.state === "running").length || 0;
      this.renderHtml("overview-docker-content", `
                <p class="mb-1"><strong>${t.containers?.length || 0} контейнеров</strong></p>
                <p class="text-muted small mb-0">Running: ${s}</p>
                <p class="text-muted small mb-0">Version: ${t.version || "N/A"}</p>
            `);
    } else e.docker?.available === !1 && this.renderHtml("overview-docker-content", `
                <p class="text-muted">Docker не доступен</p>
            `);
    if (e.database?.available && e.database.driver !== void 0) {
      const t = e.database;
      this.renderHtml("overview-database-content", `
                <p class="mb-1"><strong>${t.driver || "MySQL"}</strong></p>
                <p class="text-muted small mb-0">Version: ${t.version || "N/A"}</p>
                <p class="text-muted small mb-0">Size: ${t.size?.formatted || "N/A"}</p>
            `);
    } else e.database?.available === !1 && this.renderHtml("overview-database-content", `
                <p class="text-muted">База данных не доступна</p>
            `);
  }
  /**
   * Обновить вкладку Docker
   */
  renderDocker(e) {
    if (!e?.available) {
      this.renderHtml("docker-containers", `
                <div class="alert alert-warning">Docker недоступен</div>
            `);
      return;
    }
    const t = e.containers || [], s = document.getElementById("docker-container-count");
    if (s && (s.textContent = t.length.toString()), t.length === 0) {
      this.renderHtml("docker-containers", `
                <div class="alert alert-info">Нет контейнеров</div>
            `);
      return;
    }
    let a = `
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
    t.forEach((n) => {
      const d = n.state === "running" ? '<span class="badge bg-success">Running</span>' : '<span class="badge bg-secondary">Stopped</span>';
      a += `
                <tr>
                    <td><code>${n.name}</code></td>
                    <td>${d}</td>
                    <td><small class="text-muted">${n.image}</small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary docker-logs-btn"
                                data-container="${n.name}">
                            <i class="bi bi-file-text"></i> Логи
                        </button>
                    </td>
                </tr>
            `;
    }), a += "</tbody></table>", this.renderHtml("docker-containers", a);
  }
  /**
   * Вспомогательный метод для рендеринга HTML
   */
  renderHtml(e, t) {
    const s = document.getElementById(e);
    s && (s.innerHTML = t);
  }
  /**
   * Получить цвет прогресс-бара по проценту
   */
  getProgressColor(e) {
    return e >= 90 ? "bg-danger" : e >= 75 ? "bg-warning" : e >= 50 ? "bg-info" : "bg-success";
  }
  /**
   * Обновить вкладку System
   */
  renderSystem(e) {
    if (!e.system?.available) return;
    const t = e.system;
    if (t.server && this.renderHtml("system-server-info", `
                <dl class="row mb-0">
                    <dt class="col-sm-3">Hostname:</dt>
                    <dd class="col-sm-9"><code>${t.server.hostname || "N/A"}</code></dd>
                    <dt class="col-sm-3">OS:</dt>
                    <dd class="col-sm-9">${t.server.os || "N/A"}</dd>
                    <dt class="col-sm-3">Kernel:</dt>
                    <dd class="col-sm-9">${t.server.kernel || "N/A"}</dd>
                    <dt class="col-sm-3">Uptime:</dt>
                    <dd class="col-sm-9">${t.uptime?.formatted || "N/A"}</dd>
                    <dt class="col-sm-3">Server IP:</dt>
                    <dd class="col-sm-9"><code>${t.server.serverAddr || "N/A"}</code></dd>
                    <dt class="col-sm-3">Remote IP:</dt>
                    <dd class="col-sm-9"><code>${t.server.remoteAddr || "N/A"}</code></dd>
                </dl>
            `), t.cpu) {
      const s = t.cpu;
      this.renderHtml("system-cpu-info", `
                <dl class="row mb-0">
                    <dt class="col-sm-3">Model:</dt>
                    <dd class="col-sm-9">${s.model || "N/A"}</dd>
                    <dt class="col-sm-3">Cores:</dt>
                    <dd class="col-sm-9">${s.cores || "N/A"}</dd>
                    <dt class="col-sm-3">Frequency:</dt>
                    <dd class="col-sm-9">${s.frequency || "N/A"}</dd>
                    <dt class="col-sm-3">Cache:</dt>
                    <dd class="col-sm-9">${s.cache || "N/A"}</dd>
                    <dt class="col-sm-3">BogoMIPS:</dt>
                    <dd class="col-sm-9">${s.bogomips || "N/A"}</dd>
                </dl>
            `);
    }
  }
  /**
   * Обновить вкладку Resources
   */
  renderResources(e) {
    if (!e.system?.available) return;
    const t = e.system;
    if (t.memory) {
      const s = t.memory;
      this.renderHtml("resources-memory-details", `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Total:</dt>
                    <dd class="col-sm-8"><strong>${s.totalFormatted || "N/A"}</strong></dd>
                    <dt class="col-sm-4">Used:</dt>
                    <dd class="col-sm-8">${s.usedFormatted || "N/A"} (${s.usedPercent || 0}%)</dd>
                    <dt class="col-sm-4">Free:</dt>
                    <dd class="col-sm-8">${s.freeFormatted || "N/A"}</dd>
                    <dt class="col-sm-4">Buffers:</dt>
                    <dd class="col-sm-8">${s.buffersFormatted || "N/A"}</dd>
                    <dt class="col-sm-4">Cached:</dt>
                    <dd class="col-sm-8">${s.cachedFormatted || "N/A"}</dd>
                </dl>
            `);
    }
    if (t.disk) {
      const s = t.disk;
      this.renderHtml("resources-disk-details", `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Total:</dt>
                    <dd class="col-sm-8"><strong>${s.totalFormatted || "N/A"}</strong></dd>
                    <dt class="col-sm-4">Used:</dt>
                    <dd class="col-sm-8">${s.usedFormatted || "N/A"} (${s.percent || 0}%)</dd>
                    <dt class="col-sm-4">Free:</dt>
                    <dd class="col-sm-8">${s.freeFormatted || "N/A"}</dd>
                    <dt class="col-sm-4">Mount:</dt>
                    <dd class="col-sm-8"><code>${s.mount || "/"}</code></dd>
                </dl>
            `);
    }
    if (t.loadavg) {
      const s = t.loadavg;
      this.renderHtml("resources-loadavg", `
                <dl class="row mb-0">
                    <dt class="col-sm-4">1 minute:</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">${s["1min"] || "N/A"}</span></dd>
                    <dt class="col-sm-4">5 minutes:</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">${s["5min"] || "N/A"}</span></dd>
                    <dt class="col-sm-4">15 minutes:</dt>
                    <dd class="col-sm-8"><span class="badge bg-info">${s["15min"] || "N/A"}</span></dd>
                </dl>
            `);
    }
    if (t.network && typeof t.network == "object") {
      let s = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Interface</th><th>RX</th><th>TX</th></tr></thead><tbody>';
      for (const [a, n] of Object.entries(t.network)) {
        const d = n;
        s += `
                    <tr>
                        <td><code>${a}</code></td>
                        <td>${d.rxFormatted || "N/A"}</td>
                        <td>${d.txFormatted || "N/A"}</td>
                    </tr>
                `;
      }
      s += "</tbody></table></div>", this.renderHtml("resources-network", s);
    }
  }
  /**
   * Обновить вкладку Services
   * ВАЖНО: данные сервисов статические, обновляются только при полной загрузке
   */
  renderServices(e) {
    if (e.php?.available && e.php.version) {
      const t = e.php;
      this.renderHtml("services-php", `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Version:</dt>
                    <dd class="col-sm-8"><strong>${t.version || "N/A"}</strong></dd>
                    <dt class="col-sm-4">SAPI:</dt>
                    <dd class="col-sm-8">${t.sapi || "N/A"}</dd>
                    <dt class="col-sm-4">Memory Limit:</dt>
                    <dd class="col-sm-8">${t.memory?.limitFormatted || "N/A"}</dd>
                    <dt class="col-sm-4">Max Execution:</dt>
                    <dd class="col-sm-8">${t.limits?.maxExecutionTime || "N/A"}s</dd>
                    <dt class="col-sm-4">OPcache:</dt>
                    <dd class="col-sm-8">${t.opcache?.enabled ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-secondary">Disabled</span>'}</dd>
                </dl>
            `);
    } else e.php?.available === !1 && this.renderHtml("services-php", '<p class="text-muted">PHP информация недоступна</p>');
    if (e.nginx?.available && e.nginx.version !== void 0) {
      const t = e.nginx, s = t.protocol || "N/A", a = t.http2 ? '<span class="badge bg-success">HTTP/2</span>' : `<span class="badge bg-warning text-dark">${s}</span>`, n = t.https ? '<span class="badge bg-success">HTTPS</span>' : '<span class="badge bg-secondary">нет</span>';
      let d = `
                <dt class="col-sm-4">Version:</dt>
                <dd class="col-sm-8"><strong>${t.version || "N/A"}</strong></dd>
                <dt class="col-sm-4">Software:</dt>
                <dd class="col-sm-8"><code>${t.serverSoftware || "N/A"}</code></dd>
                <dt class="col-sm-4">Protocol:</dt>
                <dd class="col-sm-8">${a}</dd>
                <dt class="col-sm-4">HTTPS:</dt>
                <dd class="col-sm-8">${n}</dd>
                <dt class="col-sm-4">Gateway:</dt>
                <dd class="col-sm-8"><code>${t.gateway || "N/A"}</code></dd>
            `;
      t.tls && t.tls.protocol && (d += `
                    <dt class="col-sm-4">TLS:</dt>
                    <dd class="col-sm-8">${t.tls.protocol} / <code>${t.tls.cipher || "N/A"}</code></dd>
                `), t.build && t.build.tlsLibrary && (d += `
                    <dt class="col-sm-4">TLS lib:</dt>
                    <dd class="col-sm-8"><small>${t.build.tlsLibrary}</small></dd>
                `), this.renderHtml("services-nginx", `<dl class="row mb-0">${d}</dl>`);
    } else e.nginx?.available === !1 && this.renderHtml("services-nginx", '<p class="text-muted">Nginx информация недоступна</p>');
    if (e.database?.available && e.database.driver) {
      const t = e.database;
      this.renderHtml("services-mysql", `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Driver:</dt>
                    <dd class="col-sm-8"><strong>${t.driver || "MySQL"}</strong></dd>
                    <dt class="col-sm-4">Version:</dt>
                    <dd class="col-sm-8">${t.version || "N/A"}</dd>
                    <dt class="col-sm-4">Database Size:</dt>
                    <dd class="col-sm-8">${t.size?.formatted || "N/A"}</dd>
                    <dt class="col-sm-4">Tables:</dt>
                    <dd class="col-sm-8">${t.tables?.count || "N/A"}</dd>
                    <dt class="col-sm-4">Connections:</dt>
                    <dd class="col-sm-8">${t.connections?.current || "N/A"}</dd>
                </dl>
            `);
    } else e.database?.available === !1 && this.renderHtml("services-mysql", '<p class="text-muted">База данных недоступна</p>');
    if (e.redis?.available && e.redis.version) {
      const t = e.redis;
      this.renderHtml("services-redis", `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Version:</dt>
                    <dd class="col-sm-8"><strong>${t.version || "N/A"}</strong></dd>
                    <dt class="col-sm-4">Uptime:</dt>
                    <dd class="col-sm-8">${t.uptime?.formatted || "N/A"}</dd>
                    <dt class="col-sm-4">Clients:</dt>
                    <dd class="col-sm-8">${t.clients?.connected || "N/A"}</dd>
                    <dt class="col-sm-4">Memory:</dt>
                    <dd class="col-sm-8">${t.memory?.usedFormatted || "N/A"}</dd>
                    <dt class="col-sm-4">Keys:</dt>
                    <dd class="col-sm-8">${t.keyspace?.totalKeys || "N/A"}</dd>
                </dl>
            `);
    } else e.redis?.available === !1 && this.renderHtml("services-redis", '<p class="text-muted">Redis недоступен</p>');
    if (e.application?.available && e.application.yii) {
      const t = e.application, s = t.environment?.environment || "N/A", a = t.environment?.debug ?? !1;
      this.renderHtml("services-application", `
                <dl class="row mb-0">
                    <dt class="col-sm-4">Yii Version:</dt>
                    <dd class="col-sm-8"><strong>${t.yii?.version || "N/A"}</strong></dd>
                    <dt class="col-sm-4">Environment:</dt>
                    <dd class="col-sm-8"><span class="badge ${s === "prod" ? "bg-success" : "bg-warning"}">${s}</span></dd>
                    <dt class="col-sm-4">Debug:</dt>
                    <dd class="col-sm-8">${a ? '<span class="badge bg-warning">ON</span>' : '<span class="badge bg-success">OFF</span>'}</dd>
                    <dt class="col-sm-4">Cache:</dt>
                    <dd class="col-sm-8"><code>${t.cache?.class || "N/A"}</code></dd>
                    <dt class="col-sm-4">Queue:</dt>
                    <dd class="col-sm-8"><code>${t.queue?.class || "N/A"}</code></dd>
                </dl>
            `);
    } else e.application?.available === !1 && this.renderHtml("services-application", '<p class="text-muted">Информация о приложении недоступна</p>');
  }
  /**
   * Форматировать объект в HTML список
   */
  objectToHtml(e, t = 0) {
    let s = '<dl class="mb-0">';
    for (const [a, n] of Object.entries(e))
      s += `<dt class="text-muted small">${a}:</dt>`, typeof n == "object" && n !== null ? s += `<dd>${this.objectToHtml(n, t + 1)}</dd>` : s += `<dd><code>${n}</code></dd>`;
    return s += "</dl>", s;
  }
}
class h {
  constructor(e, t) {
    this.intervalId = null, this.isRunning = !1, this.updateCallback = e, this.intervalMs = t;
  }
  /**
   * Запустить автообновление
   */
  start() {
    this.isRunning || (this.isRunning = !0, this.intervalId = window.setInterval(() => {
      this.updateCallback();
    }, this.intervalMs), console.log(`[RealtimeUpdater] Started with interval ${this.intervalMs}ms`));
  }
  /**
   * Остановить автообновление
   */
  stop() {
    this.isRunning && (this.intervalId !== null && (window.clearInterval(this.intervalId), this.intervalId = null), this.isRunning = !1, console.log("[RealtimeUpdater] Stopped"));
  }
  /**
   * Проверить, запущено ли обновление
   */
  isActive() {
    return this.isRunning;
  }
  /**
   * Переключить состояние (пауза/возобновление)
   */
  toggle() {
    return this.isRunning ? this.stop() : this.start(), this.isRunning;
  }
  /**
   * Изменить интервал обновления
   */
  setInterval(e) {
    this.intervalMs = e, this.isRunning && (this.stop(), this.start());
  }
  /**
   * Получить текущий интервал
   */
  getInterval() {
    return this.intervalMs;
  }
}
class p {
  constructor() {
    this.cpuChart = null, this.memoryChart = null, this.maxDataPoints = 60, this.cpuData = [], this.memoryData = [], this.labels = [], this.initCharts();
  }
  /**
   * Инициализировать графики
   */
  initCharts() {
    if (typeof window.Chart > "u") {
      console.warn("[ChartManager] Chart.js is not loaded");
      return;
    }
    const e = window.Chart, t = document.getElementById("chart-cpu");
    t && (this.cpuChart = new e(t.getContext("2d"), {
      type: "line",
      data: {
        labels: this.labels,
        datasets: [{
          label: "CPU Usage (%)",
          data: this.cpuData,
          borderColor: "rgb(54, 162, 235)",
          backgroundColor: "rgba(54, 162, 235, 0.1)",
          tension: 0.3,
          fill: !0
        }]
      },
      options: {
        responsive: !0,
        maintainAspectRatio: !1,
        animation: !1,
        // Отключаем анимацию для производительности
        scales: {
          y: {
            beginAtZero: !0,
            max: 100,
            ticks: {
              callback: (a) => a + "%"
            }
          },
          x: {
            display: !1
            // Скрываем ось X для экономии места
          }
        },
        plugins: {
          legend: {
            display: !1
          },
          tooltip: {
            callbacks: {
              label: (a) => `CPU: ${a.parsed.y.toFixed(1)}%`
            }
          }
        }
      }
    }));
    const s = document.getElementById("chart-memory");
    s && (this.memoryChart = new e(s.getContext("2d"), {
      type: "line",
      data: {
        labels: this.labels,
        datasets: [{
          label: "Memory Usage (%)",
          data: this.memoryData,
          borderColor: "rgb(255, 99, 132)",
          backgroundColor: "rgba(255, 99, 132, 0.1)",
          tension: 0.3,
          fill: !0
        }]
      },
      options: {
        responsive: !0,
        maintainAspectRatio: !1,
        animation: !1,
        scales: {
          y: {
            beginAtZero: !0,
            max: 100,
            ticks: {
              callback: (a) => a + "%"
            }
          },
          x: {
            display: !1
          }
        },
        plugins: {
          legend: {
            display: !1
          },
          tooltip: {
            callbacks: {
              label: (a) => `Memory: ${a.parsed.y.toFixed(1)}%`
            }
          }
        }
      }
    }));
  }
  /**
   * Добавить новую точку данных
   */
  addDataPoint(e, t) {
    if (!this.cpuChart || !this.memoryChart)
      return;
    const s = (/* @__PURE__ */ new Date()).toLocaleTimeString();
    this.labels.push(s), this.cpuData.push(e), this.memoryData.push(t), this.labels.length > this.maxDataPoints && (this.labels.shift(), this.cpuData.shift(), this.memoryData.shift()), this.cpuChart.update("none"), this.memoryChart.update("none");
  }
  /**
   * Вычислить процент использования CPU из массива значений
   */
  calculateCpuPercent(e) {
    if (!e) return 0;
    const t = e.user + e.nice + e.system + e.idle + e.iowait + e.irq + e.softirq + e.steal;
    if (t === 0) return 0;
    const s = e.idle;
    return Math.min(100, Math.max(0, (t - s) / t * 100));
  }
  /**
   * Очистить графики
   */
  clear() {
    this.labels = [], this.cpuData = [], this.memoryData = [], this.cpuChart && this.cpuChart.update("none"), this.memoryChart && this.memoryChart.update("none");
  }
  /**
   * Уничтожить графики
   */
  destroy() {
    this.cpuChart && (this.cpuChart.destroy(), this.cpuChart = null), this.memoryChart && (this.memoryChart.destroy(), this.memoryChart = null);
  }
}
class u {
  constructor(e, t) {
    this.apiService = e, this.errorHandler = t, this.modalElement = document.getElementById("dockerLogsModal"), this.modalElement && typeof window.bootstrap < "u" && (this.modal = new window.bootstrap.Modal(this.modalElement));
  }
  /**
   * Показать логи контейнера
   */
  async show(e, t = 100) {
    if (!this.modal) {
      this.errorHandler.handle("Bootstrap Modal is not available");
      return;
    }
    const s = document.getElementById("docker-logs-container-name");
    s && (s.textContent = e);
    const a = document.getElementById("docker-logs-content");
    a && (a.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>'), this.modal.show();
    try {
      const n = await this.apiService.getDockerLogs(e, t);
      n.success ? this.displayLogs(n.logs) : this.displayError(n.message || "Failed to load logs");
    } catch (n) {
      this.displayError(n instanceof Error ? n.message : "Unknown error"), this.errorHandler.handle(n, "Failed to load Docker logs");
    }
  }
  /**
   * Отобразить логи в модальном окне
   */
  displayLogs(e) {
    const t = document.getElementById("docker-logs-content");
    if (t) {
      const s = t.querySelector("code");
      s && (s.textContent = e || "No logs available"), t.scrollTop = t.scrollHeight;
    }
  }
  /**
   * Отобразить ошибку
   */
  displayError(e) {
    const t = document.getElementById("docker-logs-content");
    if (t) {
      const s = t.querySelector("code");
      s && (s.textContent = `Error: ${e}`), t.classList.add("text-danger");
    }
  }
  /**
   * Скрыть модальное окно
   */
  hide() {
    this.modal && this.modal.hide();
  }
}
const r = class r {
  constructor(e) {
    this.settings = this.load(e);
  }
  /**
   * Загрузить настройки из localStorage
   */
  load(e) {
    try {
      const t = localStorage.getItem(r.STORAGE_KEY);
      if (t)
        return { ...e, ...JSON.parse(t) };
    } catch (t) {
      console.warn("Failed to load settings from localStorage", t);
    }
    return e;
  }
  /**
   * Сохранить настройки в localStorage
   */
  save(e) {
    this.settings = { ...this.settings, ...e };
    try {
      localStorage.setItem(r.STORAGE_KEY, JSON.stringify(this.settings));
    } catch (t) {
      console.warn("Failed to save settings to localStorage", t);
    }
  }
  /**
   * Получить текущие настройки
   */
  get() {
    return { ...this.settings };
  }
  /**
   * Получить интервал обновления
   */
  getUpdateInterval() {
    return this.settings.updateInterval;
  }
  /**
   * Установить интервал обновления
   */
  setUpdateInterval(e) {
    this.save({ updateInterval: e });
  }
  /**
   * Проверить, включено ли авто-обновление
   */
  isAutoRefreshEnabled() {
    return this.settings.autoRefresh;
  }
  /**
   * Установить авто-обновление
   */
  setAutoRefresh(e) {
    this.save({ autoRefresh: e });
  }
};
r.STORAGE_KEY = "sysinfo_settings";
let i = r;
class g {
  /**
   * Обработать ошибку
   */
  handle(e, t) {
    const s = e instanceof Error ? e.message : e, a = t ? `${t}: ${s}` : s;
    console.error("[SysInfo Error]", a, e), typeof window.showAlert == "function" ? window.showAlert({
      message: a,
      type: "error",
      duration: 5e3
    }) : alert(`Error: ${a}`);
  }
  /**
   * Обработать предупреждение
   */
  warn(e) {
    console.warn("[SysInfo Warning]", e), typeof window.showAlert == "function" && window.showAlert({
      message: e,
      type: "warning",
      duration: 4e3
    });
  }
  /**
   * Показать успешное сообщение
   */
  success(e) {
    typeof window.showAlert == "function" && window.showAlert({
      message: e,
      type: "success",
      duration: 3e3
    });
  }
  /**
   * Показать информационное сообщение
   */
  info(e) {
    typeof window.showAlert == "function" && window.showAlert({
      message: e,
      type: "info",
      duration: 3e3
    });
  }
}
class f {
  constructor(e) {
    const t = document.getElementById(e);
    if (!t)
      throw new Error(`Container with id "${e}" not found`);
    this.container = t;
    const s = t.getAttribute("data-config");
    if (!s)
      throw new Error("Widget configuration not found in data-config attribute");
    try {
      this.config = JSON.parse(s);
    } catch {
      throw new Error("Failed to parse widget configuration");
    }
    this.errorHandler = new g(), this.apiService = new c(this.config.endpoints, this.config.csrfToken), this.metricsRenderer = new m(), this.chartManager = new p(), this.dockerLogsModal = new u(this.apiService, this.errorHandler), this.settingsManager = new i({
      updateInterval: this.config.updateInterval,
      autoRefresh: this.config.autoRefresh
    }), this.realtimeUpdater = new h(
      () => this.updateRealtimeMetrics(),
      this.settingsManager.getUpdateInterval()
    ), console.log("[SysInfoWidget] Initialized", this.config);
  }
  /**
   * Инициализация виджета
   */
  async initialize() {
    try {
      this.setupEventHandlers(), await this.loadInitialMetrics(), this.settingsManager.isAutoRefreshEnabled() && this.realtimeUpdater.start(), console.log("[SysInfoWidget] Ready");
    } catch (e) {
      this.errorHandler.handle(e, "Initialization failed");
    }
  }
  /**
   * Загрузить начальные метрики (полные)
   */
  async loadInitialMetrics() {
    try {
      const e = await this.apiService.getAllMetrics();
      this.updateUI(e), this.errorHandler.success("Метрики загружены");
    } catch (e) {
      this.errorHandler.handle(e, "Failed to load initial metrics"), this.metricsRenderer.updateStatus(!1, "Ошибка загрузки");
    }
  }
  /**
   * Обновить метрики в режиме real-time (легковесные)
   */
  async updateRealtimeMetrics() {
    try {
      const e = await this.apiService.getRealtimeMetrics();
      this.updateUI(e);
    } catch (e) {
      console.error("[SysInfoWidget] Realtime update failed", e), this.metricsRenderer.updateStatus(!1, "Ошибка обновления");
    }
  }
  /**
   * Обновить UI с новыми метриками
   */
  updateUI(e) {
    if (this.metricsRenderer.updateTimestamp(e.timestampFormatted || "-"), this.metricsRenderer.updateStatus(!0), this.metricsRenderer.renderOverview(e), this.metricsRenderer.renderSystem(e), this.metricsRenderer.renderResources(e), this.metricsRenderer.renderServices(e), e.docker && (this.metricsRenderer.renderDocker(e.docker), this.attachDockerLogsHandlers()), e.system?.cpuUsage && e.system?.memory) {
      const t = this.chartManager.calculateCpuPercent(e.system.cpuUsage), s = e.system.memory.usedPercent || 0;
      this.chartManager.addDataPoint(t, s);
    }
  }
  /**
   * Настроить обработчики событий
   */
  setupEventHandlers() {
    const e = document.getElementById("sysinfo-btn-refresh");
    e && e.addEventListener("click", () => this.handleRefreshClick());
    const t = document.getElementById("sysinfo-btn-pause");
    t && t.addEventListener("click", () => this.handlePauseClick(t));
    const s = document.getElementById("sysinfo-btn-settings");
    s && s.addEventListener("click", () => this.handleSettingsClick());
    const a = document.getElementById("sysinfo-export-json");
    a && a.addEventListener("click", (l) => {
      l.preventDefault(), window.location.href = this.apiService.getExportJsonUrl();
    });
    const n = document.getElementById("sysinfo-export-csv");
    n && n.addEventListener("click", (l) => {
      l.preventDefault(), window.location.href = this.apiService.getExportCsvUrl();
    });
    const d = document.getElementById("settings-save");
    d && d.addEventListener("click", () => this.handleSaveSettings());
  }
  /**
   * Прикрепить обработчики к кнопкам Docker логов
   */
  attachDockerLogsHandlers() {
    document.querySelectorAll(".docker-logs-btn").forEach((t) => {
      t.addEventListener("click", (s) => {
        const n = s.currentTarget.getAttribute("data-container");
        n && this.dockerLogsModal.show(n, 100);
      });
    });
  }
  /**
   * Обработчик клика по кнопке обновления
   */
  async handleRefreshClick() {
    this.errorHandler.info("Обновление метрик..."), await this.loadInitialMetrics();
  }
  /**
   * Обработчик клика по кнопке паузы
   */
  handlePauseClick(e) {
    this.realtimeUpdater.toggle() ? (e.innerHTML = '<i class="bi bi-pause-fill"></i> Пауза', this.errorHandler.info("Авто-обновление возобновлено")) : (e.innerHTML = '<i class="bi bi-play-fill"></i> Возобновить', this.errorHandler.info("Авто-обновление приостановлено"));
  }
  /**
   * Обработчик клика по кнопке настроек
   */
  handleSettingsClick() {
    const e = this.settingsManager.get(), t = document.getElementById("settings-update-interval"), s = document.getElementById("settings-auto-refresh");
    t && (t.value = (e.updateInterval / 1e3).toString()), s && (s.checked = e.autoRefresh);
    const a = document.getElementById("settingsModal");
    a && typeof window.bootstrap < "u" && new window.bootstrap.Modal(a).show();
  }
  /**
   * Обработчик сохранения настроек
   */
  handleSaveSettings() {
    const e = document.getElementById("settings-update-interval"), t = document.getElementById("settings-auto-refresh");
    if (e && t) {
      const s = parseInt(e.value) * 1e3, a = t.checked;
      this.settingsManager.setUpdateInterval(s), this.settingsManager.setAutoRefresh(a), this.realtimeUpdater.setInterval(s), a && !this.realtimeUpdater.isActive() ? this.realtimeUpdater.start() : !a && this.realtimeUpdater.isActive() && this.realtimeUpdater.stop(), this.errorHandler.success("Настройки сохранены");
      const n = document.getElementById("settingsModal");
      if (n && typeof window.bootstrap < "u") {
        const d = window.bootstrap.Modal.getInstance(n);
        d && d.hide();
      }
    }
  }
  /**
   * Уничтожить виджет
   */
  destroy() {
    this.realtimeUpdater.stop(), this.chartManager.destroy(), console.log("[SysInfoWidget] Destroyed");
  }
}
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".sysinfo-widget").forEach((e) => {
    new f(e.id).initialize().catch(console.error);
  });
});
export {
  c as ApiService,
  p as ChartManager,
  u as DockerLogsModal,
  g as ErrorHandler,
  m as MetricsRenderer,
  h as RealtimeUpdater,
  i as SettingsManager,
  f as SysInfoWidget
};
//# sourceMappingURL=index.js.map
