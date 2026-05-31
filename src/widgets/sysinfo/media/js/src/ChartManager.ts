/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Менеджер графиков Chart.js
 * Управляет графиками CPU и Memory с историей в 60 точек (3 минуты)
 */
export class ChartManager {
    private cpuChart: any = null;
    private memoryChart: any = null;
    private maxDataPoints: number = 60; // 60 точек × 3 сек = 3 минуты
    private cpuData: number[] = [];
    private memoryData: number[] = [];
    private labels: string[] = [];

    constructor() {
        // Chart.js будет загружен глобально через CDN или npm
        this.initCharts();
    }

    /**
     * Инициализировать графики
     */
    private initCharts(): void {
        // Проверка доступности Chart.js
        if (typeof (window as any).Chart === 'undefined') {
            console.warn('[ChartManager] Chart.js is not loaded');
            return;
        }

        const Chart = (window as any).Chart;

        // CPU Chart
        const cpuCanvas = document.getElementById('chart-cpu') as HTMLCanvasElement;
        if (cpuCanvas) {
            this.cpuChart = new Chart(cpuCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: this.labels,
                    datasets: [{
                        label: 'CPU Usage (%)',
                        data: this.cpuData,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Отключаем анимацию для производительности
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: (value: any) => value + '%'
                            }
                        },
                        x: {
                            display: false // Скрываем ось X для экономии места
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (context: any) => `CPU: ${context.parsed.y.toFixed(1)}%`
                            }
                        }
                    }
                }
            });
        }

        // Memory Chart
        const memoryCanvas = document.getElementById('chart-memory') as HTMLCanvasElement;
        if (memoryCanvas) {
            this.memoryChart = new Chart(memoryCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: this.labels,
                    datasets: [{
                        label: 'Memory Usage (%)',
                        data: this.memoryData,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: (value: any) => value + '%'
                            }
                        },
                        x: {
                            display: false
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (context: any) => `Memory: ${context.parsed.y.toFixed(1)}%`
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Добавить новую точку данных
     */
    addDataPoint(cpuPercent: number, memoryPercent: number): void {
        if (!this.cpuChart || !this.memoryChart) {
            return;
        }

        // Добавить метку времени
        const time = new Date().toLocaleTimeString();
        this.labels.push(time);
        this.cpuData.push(cpuPercent);
        this.memoryData.push(memoryPercent);

        // Ограничение количества точек
        if (this.labels.length > this.maxDataPoints) {
            this.labels.shift();
            this.cpuData.shift();
            this.memoryData.shift();
        }

        // Обновить графики без анимации
        this.cpuChart.update('none');
        this.memoryChart.update('none');
    }

    /**
     * Вычислить процент использования CPU из массива значений
     */
    calculateCpuPercent(cpuUsage: any): number {
        if (!cpuUsage) return 0;

        const total = cpuUsage.user + cpuUsage.nice + cpuUsage.system +
                     cpuUsage.idle + cpuUsage.iowait + cpuUsage.irq +
                     cpuUsage.softirq + cpuUsage.steal;

        if (total === 0) return 0;

        const idle = cpuUsage.idle;
        return Math.min(100, Math.max(0, ((total - idle) / total) * 100));
    }

    /**
     * Очистить графики
     */
    clear(): void {
        this.labels = [];
        this.cpuData = [];
        this.memoryData = [];

        if (this.cpuChart) {
            this.cpuChart.update('none');
        }
        if (this.memoryChart) {
            this.memoryChart.update('none');
        }
    }

    /**
     * Уничтожить графики
     */
    destroy(): void {
        if (this.cpuChart) {
            this.cpuChart.destroy();
            this.cpuChart = null;
        }
        if (this.memoryChart) {
            this.memoryChart.destroy();
            this.memoryChart = null;
        }
    }
}
