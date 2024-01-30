<?php

namespace Goedemiddag\BetterStackLogs;

use Monolog\Handler\BufferHandler;
use Monolog\Level;

class BetterStackHandler extends BufferHandler
{
    public function __construct(string $sourceToken, string $appName = null, int|string|Level $level = Level::Debug)
    {
        $handler = new SynchronousBetterStackHandler($sourceToken, $appName, $level);

        parent::__construct(
            handler: $handler,
            level  : $level,
        );

        // add synchronous handler processors to buffer handler
        $this->pushProcessor(fn($record) => $handler->processRecord($record));
    }
}
