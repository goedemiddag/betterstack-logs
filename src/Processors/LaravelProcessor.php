<?php

namespace Goedemiddag\BetterStackLogs\Processors;

use Illuminate\Support\Facades\Config;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class LaravelProcessor implements ProcessorInterface
{
    private static mixed $name;
    private static mixed $env;

    public function __construct(?string $appName = null)
    {
        self::$name = $appName ?? Config::get('app.name');
        self::$env = Config::get('app.env');
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['app_name'] = self::$name;
        $record->extra['app_env'] = self::$env;

        return $record;
    }
}
