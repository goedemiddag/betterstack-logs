<?php

namespace Goedemiddag\BetterStackLogs;

use Illuminate\Support\ServiceProvider;

class BetterStackLogsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/betterstack-logs.php', 'logging.channels.betterstack',
        );
    }
}
