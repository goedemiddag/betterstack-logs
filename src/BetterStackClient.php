<?php

namespace Goedemiddag\BetterStackLogs;

use CurlHandle;
use Monolog\Handler\Curl\Util;

class BetterStackClient
{
    private const URL = 'https://in.logs.betterstack.com';

    public function __construct(
        private readonly string $sourceToken,
        private CurlHandle|false $handle = false,
    ) {
    }

    public function send(mixed $data): void
    {
        if (!$this->handle) {
            $this->initCurlHandle();
        }

        if ($this->handle instanceof CurlHandle) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
            curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

            Util::execute($this->handle, 5, false);
        }
    }

    private function initCurlHandle(): void
    {
        $this->handle = curl_init();

        if ($this->handle instanceof CurlHandle) {
            $headers = [
                'Content-Type: application/json',
                "Authorization: Bearer {$this->sourceToken}",
            ];

            curl_setopt($this->handle, CURLOPT_URL, self::URL);
            curl_setopt($this->handle, CURLOPT_POST, true);
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
        }
    }
}
