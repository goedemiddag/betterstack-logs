<?php

namespace Goedemiddag\BetterStackLogs\Tests;

use DateTimeImmutable;
use Goedemiddag\BetterStackLogs\BetterStackClient;
use Goedemiddag\BetterStackLogs\BetterStackHandler;
use Goedemiddag\BetterStackLogs\SynchronousBetterStackHandler;
use Goedemiddag\BetterStackLogs\Tests\Concerns\CapturesErrorLog;
use Goedemiddag\BetterStackLogs\Tests\Doubles\ThrowingClient;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Test;

class TransportFailureTest extends TestCase
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

    private function record(string $message = 'a message'): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable,
            channel : 'testing',
            level   : Level::Info,
            message : $message,
        );
    }

    #[Test]
    public function a_throwing_transport_does_not_propagate_out_of_handle_batch(): void
    {
        $client = new ThrowingClient;

        $handler = new SynchronousBetterStackHandler('a-valid-token', null, Level::Debug, true, $client);

        $handler->handleBatch([$this->record()]);

        $this->assertSame(1, $client->attempts);
        $this->assertStringContainsString('failed to deliver logs', $this->capturedErrorLog());
    }

    #[Test]
    public function a_throwing_transport_does_not_propagate_out_of_write(): void
    {
        $client = new ThrowingClient;

        $handler = new SynchronousBetterStackHandler('a-valid-token', null, Level::Debug, true, $client);

        $handler->handle($this->record());

        $this->assertSame(1, $client->attempts);
        $this->assertStringContainsString('failed to deliver logs', $this->capturedErrorLog());
    }

    #[Test]
    public function a_throwing_transport_does_not_propagate_out_of_the_buffered_shutdown_flush(): void
    {
        $client = new ThrowingClient;

        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, true, $client);

        $handler->handle($this->record());

        $handler->close();

        $this->assertSame(1, $client->attempts);
        $this->assertStringContainsString('failed to deliver logs', $this->capturedErrorLog());
    }

    #[Test]
    public function the_client_swallows_a_real_curl_failure(): void
    {
        $client = new BetterStackClient('a-valid-token', self::DEAD_HOST);

        $client->send('{"message":"hello"}');

        $this->assertStringContainsString(
            'failed to deliver logs',
            $this->capturedErrorLog(),
            'A real curl failure should be reported to error_log, not thrown.',
        );
    }

    #[Test]
    public function the_client_makes_no_request_at_all_when_the_source_token_is_blank(): void
    {
        $client = new BetterStackClient('', self::DEAD_HOST);

        $client->send('{"message":"hello"}');

        $this->assertSame(
            '',
            $this->capturedErrorLog(),
            'A blank token must short-circuit before curl_init(); reaching the dead host would have logged a failure.',
        );
    }
}
