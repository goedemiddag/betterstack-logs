<?php

namespace Goedemiddag\BetterStackLogs\Tests;

use Goedemiddag\BetterStackLogs\BetterStackLogsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BetterStackLogsServiceProvider::class,
        ];
    }
}
