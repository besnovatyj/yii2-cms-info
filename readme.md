# System Information Widget - Полная документация

## Оглавление
- [Обзор](#обзор)
- [Архитектура](#архитектура)
- [Компоненты Backend](#компоненты-backend)
- [Компоненты Frontend](#компоненты-frontend)
- [API Endpoints](#api-endpoints)
- [Конфигурация](#конфигурация)
- [Метрики](#метрики)
- [Безопасность](#безопасность)
- [Производительность](#производительность)
- [Troubleshooting](#troubleshooting)

---

## Обзор

Виджет системной информации предоставляет комплексный мониторинг сервера в режиме реального времени через веб-интерфейс администратора.

### Основные возможности

✅ **Мониторинг в реальном времени**
- Автоматическое обновление каждые 3 секунды
- Без кэширования - данные всегда актуальные
- Нет использования jQuery - только нативный TypeScript

✅ **Метрики системы**
- CPU: использование, модель, количество ядер, частота
- RAM: общая/используемая/свободная память, buffers, cached
- Disk: размер, использование, точка монтирования
- Load Average: 1/5/15 минут
- Network: статистика по интерфейсам (RX/TX)
- Temperature: температура процессора (если доступно)

✅ **Метрики сервисов**
- PHP: версия, SAPI, memory_limit, OPcache
- MySQL: версия, размер БД, количество таблиц, соединения
- Redis: версия, uptime, клиенты, память, ключи
- Yii2: версия, окружение, debug, кэш, очередь

✅ **Docker метрики**
- Список всех контейнеров с их статусами
- Версия Docker
- Количество images, volumes, networks
- Просмотр логов контейнеров (100 последних строк)

✅ **Визуализация**
- Графики CPU/Memory (Chart.js) - 60 точек × 3 секунды = 3 минуты истории
- Bootstrap 5 интерфейс с вкладками
- Прогресс-бары с цветовой индикацией
- Responsive дизайн

✅ **Дополнительные функции**
- Экспорт метрик в JSON/CSV
- Настройка интервала обновления
- Пауза/возобновление auto-refresh
- Просмотр Docker логов в модальном окне

---

## Архитектура

### Clean Architecture Pattern

Проект следует принципам Clean Architecture с четким разделением слоев:

```
┌─────────────────────────────────────────────────────┐
│                   VIEW LAYER                        │
│  ┌──────────────────────────────────────────────┐  │
│  │  SysInfoWidget (TypeScript)                  │  │
│  │  - Инициализация                             │  │
│  │  - Обработка событий                         │  │
│  │  - Координация компонентов                   │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│                PRESENTATION LAYER                   │
│  ┌──────────────────────────────────────────────┐  │
│  │  MetricsRenderer                             │  │
│  │  - Рендеринг Overview/System/Resources/etc  │  │
│  │  - Обновление DOM элементов                 │  │
│  └──────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────┐  │
│  │  ChartManager (Chart.js)                     │  │
│  │  - Создание графиков CPU/Memory             │  │
│  │  - Обновление точек данных                  │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│                 SERVICE LAYER                       │
│  ┌──────────────────────────────────────────────┐  │
│  │  ApiService (Fetch API)                      │  │
│  │  - HTTP запросы к backend                    │  │
│  │  - Обработка CSRF токенов                    │  │
│  └──────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────┐  │
│  │  RealtimeUpdater                             │  │
│  │  - Таймер auto-refresh (3 секунды)          │  │
│  │  - Start/Stop/Toggle                         │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│               CONTROLLER LAYER                      │
│  ┌──────────────────────────────────────────────┐  │
│  │  SysInfoController                           │  │
│  │  - actionApiMetrics() - полные метрики       │  │
│  │  - actionApiRealtime() - легковесные         │  │
│  │  - actionApiDockerLogs() - логи контейнера   │  │
│  │  - actionExportJson/Csv() - экспорт          │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│                 SERVICE LAYER                       │
│  ┌──────────────────────────────────────────────┐  │
│  │  SysInfoService                              │  │
│  │  - getAllMetrics() - все метрики             │  │
│  │  - getRealtimeMetrics() - только динамика    │  │
│  │  - exportToJson/Csv() - форматирование       │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│                PROVIDER LAYER                       │
│  ┌──────────────────────────────────────────────┐  │
│  │  MetricProviderInterface                     │  │
│  │  - isAvailable() - проверка доступности      │  │
│  │  - getMetrics() - получение метрик           │  │
│  └──────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────┐  │
│  │  SystemMetricProvider                        │  │
│  │  PhpMetricProvider                           │  │
│  │  DockerMetricProvider                        │  │
│  │  DatabaseMetricProvider                      │  │
│  │  RedisMetricProvider                         │  │
│  │  ApplicationMetricProvider                   │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│                  DATA SOURCES                       │
│  - /proc/stat, /proc/meminfo, /proc/loadavg        │
│  - Docker API (через socket-proxy)                 │
│  - MySQL queries (information_schema)              │
│  - Redis INFO command                              │
│  - PHP functions (phpinfo, opcache_get_status)     │
└─────────────────────────────────────────────────────┘
```

### Принципы разделения ответственности

1. **Providers** - отвечают только за сбор raw данных из источников
2. **Service** - бизнес-логика, агрегация данных из провайдеров
3. **Controller** - HTTP слой, валидация запросов, отправка ответов
4. **Frontend Components** - UI логика, рендеринг, управление состоянием

---

## Компоненты Backend

### 1. Metric Providers

#### BaseMetricProvider

Абстрактный базовый класс для всех провайдеров.

**Расположение:** `/app/Besnovatyj/Info/providers/BaseMetricProvider.php`

**Ключевые методы:**
```php
abstract public function getName(): string;
abstract public function getCategory(): string;
abstract protected function checkAvailability(): bool;
abstract protected function collectMetrics(): array;

public function isAvailable(): bool;
public function getMetrics(): array;
protected function formatBytes(int $bytes, int $precision = 2): string;
protected function safeShellExec(string $command, int $timeout = 5): ?string;
protected function safeFileReadLines(string $path): ?array;
```

**Особенности:**
- Все провайдеры stateless (без сохранения состояния)
- Встроенная обработка ошибок с try/catch
- Безопасное выполнение shell команд с timeout
- Форматирование байтов в человекочитаемый вид
- Метод `isAvailable()` проверяет доступность источника данных

#### SystemMetricProvider

Собирает метрики системы Linux из `/proc`.

**Расположение:** `/app/Besnovatyj/Info/providers/SystemMetricProvider.php`

**Источники данных:**
- `/proc/cpuinfo` - информация о процессоре
- `/proc/stat` - использование CPU
- `/proc/meminfo` - информация о памяти
- `/proc/diskstats` - статистика дисков
- `/proc/loadavg` - load average
- `/proc/net/dev` - сетевые интерфейсы
- `/proc/uptime` - время работы системы
- `/sys/class/thermal/thermal_zone*/temp` - температура

**Возвращаемая структура:**
```php
[
    'server' => [
        'hostname' => 'server-name',
        'serverAddr' => '192.168.1.100',
        'remoteAddr' => '192.168.1.50',
        'os' => 'Ubuntu 22.04 LTS',
        'kernel' => '5.15.0-91-generic',
        'architecture' => 'x86_64'
    ],
    'cpu' => [
        'model' => 'Intel(R) Xeon(R) CPU E5-2680 v4',
        'cores' => 8,
        'frequency' => '2.40 GHz',
        'cache' => '35840 KB',
        'bogomips' => '4800.00'
    ],
    'cpuUsage' => [
        'user' => 1234567,
        'nice' => 123,
        'system' => 234567,
        'idle' => 9876543,
        'iowait' => 1234,
        'irq' => 123,
        'softirq' => 234
    ],
    'memory' => [
        'total' => 16777216000,
        'totalFormatted' => '15.63 GB',
        'used' => 8388608000,
        'usedFormatted' => '7.81 GB',
        'usedPercent' => 50.0,
        'free' => 8388608000,
        'freeFormatted' => '7.81 GB',
        'buffers' => 524288000,
        'buffersFormatted' => '500 MB',
        'cached' => 2097152000,
        'cachedFormatted' => '2.00 GB'
    ],
    'disk' => [
        'total' => 107374182400,
        'totalFormatted' => '100 GB',
        'used' => 53687091200,
        'usedFormatted' => '50 GB',
        'free' => 53687091200,
        'freeFormatted' => '50 GB',
        'percent' => 50,
        'mount' => '/'
    ],
    'loadavg' => [
        '1min' => 0.52,
        '5min' => 0.48,
        '15min' => 0.45,
        'formatted' => '0.52 0.48 0.45'
    ],
    'network' => [
        'eth0' => [
            'rx' => 1234567890,
            'tx' => 987654321,
            'rxFormatted' => '1.15 GB',
            'txFormatted' => '942.23 MB'
        ],
        'lo' => [...]
    ],
    'uptime' => [
        'seconds' => 864000,
        'formatted' => '10 days, 0 hours'
    ],
    'temperature' => [
        'cpu' => 45.5 // в градусах Цельсия
    ]
]
```

#### PhpMetricProvider

Собирает информацию о PHP и его расширениях.

**Расположение:** `/app/Besnovatyj/Info/providers/PhpMetricProvider.php`

**Источники данных:**
- `PHP_VERSION`, `PHP_SAPI`, `PHP_OS`
- `ini_get()` для лимитов
- `memory_get_usage()`, `memory_get_peak_usage()`
- `opcache_get_status()` для OPcache статистики
- `get_loaded_extensions()` для списка расширений

**Возвращаемая структура:**
```php
[
    'version' => '8.4.0',
    'sapi' => 'fpm-fcgi',
    'os' => 'Linux',
    'architecture' => '64-bit',
    'memory' => [
        'current' => 2097152,
        'currentFormatted' => '2.00 MB',
        'peak' => 4194304,
        'peakFormatted' => '4.00 MB',
        'limit' => 134217728,
        'limitFormatted' => '128 MB',
        'usagePercent' => 1.56
    ],
    'limits' => [
        'maxExecutionTime' => 30,
        'maxInputTime' => 60,
        'postMaxSize' => '8M',
        'uploadMaxFilesize' => '2M',
        'maxFileUploads' => 20,
        'defaultSocketTimeout' => 60
    ],
    'opcache' => [
        'enabled' => true,
        'memoryUsage' => [
            'used' => 16777216,
            'usedFormatted' => '16.00 MB',
            'free' => 117440512,
            'freeFormatted' => '112.00 MB',
            'wasted' => 0,
            'wastedFormatted' => '0 B',
            'usagePercent' => 12.5
        ],
        'statistics' => [
            'hits' => 12345,
            'misses' => 123,
            'hitRate' => 99.01
        ]
    ],
    'extensions' => [
        'Core', 'date', 'libxml', 'openssl', 'pcre', ...
    ]
]
```

#### DockerMetricProvider

Собирает метрики Docker через Docker CLI.

**Расположение:** `/app/Besnovatyj/Info/providers/DockerMetricProvider.php`

**Требования:**
- Docker CLI установлен в контейнере
- Переменная окружения `DOCKER_HOST=tcp://socket-proxy:2375`
- Доступ к socket-proxy с разрешениями CONTAINERS, IMAGES, VOLUMES, NETWORKS

**Команды:**
```bash
docker --version
docker ps -a --format "{{.ID}}|{{.Names}}|{{.State}}|{{.Status}}|{{.Image}}"
docker images -q | wc -l
docker volume ls -q | wc -l
docker network ls -q | wc -l
docker logs --tail 100 <container>
```

**Возвращаемая структура:**
```php
[
    'version' => '24.0.7',
    'containers' => [
        [
            'id' => 'abc123def456',
            'name' => 'yii2-cms-php-fpm-1',
            'state' => 'running',
            'status' => 'Up 2 hours',
            'image' => 'php:8.4-fpm-alpine3.23'
        ],
        // ... остальные контейнеры
    ],
    'images' => 15,
    'volumes' => 4,
    'networks' => 3
]
```

**Методы:**
- `getContainerLogs(string $containerId, int $lines = 100)` - получить логи контейнера

#### DatabaseMetricProvider

Собирает статистику MySQL/MariaDB.

**Расположение:** `/app/Besnovatyj/Info/providers/DatabaseMetricProvider.php`

**Источники данных:**
```sql
SELECT VERSION()
SELECT DATABASE()
SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = ?
SELECT COUNT(*), SUM(table_rows), SUM(data_length), SUM(index_length)
  FROM information_schema.TABLES WHERE table_schema = ?
SHOW STATUS
```

**Возвращаемая структура:**
```php
[
    'driver' => 'mysql',
    'version' => '8.0.35',
    'database' => 'yii2_cms',
    'charset' => 'utf8mb4',
    'size' => [
        'bytes' => 52428800,
        'formatted' => '50.00 MB'
    ],
    'tables' => [
        'count' => 45,
        'totalRows' => 123456,
        'dataSize' => [
            'bytes' => 41943040,
            'formatted' => '40.00 MB'
        ],
        'indexSize' => [
            'bytes' => 10485760,
            'formatted' => '10.00 MB'
        ]
    ],
    'connections' => [
        'current' => 5,
        'running' => 2,
        'cached' => 3,
        'created' => 150,
        'maxUsed' => 8
    ]
]
```

#### RedisMetricProvider

Собирает статистику Redis через Yii2 Redis компонент.

**Расположение:** `/app/Besnovatyj/Info/providers/RedisMetricProvider.php`

**Источники данных:**
```redis
PING
INFO
DBSIZE
```

**Возвращаемая структура:**
```php
[
    'version' => '7.2.3',
    'uptime' => [
        'seconds' => 864000,
        'formatted' => '10 days'
    ],
    'clients' => [
        'connected' => 5,
        'blocked' => 0
    ],
    'memory' => [
        'used' => 2097152,
        'usedFormatted' => '2.00 MB',
        'peak' => 4194304,
        'peakFormatted' => '4.00 MB',
        'max' => 0,
        'maxFormatted' => 'Unlimited',
        'fragmentation' => 1.05
    ],
    'stats' => [
        'totalConnections' => 1234,
        'totalCommands' => 567890,
        'keyspaceHits' => 123456,
        'keyspaceMisses' => 1234,
        'hitRate' => 99.01
    ],
    'keyspace' => [
        'totalKeys' => 5678,
        'databases' => [
            'db0' => ['keys' => 5678, 'expires' => 234]
        ]
    ]
]
```

#### ApplicationMetricProvider

Собирает информацию о Yii2 приложении.

**Расположение:** `/app/Besnovatyj/Info/providers/ApplicationMetricProvider.php`

**Источники данных:**
- `Yii::getVersion()`
- `YII_ENV`, `YII_DEBUG`
- Компоненты приложения (cache, session, queue)

**Возвращаемая структура:**
```php
[
    'yii' => [
        'version' => '2.0.49',
        'name' => 'Yii2 CMS',
        'id' => 'yii2-app'
    ],
    'environment' => [
        'environment' => 'dev',
        'debug' => true,
        'timezone' => 'Asia/Yekaterinburg',
        'language' => 'ru-RU'
    ],
    'cache' => [
        'enabled' => true,
        'class' => 'yii\\redis\\Cache',
        'keyPrefix' => 'yii2cms_'
    ],
    'session' => [
        'available' => true,
        'class' => 'yii\\redis\\Session',
        'timeout' => 3600
    ],
    'queue' => [
        'enabled' => true,
        'class' => 'yii\\queue\\redis\\Queue',
        'driver' => 'redis'
    ]
]
```

### 2. Service Layer

#### SysInfoService

Центральный сервис для агрегации всех метрик.

**Расположение:** `/app/Besnovatyj/Info/services/SysInfoService.php`

**Инжектируемые зависимости (через DI):**
```php
public function __construct(
    SystemMetricProvider $systemProvider,
    PhpMetricProvider $phpProvider,
    DockerMetricProvider $dockerProvider,
    DatabaseMetricProvider $databaseProvider,
    RedisMetricProvider $redisProvider,
    ApplicationMetricProvider $applicationProvider
)
```

**Ключевые методы:**

**`getAllMetrics(): array`**
Возвращает полные метрики всех провайдеров.

```php
[
    'timestamp' => 1737249600,
    'timestampFormatted' => '2026-01-19 01:00:00',
    'system' => [...],      // SystemMetricProvider
    'php' => [...],         // PhpMetricProvider
    'docker' => [...],      // DockerMetricProvider
    'database' => [...],    // DatabaseMetricProvider
    'redis' => [...],       // RedisMetricProvider
    'application' => [...]  // ApplicationMetricProvider
]
```

**`getRealtimeMetrics(): array`**
Возвращает только динамические метрики для auto-refresh (легковесный).

```php
[
    'timestamp' => 1737249603,
    'timestampFormatted' => '2026-01-19 01:00:03',
    'system' => [
        'available' => true,
        'cpuUsage' => [...],    // Текущее использование CPU
        'memory' => [...],      // Текущая память
        'disk' => [...],        // Текущий диск
        'loadavg' => [...],     // Load average
        'temperature' => [...]  // Температура
    ],
    'docker' => [
        'available' => true,
        'containers' => [...]   // Только статусы контейнеров
    ],
    'database' => [
        'available' => true,
        'connections' => [...]  // Только соединения
    ]
]
```

**Отличие от getAllMetrics:**
- Не возвращает статические данные (версии, hostname, конфигурацию)
- Размер ответа ~500 байт вместо ~2.5 Кб
- Вызывается каждые 3 секунды

**`exportToJson(): string`**
Форматирует все метрики в JSON с pretty print.

**`exportToCsv(): string`**
Конвертирует метрики в CSV формат (плоская структура).

### 3. Controller Layer

#### SysInfoController

HTTP контроллер для обработки запросов.

**Расположение:** `/app/Besnovatyj/Info/controllers/backend/SysInfoController.php`

**Endpoints:**

**`GET /info/backend/sys-info/index`**
```php
public function actionIndex(): string
```
Отображает главную страницу виджета.

**Возвращает:** HTML страницу с виджетом

---

**`GET /info/backend/sys-info/api-metrics`**
```php
public function actionApiMetrics(): Response
```
Полные метрики всех провайдеров.

**Возвращает:** JSON (~2.5 Кб)
```json
{
    "timestamp": 1737249600,
    "timestampFormatted": "2026-01-19 01:00:00",
    "system": {...},
    "php": {...},
    "docker": {...},
    "database": {...},
    "redis": {...},
    "application": {...}
}
```

---

**`GET /info/backend/sys-info/api-realtime`**
```php
public function actionApiRealtime(): Response
```
Легковесные метрики для auto-refresh.

**Возвращает:** JSON (~500 байт)
```json
{
    "timestamp": 1737249603,
    "timestampFormatted": "2026-01-19 01:00:03",
    "system": {
        "available": true,
        "cpuUsage": {...},
        "memory": {...},
        "disk": {...},
        "loadavg": {...},
        "temperature": {...}
    },
    "docker": {
        "available": true,
        "containers": [...]
    }
}
```

---

**`GET /info/backend/sys-info/api-docker-logs?container=name&lines=100`**
```php
public function actionApiDockerLogs(string $container, int $lines = 100): Response
```
Логи Docker контейнера.

**Параметры:**
- `container` (required) - ID или имя контейнера
- `lines` (optional) - количество строк (max 1000)

**Возвращает:** JSON
```json
{
    "success": true,
    "logs": "...",
    "lines": 100
}
```

---

**`GET /info/backend/sys-info/export-json`**
```php
public function actionExportJson(): Response
```
Экспорт метрик в JSON файл.

**Возвращает:** Файл `metrics-2026-01-19-010000.json`

---

**`GET /info/backend/sys-info/export-csv`**
```php
public function actionExportCsv(): Response
```
Экспорт метрик в CSV файл.

**Возвращает:** Файл `metrics-2026-01-19-010000.csv`

### 4. Dependency Injection

#### Container Configuration

**Расположение:** `/app/Besnovatyj/Info/config/container.php`

```php
<?php
use Besnovatyj\Info\providers\*;
use Besnovatyj\Info\services\SysInfoService;

return [
    'singletons' => [
        SystemMetricProvider::class => SystemMetricProvider::class,
        PhpMetricProvider::class => PhpMetricProvider::class,
        DockerMetricProvider::class => DockerMetricProvider::class,
        DatabaseMetricProvider::class => DatabaseMetricProvider::class,
        RedisMetricProvider::class => RedisMetricProvider::class,
        ApplicationMetricProvider::class => ApplicationMetricProvider::class,
        SysInfoService::class => [
            'class' => SysInfoService::class,
            '__construct()' => [
                'systemProvider' => Instance::of(SystemMetricProvider::class),
                'phpProvider' => Instance::of(PhpMetricProvider::class),
                'dockerProvider' => Instance::of(DockerMetricProvider::class),
                'databaseProvider' => Instance::of(DatabaseMetricProvider::class),
                'redisProvider' => Instance::of(RedisMetricProvider::class),
                'applicationProvider' => Instance::of(ApplicationMetricProvider::class),
            ],
        ],
    ],
];
```

**Подключение в Module.php:**
```php
public function getContainerConfig(): array
{
    return require __DIR__ . '/config/container.php';
}
```

---

## Компоненты Frontend

### TypeScript Architecture

Проект использует современный TypeScript + Vite без jQuery.

**Структура:**
```
media/js/
├── src/
│   ├── index.ts                  # Entry point
│   ├── SysInfoWidget.ts          # Main widget class
│   ├── ApiService.ts             # HTTP client (Fetch API)
│   ├── MetricsRenderer.ts        # DOM rendering
│   ├── RealtimeUpdater.ts        # Auto-refresh timer
│   ├── ChartManager.ts           # Chart.js wrapper
│   ├── DockerLogsModal.ts        # Docker logs modal
│   ├── SettingsManager.ts        # User settings (localStorage)
│   └── ErrorHandler.ts           # Error handling + toasts
├── dist/
│   ├── index.js                  # Compiled bundle (~32 KB)
│   └── index.js.map              # Source map
├── package.json
├── tsconfig.json
└── vite.config.ts
```

### 1. index.ts

Entry point приложения.

```typescript
import { SysInfoWidget } from './SysInfoWidget';

document.addEventListener('DOMContentLoaded', () => {
    const widgets = document.querySelectorAll('.sysinfo-widget');
    widgets.forEach(widgetElement => {
        const widget = new SysInfoWidget(widgetElement.id);
        widget.initialize().catch(console.error);
    });
});
```

**Особенности:**
- Автоматическая инициализация при загрузке DOM
- Поддержка нескольких виджетов на странице
- Обработка ошибок инициализации

### 2. SysInfoWidget.ts

Главный класс виджета.

**Зависимости:**
```typescript
import { ApiService } from './ApiService';
import { MetricsRenderer } from './MetricsRenderer';
import { RealtimeUpdater } from './RealtimeUpdater';
import { ChartManager } from './ChartManager';
import { DockerLogsModal } from './DockerLogsModal';
import { SettingsManager } from './SettingsManager';
import { ErrorHandler } from './ErrorHandler';
```

**Поля класса:**
```typescript
private config: any;                          // Конфигурация из data-config
private apiService: ApiService;               // HTTP клиент
private metricsRenderer: MetricsRenderer;     // Рендерер
private realtimeUpdater: RealtimeUpdater;     // Таймер
private chartManager: ChartManager;           // Графики
private dockerLogsModal: DockerLogsModal;     // Модалка логов
private settingsManager: SettingsManager;     // Настройки
private errorHandler: ErrorHandler;           // Обработчик ошибок
private container: HTMLElement;               // DOM контейнер
```

**Lifecycle:**
```typescript
constructor(containerId: string) {
    // 1. Найти контейнер по ID
    // 2. Распарсить конфигурацию из data-config
    // 3. Инициализировать все компоненты
}

async initialize(): Promise<void> {
    // 1. Настроить обработчики событий
    // 2. Загрузить начальные метрики
    // 3. Запустить auto-refresh
}

destroy(): void {
    // 1. Остановить таймер
    // 2. Уничтожить графики
}
```

**Ключевые методы:**

**`setupEventHandlers()`**
Привязывает обработчики к кнопкам:
- Refresh - перезагрузка метрик
- Pause/Resume - управление auto-refresh
- Settings - открытие модалки настроек
- Export JSON/CSV - экспорт данных

**`loadInitialMetrics()`**
Загружает полные метрики через `apiService.getAllMetrics()`

**`updateRealtimeMetrics()`**
Загружает легковесные метрики через `apiService.getRealtimeMetrics()`

**`updateUI(metrics)`**
Обновляет DOM со всеми метриками:
```typescript
private updateUI(metrics: any): void {
    // Timestamp
    this.metricsRenderer.updateTimestamp(metrics.timestampFormatted);
    this.metricsRenderer.updateStatus(true);

    // Вкладки
    this.metricsRenderer.renderOverview(metrics);
    this.metricsRenderer.renderSystem(metrics);
    this.metricsRenderer.renderResources(metrics);
    this.metricsRenderer.renderServices(metrics);

    if (metrics.docker) {
        this.metricsRenderer.renderDocker(metrics.docker);
        this.attachDockerLogsHandlers();
    }

    // Графики Chart.js
    if (metrics.system?.cpuUsage && metrics.system?.memory) {
        const cpuPercent = this.chartManager.calculateCpuPercent(metrics.system.cpuUsage);
        const memPercent = metrics.system.memory.usedPercent || 0;
        this.chartManager.addDataPoint(cpuPercent, memPercent);
    }
}
```

### 3. ApiService.ts

HTTP клиент на базе Fetch API.

**Конфигурация:**
```typescript
interface Endpoints {
    metrics: string;        // '/info/backend/sys-info/api-metrics'
    realtime: string;       // '/info/backend/sys-info/api-realtime'
    dockerLogs: string;     // '/info/backend/sys-info/api-docker-logs'
    exportJson: string;     // '/info/backend/sys-info/export-json'
    exportCsv: string;      // '/info/backend/sys-info/export-csv'
}
```

**Методы:**

**`getAllMetrics(): Promise<any>`**
```typescript
const response = await fetch(this.endpoints.metrics, {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-CSRF-Token': this.csrfToken
    }
});
return await response.json();
```

**`getRealtimeMetrics(): Promise<any>`**
То же самое, но для realtime endpoint.

**`getDockerLogs(container: string, lines: number): Promise<any>`**
```typescript
const url = `${this.endpoints.dockerLogs}?container=${encodeURIComponent(container)}&lines=${lines}`;
```

**Обработка ошибок:**
```typescript
if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
}
```

### 4. MetricsRenderer.ts

Отвечает за рендеринг метрик в DOM.

**Ключевые методы:**

**`renderOverview(metrics)`**
Рендерит вкладку Overview - краткая сводка.

**Проверки:**
```typescript
// Статические данные (только при полной загрузке)
if (metrics.system?.available && metrics.system.server) {
    // Рендерить server info
}

// Динамические данные (обновляются всегда)
if (metrics.system?.available && metrics.system.memory) {
    // Рендерить memory с прогресс-баром
}
```

**`renderSystem(metrics)`**
Вкладка System - детальная информация о сервере и CPU.

**`renderResources(metrics)`**
Вкладка Resources - память, диск, Load Average, сеть.

**Особенности:**
```typescript
// Load Average - правильный ключ lowercase
if (system.loadavg) {  // НЕ loadAvg!
    const load = system.loadavg;
    // ...
}

// Network - это объект, НЕ массив
if (system.network && typeof system.network === 'object') {
    for (const [ifaceName, ifaceData] of Object.entries(system.network)) {
        // ...
    }
}
```

**`renderServices(metrics)`**
Вкладка Services - PHP, MySQL, Redis, Yii2.

**Проверка наличия полных данных:**
```typescript
// PHP - проверяем вложенные объекты
if (metrics.php?.available && metrics.php.version) {
    const php = metrics.php;
    // memory.limitFormatted (НЕ memoryLimit!)
    // limits.maxExecutionTime (НЕ maxExecutionTime!)
}

// Database
if (metrics.database?.available && metrics.database.driver) {
    // tables.count (НЕ tableCount!)
    // connections.current (НЕ connections напрямую!)
}

// Redis
if (metrics.redis?.available && metrics.redis.version) {
    // uptime.formatted (НЕ uptimeFormatted!)
    // clients.connected (НЕ clients напрямую!)
    // memory.usedFormatted
    // keyspace.totalKeys (НЕ keys!)
}

// Application
if (metrics.application?.available && metrics.application.yii) {
    // yii.version (НЕ yiiVersion!)
    // environment.environment (НЕ environment напрямую!)
    // environment.debug
    // cache.class (НЕ cacheClass!)
}
```

**Важно:** При realtime обновлении сервисы не имеют полных данных, поэтому метод пропускает рендеринг если данных нет (не очищает DOM).

**`renderDocker(docker)`**
Вкладка Docker - контейнеры с кнопками логов.

**`updateTimestamp(timestamp)`**
Обновляет timestamp последнего обновления.

**`updateStatus(success, message?)`**
Обновляет статус индикатор (зеленый/красный).

**Вспомогательные методы:**

**`renderHtml(elementId, html)`**
```typescript
private renderHtml(elementId: string, html: string): void {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = html;
    }
}
```

**`getProgressColor(percent)`**
```typescript
private getProgressColor(percent: number): string {
    if (percent >= 90) return 'bg-danger';
    if (percent >= 75) return 'bg-warning';
    if (percent >= 50) return 'bg-info';
    return 'bg-success';
}
```

### 5. ChartManager.ts

Управление графиками Chart.js.

**Создание графиков:**
```typescript
constructor() {
    this.createCpuChart();
    this.createMemoryChart();
}

private createCpuChart(): void {
    const ctx = document.getElementById('chart-cpu') as HTMLCanvasElement;
    if (!ctx) return;

    if (typeof (window as any).Chart === 'undefined') {
        console.error('[ChartManager] Chart.js is not loaded');
        return;
    }

    const Chart = (window as any).Chart;
    this.cpuChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'CPU %',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value: any) => value + '%'
                    }
                }
            }
        }
    });
}
```

**Добавление точек данных:**
```typescript
public addDataPoint(cpu: number, memory: number): void {
    const timestamp = new Date().toLocaleTimeString();

    // CPU
    if (this.cpuChart) {
        this.cpuChart.data.labels.push(timestamp);
        this.cpuChart.data.datasets[0].data.push(cpu);

        // Ограничение 60 точек (3 минуты при интервале 3 секунды)
        if (this.cpuChart.data.labels.length > this.maxDataPoints) {
            this.cpuChart.data.labels.shift();
            this.cpuChart.data.datasets[0].data.shift();
        }

        this.cpuChart.update('none'); // 'none' - без анимации
    }

    // Memory (аналогично)
}
```

**Расчет CPU процента:**
```typescript
public calculateCpuPercent(cpuUsage: any): number {
    const total = cpuUsage.user + cpuUsage.nice + cpuUsage.system +
                  cpuUsage.idle + cpuUsage.iowait + cpuUsage.irq +
                  cpuUsage.softirq;
    const used = total - cpuUsage.idle;
    return total > 0 ? Math.round((used / total) * 100) : 0;
}
```

### 6. RealtimeUpdater.ts

Таймер для auto-refresh.

```typescript
export class RealtimeUpdater {
    private intervalId: number | null = null;
    private interval: number;
    private callback: () => void;
    private active: boolean = false;

    constructor(callback: () => void, interval: number) {
        this.callback = callback;
        this.interval = interval;
    }

    public start(): void {
        if (this.active) return;

        this.active = true;
        this.intervalId = window.setInterval(() => {
            this.callback();
        }, this.interval);

        console.log('[RealtimeUpdater] Started');
    }

    public stop(): void {
        if (this.intervalId !== null) {
            window.clearInterval(this.intervalId);
            this.intervalId = null;
        }
        this.active = false;
        console.log('[RealtimeUpdater] Stopped');
    }

    public toggle(): boolean {
        if (this.active) {
            this.stop();
        } else {
            this.start();
        }
        return this.active;
    }

    public setInterval(newInterval: number): void {
        this.interval = newInterval;
        if (this.active) {
            this.stop();
            this.start();
        }
    }

    public isActive(): boolean {
        return this.active;
    }
}
```

### 7. DockerLogsModal.ts

Модальное окно для просмотра логов Docker.

```typescript
export class DockerLogsModal {
    private apiService: ApiService;
    private errorHandler: ErrorHandler;

    constructor(apiService: ApiService, errorHandler: ErrorHandler) {
        this.apiService = apiService;
        this.errorHandler = errorHandler;
    }

    public async show(container: string, lines: number = 100): Promise<void> {
        const modalElement = document.getElementById('dockerLogsModal');
        if (!modalElement) return;

        if (typeof (window as any).bootstrap === 'undefined') {
            this.errorHandler.handle('Bootstrap Modal is not available');
            return;
        }

        // Обновить заголовок
        const title = modalElement.querySelector('.modal-title');
        if (title) {
            title.textContent = `Docker Logs: ${container}`;
        }

        // Показать loading
        const body = modalElement.querySelector('.modal-body');
        if (body) {
            body.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
        }

        // Показать модалку
        const modal = new (window as any).bootstrap.Modal(modalElement);
        modal.show();

        // Загрузить логи
        try {
            const response = await this.apiService.getDockerLogs(container, lines);

            if (response.success && body) {
                body.innerHTML = `<pre class="mb-0">${this.escapeHtml(response.logs)}</pre>`;
            } else {
                throw new Error(response.message || 'Failed to load logs');
            }
        } catch (error) {
            if (body) {
                body.innerHTML = `<div class="alert alert-danger">Error: ${(error as Error).message}</div>`;
            }
        }
    }

    private escapeHtml(text: string): string {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
```

### 8. SettingsManager.ts

Управление пользовательскими настройками через localStorage.

```typescript
interface Settings {
    updateInterval: number;  // Интервал обновления (мс)
    autoRefresh: boolean;    // Включен ли auto-refresh
}

export class SettingsManager {
    private storageKey: string = 'sysinfo_settings';
    private settings: Settings;

    constructor(defaults: Settings) {
        // Загрузить из localStorage или использовать defaults
        const stored = localStorage.getItem(this.storageKey);
        if (stored) {
            this.settings = JSON.parse(stored);
        } else {
            this.settings = defaults;
            this.save();
        }
    }

    public get(): Settings {
        return { ...this.settings };
    }

    public setUpdateInterval(interval: number): void {
        this.settings.updateInterval = interval;
        this.save();
    }

    public setAutoRefresh(enabled: boolean): void {
        this.settings.autoRefresh = enabled;
        this.save();
    }

    public getUpdateInterval(): number {
        return this.settings.updateInterval;
    }

    public isAutoRefreshEnabled(): boolean {
        return this.settings.autoRefresh;
    }

    private save(): void {
        localStorage.setItem(this.storageKey, JSON.stringify(this.settings));
    }
}
```

### 9. ErrorHandler.ts

Централизованная обработка ошибок.

```typescript
export class ErrorHandler {
    public handle(error: Error | string, context?: string): void {
        const message = error instanceof Error ? error.message : error;
        const fullMessage = context ? `${context}: ${message}` : message;

        console.error('[ErrorHandler]', fullMessage);
        this.showToast('danger', fullMessage);
    }

    public success(message: string): void {
        this.showToast('success', message);
    }

    public info(message: string): void {
        this.showToast('info', message);
    }

    private showToast(type: string, message: string): void {
        // Можно использовать Bootstrap Toast или простой alert
        // Пока просто логируем
        console.log(`[${type.toUpperCase()}]`, message);
    }
}
```

### Build Configuration

#### package.json
```json
{
  "name": "sysinfo-widget",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "tsc && vite build",
    "preview": "vite preview"
  },
  "devDependencies": {
    "typescript": "^5.3.3",
    "vite": "^5.0.10"
  },
  "dependencies": {
    "chart.js": "^4.4.0"
  }
}
```

#### tsconfig.json
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "lib": ["ES2020", "DOM"],
    "moduleResolution": "bundler",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "outDir": "./dist"
  },
  "include": ["src/**/*"]
}
```

#### vite.config.ts
```typescript
import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    lib: {
      entry: 'src/index.ts',
      name: 'SysInfoWidget',
      formats: ['es'],
      fileName: 'index'
    },
    rollupOptions: {
      external: [],
      output: {
        globals: {}
      }
    },
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: true
  }
});
```

---

## API Endpoints

### Полный список

| Метод | URL | Параметры | Описание | Размер ответа |
|-------|-----|-----------|----------|---------------|
| GET | `/info/backend/sys-info/index` | - | Главная страница | HTML |
| GET | `/info/backend/sys-info/api-metrics` | - | Полные метрики | ~2.5 KB JSON |
| GET | `/info/backend/sys-info/api-realtime` | - | Realtime метрики | ~500 B JSON |
| GET | `/info/backend/sys-info/api-docker-logs` | `container`, `lines` | Логи контейнера | Variable JSON |
| GET | `/info/backend/sys-info/export-json` | - | Экспорт JSON | File download |
| GET | `/info/backend/sys-info/export-csv` | - | Экспорт CSV | File download |

### Детали

#### GET /api-metrics

**Response:**
```json
{
    "timestamp": 1737249600,
    "timestampFormatted": "2026-01-19 01:00:00",
    "system": {
        "available": true,
        "server": {...},
        "cpu": {...},
        "cpuUsage": {...},
        "memory": {...},
        "disk": {...},
        "loadavg": {...},
        "network": {...},
        "uptime": {...},
        "temperature": {...}
    },
    "php": {
        "available": true,
        "version": "8.4.0",
        "memory": {...},
        "limits": {...},
        "opcache": {...},
        "extensions": [...]
    },
    "docker": {
        "available": true,
        "version": "24.0.7",
        "containers": [...],
        "images": 15,
        "volumes": 4,
        "networks": 3
    },
    "database": {
        "available": true,
        "driver": "mysql",
        "version": "8.0.35",
        "size": {...},
        "tables": {...},
        "connections": {...}
    },
    "redis": {
        "available": true,
        "version": "7.2.3",
        "uptime": {...},
        "clients": {...},
        "memory": {...},
        "stats": {...},
        "keyspace": {...}
    },
    "application": {
        "available": true,
        "yii": {...},
        "environment": {...},
        "cache": {...},
        "session": {...},
        "queue": {...}
    }
}
```

#### GET /api-realtime

**Response:**
```json
{
    "timestamp": 1737249603,
    "timestampFormatted": "2026-01-19 01:00:03",
    "system": {
        "available": true,
        "cpuUsage": {...},
        "memory": {...},
        "disk": {...},
        "loadavg": {...},
        "temperature": {...}
    },
    "docker": {
        "available": true,
        "containers": [...]
    },
    "database": {
        "available": true,
        "connections": {...}
    }
}
```

**Отличия:**
- Не содержит статических данных (версии, конфигурацию)
- ~5x меньше размер
- Вызывается каждые 3 секунды

#### GET /api-docker-logs

**Query параметры:**
- `container` (string, required) - ID или имя контейнера
- `lines` (int, optional) - количество строк (default: 100, max: 1000)

**Response:**
```json
{
    "success": true,
    "logs": "[2026-01-19 01:00:00] app.INFO: Message...\n[2026-01-19 01:00:01] ...",
    "lines": 100
}
```

**Error response:**
```json
{
    "success": false,
    "message": "Container not found"
}
```

---

## Конфигурация

### Widget Configuration

Виджет конфигурируется через атрибут `data-config`:

```php
// В view: /app/Besnovatyj/Info/widgets/sysinfo/views/index.php
<div id="<?= Html::encode($widgetId) ?>"
     class="sysinfo-widget"
     data-config='<?= Json::htmlEncode($config) ?>'>
```

**Структура конфигурации:**
```php
$config = [
    'updateInterval' => 3000,  // Интервал обновления (мс)
    'autoRefresh' => true,     // Включить auto-refresh
    'csrfToken' => Yii::$app->request->csrfToken,
    'endpoints' => [
        'metrics' => Url::to(['/info/backend/sys-info/api-metrics']),
        'realtime' => Url::to(['/info/backend/sys-info/api-realtime']),
        'dockerLogs' => Url::to(['/info/backend/sys-info/api-docker-logs']),
        'exportJson' => Url::to(['/info/backend/sys-info/export-json']),
        'exportCsv' => Url::to(['/info/backend/sys-info/export-csv']),
    ],
];
```

### Asset Bundle

**SysInfoAsset.php:**
```php
class SysInfoAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/media';

    public $css = [
        'css/sysinfo.css',
    ];

    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        'js/dist/index.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];

    public $jsOptions = [
        'type' => 'module', // ES6 модули
    ];
}
```

**Порядок загрузки:**
1. Yii2 core JS
2. Bootstrap 5 CSS + JS
3. Chart.js (CDN)
4. Собственный JS bundle (ES6 module)

---

## Метрики

### System Metrics

**CPU:**
- Model name
- Cores count
- Frequency (MHz/GHz)
- Cache size
- BogoMIPS
- Current usage (user, system, idle, iowait, etc.)

**Memory:**
- Total/Used/Free (bytes + formatted)
- Usage percentage
- Buffers/Cached
- Available memory

**Disk:**
- Total/Used/Free space
- Usage percentage
- Mount point
- Filesystem

**Load Average:**
- 1/5/15 minutes

**Network:**
- Per interface RX/TX bytes
- Formatted sizes

**Temperature:**
- CPU temperature (Celsius)

**Uptime:**
- Seconds + formatted string

### PHP Metrics

- Version, SAPI, OS, Architecture
- Current/Peak memory usage
- Memory limit + usage %
- max_execution_time, post_max_size, etc.
- OPcache status (enabled, memory, hit rate)
- Loaded extensions list

### Docker Metrics

- Docker version
- Containers list (ID, name, state, status, image)
- Total images count
- Total volumes count
- Total networks count
- Container logs (via modal)

### Database Metrics

- Driver (mysql/pgsql)
- Version
- Database name + charset
- Total database size
- Tables count + total rows
- Data/Index sizes
- Connection stats (current/running/cached/max)

### Redis Metrics

- Version
- Uptime
- Connected/Blocked clients
- Memory usage (used/peak/max/fragmentation)
- Hit rate
- Total keys + per-database breakdown

### Application Metrics

- Yii version, app name/ID
- Environment (dev/prod)
- Debug mode
- Timezone, Language
- Cache class + config
- Session handler
- Queue driver

---

## Безопасность

### Backend Security

**1. CSRF Protection**
Все POST запросы проверяют CSRF токен:
```php
// В контроллере
Yii::$app->request->csrfToken;

// В JS
headers: {
    'X-CSRF-Token': this.csrfToken
}
```

**2. Access Control**
Контроллер защищен фильтром доступа:
```php
public function behaviors()
{
    return [
        'access' => [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'], // Только авторизованные
                ],
            ],
        ],
    ];
}
```

**3. Docker Security**

Используется **socket-proxy** вместо прямого доступа к Docker socket:

```yaml
# docker-compose.yml
socket-proxy:
    environment:
      CONTAINERS: 1     # Разрешено чтение контейнеров
      IMAGES: 1         # Разрешено чтение образов
      VOLUMES: 1        # Разрешено чтение томов
      NETWORKS: 1       # Разрешено чтение сетей
      POST: 0           # ЗАПРЕЩЕНО создание/изменение
      DELETE: 0         # ЗАПРЕЩЕНО удаление
```

**Безопасность:**
- ✅ Только READ операции
- ✅ Нет прямого доступа к /var/run/docker.sock
- ✅ Изолированная сеть socket-proxy
- ✅ Timeout на команды (5 секунд)

**4. Shell Command Execution**

Все shell команды выполняются безопасно:
```php
protected function safeShellExec(string $command, int $timeout = 5): ?string
{
    if (!function_exists('shell_exec')) {
        return null;
    }

    $fullCommand = sprintf(
        'timeout %d %s 2>&1',
        $timeout,
        $command  // БЕЗ пользовательского ввода!
    );

    $output = @shell_exec($fullCommand);
    return $output !== null ? trim($output) : null;
}
```

**Безопасность:**
- ✅ Встроенный timeout
- ✅ Нет использования пользовательского ввода в командах
- ✅ Обработка ошибок через try/catch
- ✅ Suppress warnings с @

**5. SQL Injection Protection**

Все SQL запросы используют параметризованные запросы:
```php
$sql = "SELECT SUM(data_length + index_length)
        FROM information_schema.TABLES
        WHERE table_schema = :dbName";

$size = Yii::$app->db->createCommand($sql, [
    ':dbName' => $dbName  // Параметр, НЕ конкатенация!
])->queryScalar();
```

**6. XSS Protection**

Frontend использует безопасные методы:
```typescript
// Экранирование HTML
private escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;  // НЕ innerHTML!
    return div.innerHTML;
}

// Использование
body.innerHTML = `<pre>${this.escapeHtml(logs)}</pre>`;
```

### Frontend Security

**1. Content Security Policy**

Рекомендуется настроить CSP headers:
```
Content-Security-Policy:
    default-src 'self';
    script-src 'self' https://cdn.jsdelivr.net;
    style-src 'self' 'unsafe-inline';
```

**2. HTTPS Only**

Все запросы должны идти через HTTPS в production.

**3. localStorage Security**

Settings хранятся в localStorage:
- ✅ Нет чувствительных данных
- ✅ Только updateInterval и autoRefresh
- ✅ Client-side only

---

## Производительность

### Backend Optimization

**1. No Caching Strategy**

Метрики НЕ кэшируются намеренно:
- Данные всегда актуальные
- Realtime мониторинг
- Провайдеры stateless

**2. Lightweight Realtime Endpoint**

`api-realtime` возвращает только динамические данные:
- ~500 байт вместо ~2.5 Кб
- Экономия трафика: 80%
- Быстрее парсинг JSON

**3. Efficient Data Collection**

Провайдеры используют оптимизированные методы:
```php
// Читаем файл один раз
$lines = file('/proc/stat');

// Парсим все метрики из одного массива
$cpuInfo = $this->parseCpuInfo($lines);
$cpuUsage = $this->parseCpuUsage($lines);
```

**4. Shell Command Timeout**

Все команды имеют timeout 5 секунд:
```bash
timeout 5 docker ps -a
```

**5. Database Query Optimization**

Используем агрегирующие запросы:
```sql
-- Вместо N запросов - один
SELECT COUNT(*) as count,
       SUM(table_rows) as total_rows,
       SUM(data_length) as data_size,
       SUM(index_length) as index_size
FROM information_schema.TABLES
WHERE table_schema = :dbName
```

### Frontend Optimization

**1. Chart.js Data Limiting**

Графики хранят максимум 60 точек:
```typescript
if (this.cpuChart.data.labels.length > 60) {
    this.cpuChart.data.labels.shift();
    this.cpuChart.data.datasets[0].data.shift();
}
```

60 точек × 3 секунды = 3 минуты истории

**2. Chart Update Without Animation**

```typescript
this.cpuChart.update('none');  // Без анимации для performance
```

**3. Debouncing Auto-refresh**

Realtime updater использует интервал 3 секунды:
- Не перегружает сервер
- Достаточно для мониторинга
- Настраивается пользователем

**4. Lazy Loading Docker Logs**

Логи загружаются только при открытии модалки:
```typescript
modal.show();  // Сначала показываем модалку
// Потом загружаем логи асинхронно
await this.loadLogs();
```

**5. ES6 Modules**

TypeScript компилируется в ES6 модули:
- Современный синтаксис
- Tree-shaking в Vite
- Меньший размер bundle (~32 KB)

**6. CDN для Chart.js**

Chart.js загружается с CDN:
- Параллельная загрузка
- Кэширование браузером
- Уменьшает размер собственного bundle

### Build Optimization

**Vite Production Build:**
```bash
npm run build
```

**Результат:**
```
dist/index.js  32.26 kB │ gzip: 7.61 kB │ map: 71.62 kB
✓ built in 339ms
```

**Оптимизации:**
- Minification
- Tree-shaking
- Source maps для отладки
- ES6 модули

---

## Troubleshooting

### Общие проблемы

#### 1. "Array to string conversion"

**Симптом:** Ошибка в `/app/Besnovatyj/Info/widgets/sysinfo/views/index.php:13`

**Причина:** Попытка вывести массив как строку в HTML атрибуте

**Решение:**
```php
// НЕПРАВИЛЬНО
<div data-config='<?= $config ?>'>

// ПРАВИЛЬНО
use yii\helpers\Json;
<div data-config='<?= Json::htmlEncode($config) ?>'>
```

#### 2. "TypeError: can't access property 'hostname', e is undefined"

**Симптом:** Ошибка в MetricsRenderer.ts:40 при realtime обновлении

**Причина:** Realtime endpoint не возвращает статические данные (server, cpu info)

**Решение:**
```typescript
// НЕПРАВИЛЬНО
if (metrics.system?.available) {
    const server = metrics.system.server;  // undefined при realtime!
}

// ПРАВИЛЬНО
if (metrics.system?.available && metrics.system.server) {
    const server = metrics.system.server;
}
```

#### 3. "Chart.js is not loaded"

**Симптом:** Предупреждение в консоли, графики не отображаются

**Причина:** Chart.js не подключен в Assets

**Решение:**
```php
// SysInfoAsset.php
public $js = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
    'js/dist/index.js',
];
```

#### 4. "Docker недоступен"

**Симптом:** Вкладка Docker показывает "Docker недоступен"

**Причина:** Docker CLI не установлен или нет доступа к socket-proxy

**Решение:** См. `DOCKER-SETUP.md`

#### 5. "[object Object]" в данных сервисов

**Симптом:** Вместо значений показывается "[object Object]"

**Причина:** Неправильный путь к вложенным свойствам

**Решение:**
```typescript
// НЕПРАВИЛЬНО
php.memoryLimit         // Это объект!
database.connections    // Это объект!

// ПРАВИЛЬНО
php.memory.limitFormatted
database.connections.current
```

#### 6. TypeScript compilation errors

**Симптом:**
```
error TS7006: Parameter 'value' implicitly has an 'any' type
error TS2339: Property 'error' does not exist on type 'ErrorHandler'
```

**Решение:**
```typescript
// Добавить явный тип
callback: (value: any) => value + '%'

// Использовать правильный метод
this.errorHandler.handle('message');  // НЕ .error()
```

#### 7. Services данные исчезают при auto-refresh

**Симптом:** При автообновлении пропадает информация о PHP, MySQL, Redis

**Причина:** Realtime endpoint не возвращает полные данные сервисов

**Решение:** Методы рендеринга должны проверять наличие данных:
```typescript
if (metrics.php?.available && metrics.php.version) {
    // Рендерить
}
// Если данных нет - НЕ очищать DOM (пропустить рендеринг)
```

### Debug Mode

Включите логирование в консоль:

```typescript
console.log('[SysInfoWidget] Initialized', this.config);
console.log('[RealtimeUpdater] Started');
console.log('[ChartManager] Chart.js version:', Chart.version);
```

Все компоненты логируют свои действия с префиксом `[ComponentName]`.

### Network Tab

Проверьте запросы в DevTools → Network:

**Успешный запрос:**
```
GET /info/backend/sys-info/api-realtime
Status: 200 OK
Size: 814 B
Time: 1.1s
```

**Неуспешный запрос:**
```
Status: 500 Internal Server Error
Response: {"message": "...", "file": "...", "line": ...}
```

### PHP Logs

Проверьте логи приложения:
```bash
tail -f /app/runtime/logs/app.log
```

Все ошибки провайдеров логируются через Yii::error().

---

## Заключение

Виджет системной информации - это полноценное решение для мониторинга сервера с современной архитектурой:

✅ **Clean Architecture** - четкое разделение слоев
✅ **Dependency Injection** - все зависимости через DI контейнер
✅ **TypeScript + Vite** - современный frontend без jQuery
✅ **Chart.js** - красивые графики в реальном времени
✅ **Docker Security** - безопасный доступ через socket-proxy
✅ **No Caching** - всегда актуальные данные
✅ **Responsive UI** - Bootstrap 5 с адаптивным дизайном
✅ **Extensible** - легко добавить новые провайдеры метрик

**Производительность:**
- Backend: ~1 секунда на полные метрики
- Frontend: ~32 KB bundle (gzip: 7.6 KB)
- Realtime: обновление каждые 3 секунды

**Безопасность:**
- CSRF protection
- Access control
- Socket-proxy для Docker
- Parametrized SQL queries
- XSS protection
- Command timeouts

Проект готов к использованию в production (кроме Docker метрик на dev-only серверах).
