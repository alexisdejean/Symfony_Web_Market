<?php

namespace App\Security;

use Symfony\Component\HttpKernel\KernelInterface;

final class FileRateLimiter
{
    private const MAX_TRACKED_IDENTITIES = 5000;

    public function __construct(private KernelInterface $kernel)
    {
    }

    public function consume(string $scope, string $identity, int $limit, int $windowSeconds): bool
    {
        if ($limit < 1 || $windowSeconds < 1) {
            return false;
        }

        $directory = $this->kernel->getCacheDir().'/security-rate-limits';
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            return false;
        }

        $file = $directory.'/'.hash('sha256', $scope).'.json';
        $handle = @fopen($file, 'c+');
        if (!is_resource($handle)) {
            return false;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);

            return false;
        }

        try {
            rewind($handle);
            $contents = stream_get_contents($handle);
            if (!is_string($contents)) {
                return false;
            }

            try {
                $entries = $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return false;
            }

            if (!is_array($entries)) {
                return false;
            }

            $now = time();
            foreach ($entries as $key => $entry) {
                if (!is_array($entry) || (int) ($entry['expires_at'] ?? 0) <= $now) {
                    unset($entries[$key]);
                }
            }

            $key = hash('sha256', $identity);
            if (!isset($entries[$key]) && count($entries) >= self::MAX_TRACKED_IDENTITIES) {
                return false;
            }

            $entry = $entries[$key] ?? ['count' => 0, 'expires_at' => $now + $windowSeconds];
            if ((int) $entry['count'] >= $limit) {
                return false;
            }

            ++$entry['count'];
            $entries[$key] = $entry;
            $encoded = json_encode($entries, JSON_THROW_ON_ERROR);

            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $encoded) !== strlen($encoded) || !fflush($handle)) {
                return false;
            }

            @chmod($file, 0600);

            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}