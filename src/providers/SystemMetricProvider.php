<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

/**
 * Провайдер системных метрик (CPU, Memory, Disk, Network, etc.)
 * Собирает информацию из Linux /proc filesystem
 */
class SystemMetricProvider extends BaseMetricProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'System';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAvailability(): bool
    {
        return PHP_OS_FAMILY === 'Linux'
            && is_readable('/proc/stat')
            && is_readable('/proc/meminfo');
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMetrics(): array
    {
        return [
            'server' => $this->getServerInfo(),
            'cpu' => $this->getCpuInfo(),
            'cpuUsage' => $this->getCpuUsage(),
            'temperature' => $this->getTempInfo(),
            'memory' => $this->getMemInfo(),
            'disk' => $this->getDiskInfo(),
            'loadavg' => $this->getLoadAvg(),
            'network' => $this->getNetDev(),
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * Получает информацию о сервере
     *
     * @return array
     */
    private function getServerInfo(): array
    {
        return [
            'hostname' => php_uname('n'),
            'serverAddr' => $this->getServerAddr(),
            'remoteAddr' => $this->getRemoteAddr(),
            'os' => $this->getDistName(),
            'kernel' => php_uname('r'),
            'architecture' => php_uname('m'),
        ];
    }

    /**
     * Получает IP адрес сервера
     *
     * @return string
     */
    private function getServerAddr(): string
    {
        if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            return $_SERVER['SERVER_ADDR'];
        }

        return gethostbyname(php_uname('n'));
    }

    /**
     * Получает IP адрес клиента
     *
     * @return string
     */
    private function getRemoteAddr(): string
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return preg_replace('/^.+,\s*/', '', $_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Получает название дистрибутива Linux
     *
     * @return string
     */
    private function getDistName(): string
    {
        foreach (glob('/etc/*release') as $name) {
            if (in_array($name, ['/etc/centos-release', '/etc/redhat-release', '/etc/system-release'])) {
                $content = $this->safeFileReadLines($name);
                return $content ? trim($content[0]) : 'Unknown Linux';
            }

            // parse_ini_file может быть в disable_functions (hardening FPM): @ не гасит
            // fatal Error от отключённой функции, поэтому проверяем доступность явно —
            // иначе весь блок system-метрик падает. Нет функции → фоллбэк на php_uname() ниже.
            $releaseInfo = function_exists('parse_ini_file') ? @\parse_ini_file($name) : false;

            if (isset($releaseInfo['DISTRIB_DESCRIPTION'])) {
                return $releaseInfo['DISTRIB_DESCRIPTION'];
            }

            if (isset($releaseInfo['PRETTY_NAME'])) {
                return $releaseInfo['PRETTY_NAME'];
            }
        }

        return php_uname('s') . ' ' . php_uname('r');
    }

    /**
     * Получает информацию о CPU
     *
     * @return array
     */
    private function getCpuInfo(): array
    {
        $content = $this->safeFileRead('/proc/cpuinfo');
        if (!$content) {
            return [];
        }

        $info = [];

        @preg_match_all("/processor\s{0,}:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $content, $processor);
        @preg_match_all("/model\s+name\s{0,}:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $content, $model);

        if (empty($model[0])) {
            @preg_match_all("/Hardware\s{0,}:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $content, $model);
        }

        @preg_match_all("/cpu\s+MHz\s{0,}:+\s{0,}([\d.]+)[\r\n]+/", $content, $mhz);

        if (empty($mhz[0])) {
            $freqFile = $this->safeFileRead('/sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_max_freq');
            if ($freqFile) {
                $mhz = ['', [sprintf('%.3f', intval(trim($freqFile)) / 1000)]];
            }
        }

        @preg_match_all("/cache\s+size\s{0,}:+\s{0,}([\d.]+\s{0,}[A-Z]+[\r\n]+)/", $content, $cache);
        @preg_match_all("/(?i)bogomips\s{0,}:+\s{0,}([\d.]+)[\r\n]+/", $content, $bogomips);

        if (is_array($model[1] ?? null) && !empty($model[1])) {
            $info['cores'] = count($processor[1]);
            $info['model'] = trim($model[1][0]);
            $info['frequency'] = isset($mhz[1][0]) ? trim($mhz[1][0]) . ' MHz' : 'N/A';
            $info['bogomips'] = isset($bogomips[1][0]) ? trim($bogomips[1][0]) : 'N/A';
            if (!empty($cache[0])) {
                $info['cache'] = trim($cache[1][0]);
            }
        }

        return $info;
    }

    /**
     * Получает текущую загрузку CPU
     *
     * @return array
     */
    private function getCpuUsage(): array
    {
        $lines = $this->safeFileReadLines('/proc/stat');
        if (!$lines) {
            return [];
        }

        $firstLine = trim(array_shift($lines));
        $values = preg_split('/\s+/', $firstLine);
        $values = array_slice($values, 1);

        if (count($values) < 7) {
            return [];
        }

        return [
            'user' => (int)($values[0] ?? 0),
            'nice' => (int)($values[1] ?? 0),
            'system' => (int)($values[2] ?? 0),
            'idle' => (int)($values[3] ?? 0),
            'iowait' => (int)($values[4] ?? 0),
            'irq' => (int)($values[5] ?? 0),
            'softirq' => (int)($values[6] ?? 0),
            'steal' => (int)($values[7] ?? 0),
        ];
    }

    /**
     * Получает температуру CPU и GPU
     *
     * @return array
     */
    private function getTempInfo(): array
    {
        $info = ['cpu' => null, 'gpu' => null];

        $cpuTemp = $this->safeFileRead('/sys/class/thermal/thermal_zone0/temp');
        if ($cpuTemp) {
            $info['cpu'] = round(doubleval($cpuTemp) / 1000.0, 1);
        }

        $gpuTemp = $this->safeFileRead('/sys/class/thermal/thermal_zone10/temp');
        if ($gpuTemp) {
            $info['gpu'] = round(doubleval($gpuTemp) / 1000.0, 1);
        }

        return $info;
    }

    /**
     * Получает информацию о памяти
     *
     * @return array
     */
    private function getMemInfo(): array
    {
        $content = $this->safeFileRead('/proc/meminfo');
        if (!$content) {
            return [];
        }

        preg_match_all("/MemTotal\s{0,}:+\s{0,}([\d.]+).+?MemFree\s{0,}:+\s{0,}([\d.]+).+?Cached\s{0,}:+\s{0,}([\d.]+).+?SwapTotal\s{0,}:+\s{0,}([\d.]+).+?SwapFree\s{0,}:+\s{0,}([\d.]+)/s", $content, $buf);
        preg_match_all("/Buffers\s{0,}:+\s{0,}([\d.]+)/s", $content, $buffers);

        if (empty($buf[1])) {
            return [];
        }

        $info = [];
        $info['total'] = round($buf[1][0] / 1024, 2);
        $info['free'] = round($buf[2][0] / 1024, 2);
        $info['buffers'] = round(($buffers[1][0] ?? 0) / 1024, 2);
        $info['cached'] = round($buf[3][0] / 1024, 2);
        $info['used'] = round($info['total'] - $info['free'] - $info['buffers'] - $info['cached'], 2);
        $info['usedPercent'] = ($info['total'] != 0) ? round($info['used'] / $info['total'] * 100, 2) : 0;

        $info['swap'] = [
            'total' => round($buf[4][0] / 1024, 2),
            'free' => round($buf[5][0] / 1024, 2),
        ];
        $info['swap']['used'] = round($info['swap']['total'] - $info['swap']['free'], 2);
        $info['swap']['percent'] = ($info['swap']['total'] != 0) ? round($info['swap']['used'] / $info['swap']['total'] * 100, 2) : 0;

        // Форматирование размеров
        foreach (['total', 'free', 'buffers', 'cached', 'used'] as $key) {
            $info[$key . 'Formatted'] = $this->formatMemorySize($info[$key]);
        }

        foreach (['total', 'free', 'used'] as $key) {
            $info['swap'][$key . 'Formatted'] = $this->formatMemorySize($info['swap'][$key]);
        }

        return $info;
    }

    /**
     * Форматирует размер памяти (в MB) в человекочитаемый формат
     *
     * @param float $mb Размер в MB
     * @return string
     */
    private function formatMemorySize(float $mb): string
    {
        if ($mb < 1024) {
            return round($mb, 2) . ' MB';
        }

        return round($mb / 1024, 2) . ' GB';
    }

    /**
     * Получает информацию о диске
     *
     * @return array
     */
    private function getDiskInfo(): array
    {
        $total = @disk_total_space('.');
        $free = @disk_free_space('.');

        if ($total === false || $free === false) {
            return [];
        }

        $totalGb = round($total / (1024 * 1024 * 1024), 2);
        $freeGb = round($free / (1024 * 1024 * 1024), 2);
        $usedGb = round($totalGb - $freeGb, 2);

        return [
            'total' => $totalGb,
            'free' => $freeGb,
            'used' => $usedGb,
            'percent' => ($totalGb != 0) ? round($usedGb / $totalGb * 100, 2) : 0,
            'totalFormatted' => $totalGb . ' GB',
            'freeFormatted' => $freeGb . ' GB',
            'usedFormatted' => $usedGb . ' GB',
        ];
    }

    /**
     * Получает Load Average
     *
     * @return array
     */
    private function getLoadAvg(): array
    {
        $lines = $this->safeFileReadLines('/proc/loadavg');
        if (!$lines) {
            return [];
        }

        $values = explode(' ', trim($lines[0]));

        return [
            '1min' => (float)($values[0] ?? 0),
            '5min' => (float)($values[1] ?? 0),
            '15min' => (float)($values[2] ?? 0),
            'formatted' => implode(' ', array_slice($values, 0, 3)),
        ];
    }

    /**
     * Получает информацию о сетевых интерфейсах
     *
     * @return array
     */
    private function getNetDev(): array
    {
        $lines = $this->safeFileReadLines('/proc/net/dev');
        if (!$lines || count($lines) < 3) {
            return [];
        }

        $info = [];
        for ($i = 2; $i < count($lines); $i++) {
            $parts = preg_split('/\s+/', trim($lines[$i]));
            if (count($parts) < 10) {
                continue;
            }

            $dev = trim($parts[0], ':');
            $rx = (int)$parts[1];
            $tx = (int)$parts[9];

            $info[$dev] = [
                'rx' => $rx,
                'tx' => $tx,
                'rxFormatted' => $this->formatBytes($rx),
                'txFormatted' => $this->formatBytes($tx),
            ];
        }

        return $info;
    }

    /**
     * Получает время работы системы (uptime)
     *
     * @return array
     */
    private function getUptime(): array
    {
        $lines = $this->safeFileReadLines('/proc/uptime');
        if (!$lines) {
            return [];
        }

        $values = explode(' ', trim($lines[0]));
        $seconds = (int)floor((float)$values[0]);

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return [
            'seconds' => $seconds,
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'formatted' => $this->formatUptime($seconds),
        ];
    }
}
