<?php

namespace MathisVincent\MaintenanceBundle\Service;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Manages the active state and level of the application maintenance mode.
 */
class MaintenanceManager
{
    private string $stateFile;

    public function __construct(KernelInterface $kernel)
    {
        $this->stateFile = $kernel->getProjectDir() . '/var/maintenance.json';
    }

    public function activate(string $level): void
    {
        $data = [
            'active' => true,
            'level' => $level,
        ];

        $dir = dirname($this->stateFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->stateFile, json_encode($data));
    }

    public function deactivate(): void
    {
        if (file_exists($this->stateFile)) {
            unlink($this->stateFile);
        }
    }

    public function getState(): ?array
    {
        if (!file_exists($this->stateFile)) {
            return null;
        }

        $content = file_get_contents($this->stateFile);
        return json_decode($content, true);
    }
}