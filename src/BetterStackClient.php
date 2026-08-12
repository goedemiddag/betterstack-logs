<?php

namespace Goedemiddag\BetterStackLogs;

use CurlHandle;
use Monolog\Handler\Curl\Util;
use Throwable;

class BetterStackClient
{
    private const URL = 'https://in.logs.betterstack.com';
    private const DEFAULT_CONNECT_TIMEOUT = 2;
    private const DEFAULT_TIMEOUT = 5;
    private const DEFAULT_RETRIES = 2;

    public function __construct(
        private readonly string $sourceToken,
        private readonly ?string $host,
        private CurlHandle|false $handle = false,
        private readonly ?int $connectTimeout = null,
        private readonly ?int $timeout = null,
        private readonly ?int $retries = null,
    ) {}

    public function send(mixed $data): void
    {
        if (!$this->hasSourceToken()) {
            return;
        }

        if (!$this->handle) {
            $this->initCurlHandle();
        }

        if (!$this->handle instanceof CurlHandle) {
            return;
        }

        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

        try {
            Util::execute($this->handle, $this->positiveOrDefault($this->retries, self::DEFAULT_RETRIES));
        } catch (Throwable $e) {
            error_log('[betterstack-logs] failed to deliver logs: ' . $e->getMessage());
        }
    }

    private function positiveOrDefault(?int $value, int $default): int
    {
        if ($value === null || $value <= 0) {
            return $default;
        }

        return $value;
    }

    private function hasSourceToken(): bool
    {
        return trim($this->sourceToken) !== '';
    }

    private function initCurlHandle(): void
    {
        $this->handle = curl_init();

        if ($this->handle instanceof CurlHandle) {
            $headers = [
                'Content-Type: application/json',
                "Authorization: Bearer {$this->sourceToken}",
            ];

            curl_setopt($this->handle, CURLOPT_URL, $this->host ? "https://$this->host" : self::URL);
            curl_setopt($this->handle, CURLOPT_POST, true);
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT, $this->positiveOrDefault($this->connectTimeout, self::DEFAULT_CONNECT_TIMEOUT));
            curl_setopt($this->handle, CURLOPT_TIMEOUT, $this->positiveOrDefault($this->timeout, self::DEFAULT_TIMEOUT));
        }
    }
}
