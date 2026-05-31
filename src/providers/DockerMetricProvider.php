<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Info\providers;

/**
 * Провайдер метрик Docker
 * Собирает информацию о контейнерах, images, volumes через Docker CLI
 */
class DockerMetricProvider extends BaseMetricProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Docker';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return 'docker';
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAvailability(): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }

        $version = $this->safeShellExec('docker --version');
        return $version !== null && str_contains($version, 'Docker version');
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMetrics(): array
    {
        return [
            'version' => $this->getDockerVersion(),
            'containers' => $this->getContainers(),
            'images' => $this->getImagesCount(),
            'volumes' => $this->getVolumesCount(),
            'networks' => $this->getNetworksCount(),
        ];
    }

    /**
     * Получает версию Docker
     *
     * @return string
     */
    private function getDockerVersion(): string
    {
        $output = $this->safeShellExec('docker --version');
        if ($output && preg_match('/Docker version ([\d.]+)/', $output, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    /**
     * Получает список контейнеров
     *
     * @return array
     */
    private function getContainers(): array
    {
        $output = $this->safeShellExec('docker ps -a --format "{{.ID}}|{{.Names}}|{{.State}}|{{.Status}}|{{.Image}}"');
        if (!$output) {
            return [];
        }

        $containers = [];
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $parts = explode('|', $line);
            if (count($parts) < 5) {
                continue;
            }

            $containers[] = [
                'id' => $parts[0],
                'name' => $parts[1],
                'state' => $parts[2],
                'status' => $parts[3],
                'image' => $parts[4],
            ];
        }

        return $containers;
    }

    /**
     * Получает количество images
     *
     * @return int
     */
    private function getImagesCount(): int
    {
        $output = $this->safeShellExec('docker images -q | wc -l');
        return $output ? (int)$output : 0;
    }

    /**
     * Получает количество volumes
     *
     * @return int
     */
    private function getVolumesCount(): int
    {
        $output = $this->safeShellExec('docker volume ls -q | wc -l');
        return $output ? (int)$output : 0;
    }

    /**
     * Получает количество networks
     *
     * @return int
     */
    private function getNetworksCount(): int
    {
        $output = $this->safeShellExec('docker network ls -q | wc -l');
        return $output ? (int)$output : 0;
    }

    /**
     * Получает логи контейнера
     *
     * @param string $containerId ID или имя контейнера
     * @param int $lines Количество последних строк (по умолчанию 100)
     * @return array
     */
    public function getContainerLogs(string $containerId, int $lines = 100): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Docker is not available',
            ];
        }

        // Ограничение на количество строк
        $lines = min($lines, 1000);

        $output = $this->safeShellExec(
            sprintf('docker logs --tail %d %s 2>&1', $lines, escapeshellarg($containerId)),
            10 // увеличенный таймаут для логов
        );

        if ($output === null) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve logs',
            ];
        }

        // Ограничение на размер вывода (1 MB)
        if (strlen($output) > 1024 * 1024) {
            $output = substr($output, -1024 * 1024);
        }

        return [
            'success' => true,
            'logs' => $output,
            'lines' => substr_count($output, "\n"),
        ];
    }

    /**
     * Получает статистику контейнера
     *
     * @param string $containerId ID или имя контейнера
     * @return array
     */
    public function getContainerStats(string $containerId): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Docker is not available',
            ];
        }

        $output = $this->safeShellExec(
            sprintf(
                'docker stats %s --no-stream --format "{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}|{{.NetIO}}|{{.BlockIO}}"',
                escapeshellarg($containerId)
            )
        );

        if (!$output) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve stats',
            ];
        }

        $parts = explode('|', $output);
        if (count($parts) < 5) {
            return [
                'success' => false,
                'message' => 'Invalid stats format',
            ];
        }

        return [
            'success' => true,
            'cpu' => trim($parts[0]),
            'memoryUsage' => trim($parts[1]),
            'memoryPercent' => trim($parts[2]),
            'networkIO' => trim($parts[3]),
            'blockIO' => trim($parts[4]),
        ];
    }
}
