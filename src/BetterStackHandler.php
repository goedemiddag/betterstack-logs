<?php

namespace Goedemiddag\BetterStackLogs;

use Monolog\Handler\BufferHandler;
use Monolog\Level;

class BetterStackHandler extends BufferHandler
{
    public function __construct(
        string $sourceToken,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct(
            handler: new SynchronousBetterStackHandler($sourceToken, $level, $bubble, BetterStackClient::URL),
            level  : $level,
            bubble : $bubble,
        );
    }
}