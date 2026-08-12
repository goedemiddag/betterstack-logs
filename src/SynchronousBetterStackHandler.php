<?php

namespace Goedemiddag\BetterStackLogs;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

class SynchronousBetterStackHandler extends AbstractProcessingHandler
{
    private BetterStackClient $client;

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
        parent::__construct($level);

        $this->active = self::resolveEnabled($enabled) && trim((string) $sourceToken) !== '';

        $this->client = $client ?? new BetterStackClient(
            sourceToken   : (string) $sourceToken,
            host          : $host,
            connectTimeout: $connectTimeout,
            timeout       : $timeout,
            retries       : $retries,
        );

        if (!$this->active) {
            return;
        }

        /** @var array<callable> $processors */
        $processors = (new Collection(Arr::wrap(Config::get('logging.channels.betterstack.processors'))))
            ->map(function ($processor) {
                if (is_string($processor)) {
                    return Container::getInstance()->make($processor);
                }

                if (is_array($processor) && is_string($processor['processor'] ?? null)) {
                    return Container::getInstance()->make($processor['processor'], Arr::wrap($processor['with'] ?? []));
                }

                throw new InvalidArgumentException('Invalid processor definition for BetterStack handler.');
            })
            ->toArray();

        $this->processors = $processors;
    }

    private static function resolveEnabled(?bool $enabled): bool
    {
        if ($enabled !== null) {
            return $enabled;
        }

        $configured = Config::get('logging.channels.betterstack.enabled');

        return (bool) ($configured ?? true);
    }

    public function isHandling(LogRecord $record): bool
    {
        if (!$this->active) {
            return false;
        }

        return parent::isHandling($record);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    protected function write(LogRecord $record): void
    {
        try {
            $this->client->send($record->formatted);
        } catch (Throwable $e) {
            $this->reportFailure($e);
        }
    }

    public function handleBatch(array $records): void
    {
        if (!$this->active) {
            return;
        }

        try {
            $formattedRecords = $this->getFormatter()->formatBatch($records);

            $this->client->send($formattedRecords);
        } catch (Throwable $e) {
            $this->reportFailure($e);
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new BetterStackFormatter;
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->getDefaultFormatter();
    }

    public function processRecord(LogRecord $record): LogRecord
    {
        return parent::processRecord($record);
    }

    private function reportFailure(Throwable $e): void
    {
        error_log('[betterstack-logs] failed to deliver logs: ' . $e->getMessage());
    }
}
