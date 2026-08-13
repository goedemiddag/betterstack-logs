<?php

namespace Goedemiddag\BetterStackLogs\Tests\Doubles;

use Goedemiddag\BetterStackLogs\BetterStackClient;

class RecordingClient extends BetterStackClient
{
    /** @var array<int, string> */
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('recording-token', null);
    }

    public function send(string $data): void
    {
        $this->sent[] = $data;
    }
}
