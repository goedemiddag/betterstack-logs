<?php

namespace Goedemiddag\BetterStackLogs\Processors;

use Illuminate\Support\Facades\Config;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class LaravelProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['app_name'] = Config::get('logging.channels.betterstack.handler_with.appName') ?? Config::get('app.name');
        $record->extra['app_env'] = Config::get('app.env');

        return $record;
    }
}
