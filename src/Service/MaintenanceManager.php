<?php

namespace MathisVincent\MaintenanceBundle\Service;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Manages the active state and level of the application maintenance mode.
 */
class MaintenanceManager
{
    private string $stateFile;
    private string $announcementFile;

    public function __construct(KernelInterface $kernel)
    {
        $this->stateFile = $kernel->getProjectDir().'/var/maintenance.json';
        $this->announcementFile = $kernel->getProjectDir().'/var/maintenance_announcement.json';
    }

    /**
     * @param int|null $duration seconds before the maintenance ends by itself, null to keep it until deactivated
     */
    public function activate(string $level, ?int $duration = null): void
    {
        $data = [
            'active' => true,
            'level' => $level,
        ];

        if (null !== $duration) {
            $data['ends_at'] = time() + $duration;
        }

        $this->write($this->stateFile, $data);
        // the announced maintenance has started: visitors must not keep seeing it as upcoming
        $this->cancelAnnouncement();
    }

    public function deactivate(): void
    {
        if (file_exists($this->stateFile)) {
            unlink($this->stateFile);
        }
    }

    public function getState(): ?array
    {
        $state = $this->read($this->stateFile);

        // checked on read, so the maintenance still ends if the server can no longer be reached
        if (isset($state['ends_at']) && $state['ends_at'] <= time()) {
            return null;
        }

        return $state;
    }

    public function announce(int $startsAt): void
    {
        $this->write($this->announcementFile, ['starts_at' => $startsAt]);
    }

    public function cancelAnnouncement(): void
    {
        if (file_exists($this->announcementFile)) {
            unlink($this->announcementFile);
        }
    }

    /**
     * Returns the announced start timestamp, null when nothing is announced or the date has passed.
     */
    public function getAnnouncement(): ?int
    {
        $startsAt = $this->read($this->announcementFile)['starts_at'] ?? null;

        return $startsAt > time() ? $startsAt : null;
    }

    private function write(string $file, array $data): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        file_put_contents($file, json_encode($data));
    }

    private function read(string $file): ?array
    {
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);

        return json_decode($content, true);
    }
}
