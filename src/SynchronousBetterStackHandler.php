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

class SynchronousBetterStackHandler extends AbstractProcessingHandler
{
    private BetterStackClient $client;

    public function __construct(
        string $sourceToken,
        ?string $host,
        int|string|Level $level = Level::Debug,
    ) {
        parent::__construct($level);

        $this->client = new BetterStackClient($sourceToken, $host);

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

    protected function write(LogRecord $record): void
    {
        $this->client->send($record->formatted);
    }

    public function handleBatch(array $records): void
    {
        $formattedRecords = $this->getFormatter()->formatBatch($records);

        $this->client->send($formattedRecords);
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
}
