<?php

namespace Goedemiddag\BetterStackLogs\Tests\Doubles;

use Goedemiddag\BetterStackLogs\BetterStackClient;
use RuntimeException;

class ThrowingClient extends BetterStackClient
{
    public int $attempts = 0;

    public function __construct()
    {
        parent::__construct('throwing-token', null);
    }

    public function send(mixed $data): void
    {
        $this->attempts++;

        throw new RuntimeException("Curl error (code 60): SSL: no alternative certificate subject name matches target hostname 'in.logs.betterstack.com'");
    }
}
