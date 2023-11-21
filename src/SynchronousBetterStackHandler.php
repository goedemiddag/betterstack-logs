<?php

namespace Goedemiddag\BetterStackLogs;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\WebProcessor;

class SynchronousBetterStackHandler extends AbstractProcessingHandler
{
    private BetterStackClient $client;

    public function __construct(
        string $sourceToken,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
        string $endpoint = BetterStackClient::URL
    ) {
        parent::__construct($level, $bubble);

        $this->client = new BetterStackClient($sourceToken, $endpoint);

        $this->pushProcessor(new WebProcessor);
        $this->pushProcessor(new ProcessIdProcessor);
        $this->pushProcessor(new HostnameProcessor);
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
        return new BetterStackFormatter();
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->getDefaultFormatter();
    }
}
