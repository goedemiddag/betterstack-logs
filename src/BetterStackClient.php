<?php

namespace Goedemiddag\BetterStackLogs;

use CurlHandle;
use Monolog\Handler\Curl\Util;

class BetterStackClient
{
    const URL = "https://in.logtail.com";

    public function __construct(
        private readonly string $sourceToken,
        private readonly string $endpoint = self::URL,
        private ?CurlHandle $handle = null
    ) {
    }

    public function send($data): void
    {
        if (is_null($this->handle)) {
            $this->initCurlHandle();
        }

        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

        Util::execute($this->handle, 5, false);
    }

    private function initCurlHandle(): void
    {
        $this->handle = curl_init();

        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$this->sourceToken}",
        ];

        curl_setopt($this->handle, CURLOPT_URL, $this->endpoint);
        curl_setopt($this->handle, CURLOPT_POST, true);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
    }
}
