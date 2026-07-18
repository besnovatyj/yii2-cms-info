<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

/**
 * Провайдер метрик веб-сервера Nginx.
 *
 * Основной источник данных — суперглобальный $_SERVER, который PHP-FPM получает
 * от nginx через fastcgi_params: стоковый файл передаёт
 * `SERVER_SOFTWARE nginx/$nginx_version` и `SERVER_PROTOCOL`. Это работает без
 * shell-вызовов и под FPM-hardening (disable_functions), в отличие от `nginx -v`,
 * который используется лишь как резерв на bare-metal окружении.
 *
 * В dev (nginx в отдельном Docker-контейнере) бинаря nginx в php-контейнере нет —
 * версия всё равно определяется по SERVER_SOFTWARE, а резервный вызов деградирует
 * в null, не роняя блок метрик.
 */
class NginxMetricProvider extends BaseMetricProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Nginx';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'services';
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAvailability(): bool
    {
        // Веб-сервер сам представился через SERVER_SOFTWARE (основной путь).
        if (stripos($this->serverSoftware(), 'nginx') !== false) {
            return true;
        }

        // Резерв: bare-metal, где доступен бинарь nginx (если shell не отключён).
        return $this->versionFromBinary() !== null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUnavailableMessage(): string
    {
        return 'Nginx не обнаружен (запрос обслужен не через nginx либо SERVER_SOFTWARE скрыт)';
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMetrics(): array
    {
        $software = $this->serverSoftware();
        $protocol = (string)($_SERVER['SERVER_PROTOCOL'] ?? '');

        return [
            'version' => $this->detectVersion($software),
            'serverSoftware' => $software !== '' ? $software : 'N/A',
            'protocol' => $protocol !== '' ? $protocol : 'N/A',
            'http2' => stripos($protocol, 'HTTP/2') !== false,
            'http3' => stripos($protocol, 'HTTP/3') !== false,
            'https' => $this->isHttps(),
            'scheme' => (string)($_SERVER['REQUEST_SCHEME'] ?? ($this->isHttps() ? 'https' : 'http')),
            'gateway' => (string)($_SERVER['GATEWAY_INTERFACE'] ?? 'N/A'),
            'tls' => $this->getTlsInfo(),
            'build' => $this->getBuildInfo(),
        ];
    }

    /**
     * Сырое значение SERVER_SOFTWARE (например, "nginx/1.24.0").
     *
     * @return string
     */
    private function serverSoftware(): string
    {
        return (string)($_SERVER['SERVER_SOFTWARE'] ?? '');
    }

    /**
     * Определяет версию nginx: сначала из SERVER_SOFTWARE, затем из бинаря.
     *
     * @param string $software Значение SERVER_SOFTWARE
     * @return string Версия вида "1.24.0" или "Unknown"
     */
    private function detectVersion(string $software): string
    {
        if (preg_match('~nginx/([\d.]+)~i', $software, $m) === 1) {
            return $m[1];
        }

        return $this->versionFromBinary() ?? 'Unknown';
    }

    /**
     * Резервное определение версии через `nginx -v`.
     *
     * nginx печатает версию в stderr, поэтому нужен 2>&1 (собственный вызов
     * вместо BaseMetricProvider::safeShellExec, который глушит stderr в /dev/null).
     * Если shell_exec в disable_functions или бинаря нет — возвращает null.
     *
     * @return string|null
     */
    private function versionFromBinary(): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        $output = @shell_exec('timeout 3 nginx -v 2>&1');
        if ($output === null || $output === '') {
            return null;
        }

        return preg_match('~nginx/([\d.]+)~i', $output, $m) === 1 ? $m[1] : null;
    }

    /**
     * Определяет, обслужен ли текущий запрос по HTTPS.
     *
     * @return bool
     */
    private function isHttps(): bool
    {
        $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        if (($_SERVER['REQUEST_SCHEME'] ?? '') === 'https') {
            return true;
        }

        if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }

        return (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /**
     * Живые параметры TLS текущего соединения (протокол/шифр).
     *
     * Заполняется только если nginx проброшен через fastcgi_param
     * SSL_PROTOCOL/SSL_CIPHER (стоковый fastcgi_params их не передаёт).
     * Иначе возвращается null и блок в UI не отображается.
     *
     * @return array{protocol: string, cipher: string}|null
     */
    private function getTlsInfo(): ?array
    {
        $protocol = (string)($_SERVER['SSL_PROTOCOL'] ?? '');
        $cipher = (string)($_SERVER['SSL_CIPHER'] ?? '');

        if ($protocol === '' && $cipher === '') {
            return null;
        }

        return [
            'protocol' => $protocol !== '' ? $protocol : 'N/A',
            'cipher' => $cipher !== '' ? $cipher : 'N/A',
        ];
    }

    /**
     * Информация о сборке nginx (`nginx -V`): версия TLS-библиотеки и число модулей.
     *
     * Доступна только на bare-metal при включённом shell_exec; под FPM-hardening
     * или в dev (нет бинаря) деградирует в пустой массив.
     *
     * @return array
     */
    private function getBuildInfo(): array
    {
        if (!function_exists('shell_exec')) {
            return [];
        }

        $output = @shell_exec('timeout 3 nginx -V 2>&1');
        if ($output === null || $output === '') {
            return [];
        }

        $build = [];

        // Строка вида: "built with OpenSSL 3.0.13 30 Jan 2024"
        if (preg_match('~built with (\S+ [\d.]+[\w. ]*?)(?: \(|$|\n)~i', $output, $m) === 1) {
            $build['tlsLibrary'] = trim($m[1]);
        }

        // Число подключённых динамических/статических модулей (--with-*_module).
        $build['modulesCount'] = preg_match_all('~--with-\S+_module~', $output);

        return $build;
    }
}
