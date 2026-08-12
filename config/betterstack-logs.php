<?php

use Goedemiddag\BetterStackLogs\BetterStackHandler;
use Goedemiddag\BetterStackLogs\Processors\LaravelProcessor;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;

return [
    'driver' => 'monolog',
    'level'  => env('LOG_LEVEL', 'debug'),

    'enabled' => env('BETTERSTACK_LOGS_ENABLED', true),

    'handler'      => BetterStackHandler::class,
    'handler_with' => [
        'sourceToken' => env('BETTERSTACK_LOGS_SOURCE_TOKEN'),
        'host'        => env('BETTERSTACK_LOGS_HOST'),

        'connectTimeout' => (int) env('BETTERSTACK_LOGS_CONNECT_TIMEOUT', 0),
        'timeout'        => (int) env('BETTERSTACK_LOGS_TIMEOUT', 0),
        'retries'        => (int) env('BETTERSTACK_LOGS_RETRIES', 0),
    ],

    'processors' => [
        WebProcessor::class,
        PsrLogMessageProcessor::class,
        ProcessIdProcessor::class,
        HostnameProcessor::class,
        LaravelProcessor::class,
        [
            'processor' => IntrospectionProcessor::class,
            'with'      => ['skipClassesPartials' => ['Illuminate\\', 'Goedemiddag\\BetterStackLogs\\']],
        ],
    ],
];
