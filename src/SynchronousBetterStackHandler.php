<?php

namespace Goedemiddag\BetterStackLogs;

use Goedemiddag\BetterStackLogs\Processors\LaravelProcessor;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;

class SynchronousBetterStackHandler extends AbstractProcessingHandler
{
    private BetterStackClient $client;

    public function __construct(
        string $sourceToken,
        string $appName = null,
        int|string|Level $level = Level::Debug,
    ) {
        parent::__construct($level);

        $this->client = new BetterStackClient($sourceToken);

        $this->pushProcessor(new WebProcessor);
        $this->pushProcessor(new PsrLogMessageProcessor);
        $this->pushProcessor(new ProcessIdProcessor);
        $this->pushProcessor(new HostnameProcessor);
        $this->pushProcessor(new LaravelProcessor($appName));
        $this->pushProcessor(new IntrospectionProcessor(skipClassesPartials: ['Illuminate\\', 'Goedemiddag\\BetterStackLogs\\']));
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

    public function processRecord(LogRecord $record): LogRecord
    {
        return parent::processRecord($record);
    }
}
