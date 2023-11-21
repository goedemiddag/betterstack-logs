<?php

declare(strict_types=0);

namespace Goedemiddag\BetterStackLogs;

use Illuminate\Support\Arr;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class BetterStackFormatter extends JsonFormatter
{
    public function __construct()
    {
        parent::__construct(
            appendNewline: false,
        );

        $this->setMaxNormalizeItemCount(PHP_INT_MAX);
    }

    public function format(LogRecord $record): string
    {
        $normalized = $this->normalize(self::formatRecord($record));

        return $this->toJson($normalized, true);
    }

    public function formatBatch(array $records): string
    {
        $normalized = array_values((array) $this->normalize(array_map(self::formatRecord(...), $records)));

        return $this->toJson($normalized, true);
    }

    /**
     * @return array<mixed>
     */
    protected static function formatRecord(LogRecord $record): array
    {
        $data = [
            'dt'      => $record->datetime,
            'message' => $record->message,
            'level'   => $record->level->name,
            'monolog' => [
                'channel' => $record->channel,
            ],
        ];

        // only add context when it is not empty
        if ($context = $record->context) {
            $data['monolog']['context'] = $context;
        }

        $extra = $record->extra;

        if ($name = Arr::pull($extra, 'app_name')) {
            /** @var string $name */
            $data['app_name'] = "[{$name}]";
        }

        $data['monolog']['extra'] = $extra;

        return $data;
    }
}
