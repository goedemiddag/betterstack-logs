# Monolog handler for Better Stack Logs

[![Laravel Compatibility](https://badge.laravel.cloud/badge/goedemiddag/betterstack-logs)](https://packagist.org/packages/goedemiddag/betterstack-logs)

This package allows you to write logs to Better Stack Logs.

## Installation

First use composer to install the package using the following command

```sh
composer require goedemiddag/betterstack-logs
```

## Usage

Add a new channel to the `config/logging.php` file

```php
'channels' => [
    ...
    'betterstack' => [
        'driver'       => 'monolog',
        'level'        => env('LOG_LEVEL', 'debug'),
        'enabled'      => env('BETTERSTACK_LOGS_ENABLED', true),
        'handler'      => \Goedemiddag\BetterStackLogs\BetterStackHandler::class,
        'handler_with' => [
            'sourceToken' => env('BETTERSTACK_LOGS_SOURCE_TOKEN'),
            'host'        => env('BETTERSTACK_LOGS_HOST'),
        ],
    ],
    ...
]
```

Set the default log channel to `betterstack` or add it to the `stack` channel.

Add the following to your `.env` file:

```sh
BETTERSTACK_LOGS_SOURCE_TOKEN=your-source-token
BETTERSTACK_LOGS_HOST=your-ingestion-host
```
