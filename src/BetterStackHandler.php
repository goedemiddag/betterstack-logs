<?php

namespace Goedemiddag\BetterStackLogs;

use Monolog\Handler\BufferHandler;
use Monolog\Level;
use Monolog\LogRecord;

class BetterStackHandler extends BufferHandler
{
    private bool $active;

    public function __construct(
        ?string $sourceToken = null,
        ?string $host = null,
        int|string|Level $level = Level::Debug,
        ?bool $enabled = null,
        ?BetterStackClient $client = null,
        ?int $connectTimeout = null,
        ?int $timeout = null,
        ?int $retries = null,
    ) {
        $handler = new SynchronousBetterStackHandler(
            sourceToken   : $sourceToken,
            host          : $host,
            level         : $level,
            enabled       : $enabled,
            client        : $client,
            connectTimeout: $connectTimeout,
            timeout       : $timeout,
            retries       : $retries,
        );

        parent::__construct(
            handler: $handler,
            level  : $level,
        );

        $this->active = $handler->isActive();

        $this->pushProcessor(fn($record) => $handler->processRecord($record));
    }

    public function isHandling(LogRecord $record): bool
    {
        if (!$this->active) {
            return false;
        }

        return parent::isHandling($record);
    }

    public function handle(LogRecord $record): bool
    {
        if (!$this->active) {
            return false;
        }

        return parent::handle($record);
    }
}
