<?php

namespace Goedemiddag\BetterStackLogs\Tests;

use Goedemiddag\BetterStackLogs\BetterStackHandler;
use Goedemiddag\BetterStackLogs\Tests\Concerns\CapturesErrorLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class ChannelResolutionTest extends TestCase
{
    use CapturesErrorLog;
    private const DEAD_HOST = '127.0.0.1:1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->captureErrorLog();
    }

    protected function tearDown(): void
    {
        $this->releaseErrorLog();

        parent::tearDown();
    }

    #[Test]
    public function the_channel_resolves_and_accepts_writes_without_a_source_token(): void
    {
        Config::set('logging.channels.betterstack.handler_with.sourceToken', null);
        Config::set('logging.channels.betterstack.handler_with.host', self::DEAD_HOST);

        $logger = Log::channel('betterstack');

        $this->assertInstanceOf(
            BetterStackHandler::class,
            $logger->getLogger()->getHandlers()[0],
            'The channel should resolve normally, not fall back to the emergency logger.',
        );

        $logger->info('no source token configured');

        $logger->getLogger()->close();

        $this->assertSame('', $this->capturedErrorLog(), 'A blank token must perform no network I/O.');
    }

    #[Test]
    public function the_channel_performs_no_network_io_when_disabled_with_a_valid_token(): void
    {
        Config::set('logging.channels.betterstack.enabled', false);
        Config::set('logging.channels.betterstack.handler_with.sourceToken', 'a-valid-token');
        Config::set('logging.channels.betterstack.handler_with.host', self::DEAD_HOST);

        $logger = Log::channel('betterstack');

        $logger->info('should go nowhere');
        $logger->getLogger()->close();

        $this->assertSame('', $this->capturedErrorLog(), 'A disabled channel must perform no network I/O.');
    }

    #[Test]
    public function the_channel_still_attempts_delivery_when_enabled_with_a_valid_token(): void
    {
        Config::set('logging.channels.betterstack.handler_with.sourceToken', 'a-valid-token');
        Config::set('logging.channels.betterstack.handler_with.host', self::DEAD_HOST);

        $logger = Log::channel('betterstack');

        $logger->info('should be attempted');
        $logger->getLogger()->close();

        $this->assertStringContainsString(
            'failed to deliver logs',
            $this->capturedErrorLog(),
            'The default configuration must still ship logs; only the attempt failing is expected here.',
        );
    }

    #[Test]
    public function it_survives_an_action_level_wrapped_handler_when_disabled(): void
    {
        Config::set('logging.channels.betterstack.enabled', false);
        Config::set('logging.channels.betterstack.handler_with.sourceToken', 'a-valid-token');
        Config::set('logging.channels.betterstack.handler_with.host', self::DEAD_HOST);
        Config::set('logging.channels.betterstack.action_level', 'debug');

        $logger = Log::channel('betterstack');

        $logger->error('wrapped in a FingersCrossedHandler');
        $logger->getLogger()->close();

        $this->assertSame(
            '',
            $this->capturedErrorLog(),
            'FingersCrossedHandler calls handle() directly, bypassing isHandling().',
        );
    }
}
