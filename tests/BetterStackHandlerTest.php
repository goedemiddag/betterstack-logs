<?php

namespace Goedemiddag\BetterStackLogs\Tests;

use DateTimeImmutable;
use Goedemiddag\BetterStackLogs\BetterStackHandler;
use Goedemiddag\BetterStackLogs\SynchronousBetterStackHandler;
use Goedemiddag\BetterStackLogs\Tests\Doubles\RecordingClient;
use Illuminate\Support\Facades\Config;
use Monolog\Handler\BufferHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

class BetterStackHandlerTest extends TestCase
{
    private function record(string $message = 'a message'): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable,
            channel : 'testing',
            level   : Level::Info,
            message : $message,
        );
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function blankTokens(): array
    {
        return [
            'null token'       => [null],
            'empty token'      => [''],
            'whitespace token' => ['   '],
        ];
    }

    #[Test]
    #[DataProvider('blankTokens')]
    public function it_constructs_without_error_when_the_source_token_is_blank(?string $token): void
    {
        $handler = new BetterStackHandler($token);

        $this->assertInstanceOf(BetterStackHandler::class, $handler);
    }

    #[Test]
    #[DataProvider('blankTokens')]
    public function it_never_reaches_the_client_when_the_source_token_is_blank(?string $token): void
    {
        $client = new RecordingClient;

        $handler = new BetterStackHandler($token, null, Level::Debug, null, $client);

        $this->assertFalse($handler->handle($this->record()));

        $handler->close();

        $this->assertSame([], $client->sent);
    }

    #[Test]
    public function it_never_reaches_the_client_when_disabled_even_with_a_valid_token(): void
    {
        $client = new RecordingClient;

        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, false, $client);

        $this->assertFalse($handler->handle($this->record()));

        $handler->close();

        $this->assertSame([], $client->sent);
    }

    #[Test]
    public function it_reads_the_enabled_flag_from_the_channel_config(): void
    {
        Config::set('logging.channels.betterstack.enabled', false);

        $client = new RecordingClient;

        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, null, $client);

        $handler->handle($this->record());
        $handler->close();

        $this->assertSame([], $client->sent);
    }

    #[Test]
    public function the_constructor_argument_overrides_the_config_key(): void
    {
        Config::set('logging.channels.betterstack.enabled', false);

        $client = new RecordingClient;

        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, true, $client);

        $handler->handle($this->record());
        $handler->close();

        $this->assertCount(1, $client->sent);
    }

    #[Test]
    public function it_defaults_to_enabled_when_the_config_key_is_absent_entirely(): void
    {
        Config::set('logging', []);

        $this->assertNull(Config::get('logging.channels.betterstack.enabled'));

        $client = new RecordingClient;

        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, null, $client);

        $handler->handle($this->record());
        $handler->close();

        $this->assertCount(1, $client->sent, 'An upgrade without config changes must keep shipping logs.');
    }

    #[Test]
    public function it_sends_a_record_to_the_client_when_enabled_with_a_valid_token(): void
    {
        $client = new RecordingClient;

        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, true, $client);

        $handler->handle($this->record('hello betterstack'));
        $handler->close();

        $this->assertCount(1, $client->sent);
        $this->assertStringContainsString('hello betterstack', (string) $client->sent[0]);
    }

    #[Test]
    public function is_handling_reports_false_when_the_channel_is_off(): void
    {
        $disabledByToken = new BetterStackHandler(null);
        $disabledByFlag = new BetterStackHandler('a-valid-token', null, Level::Debug, false);
        $active = new BetterStackHandler('a-valid-token', null, Level::Debug, true);

        $this->assertFalse($disabledByToken->isHandling($this->record()));
        $this->assertFalse($disabledByFlag->isHandling($this->record()));
        $this->assertTrue($active->isHandling($this->record()));
    }

    private function bufferSizeOf(BetterStackHandler $handler): int
    {
        return (int) (new ReflectionProperty(BufferHandler::class, 'bufferSize'))->getValue($handler);
    }

    #[Test]
    public function a_disabled_handler_buffers_nothing(): void
    {
        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, false, new RecordingClient);

        $handler->handle($this->record());

        $this->assertSame(0, $this->bufferSizeOf($handler), 'A disabled channel must not buffer for the shutdown flush.');
    }

    #[Test]
    public function an_active_handler_still_buffers(): void
    {
        $handler = new BetterStackHandler('a-valid-token', null, Level::Debug, true, new RecordingClient);

        $handler->handle($this->record());

        $this->assertSame(1, $this->bufferSizeOf($handler));
    }

    #[Test]
    public function the_synchronous_handler_ignores_handle_batch_when_the_channel_is_off(): void
    {
        $client = new RecordingClient;

        $handler = new SynchronousBetterStackHandler('a-valid-token', null, Level::Debug, false, $client);

        $handler->handleBatch([$this->record()]);

        $this->assertSame([], $client->sent);
    }

    #[Test]
    public function the_synchronous_handler_is_inactive_when_the_channel_is_off(): void
    {
        $this->assertFalse((new SynchronousBetterStackHandler(null, null))->isActive());
        $this->assertFalse((new SynchronousBetterStackHandler('', null))->isActive());
        $this->assertFalse((new SynchronousBetterStackHandler('a-valid-token', null, Level::Debug, false))->isActive());
        $this->assertTrue((new SynchronousBetterStackHandler('a-valid-token', null))->isActive());
    }
}
