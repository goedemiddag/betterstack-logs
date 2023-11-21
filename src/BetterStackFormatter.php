<?php

namespace Goedemiddag\BetterStackLogs;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class BetterStackFormatter extends JsonFormatter
{

    public function __construct()
    {
        parent::__construct(
            appendNewline: false
        );
    }

    public function format(LogRecord $record): string
    {
        $normalized = $this->normalize(self::formatRecord($record));

        return $this->toJson($normalized, true);
    }

    public function formatBatch(array $records): string
    {
        $normalized = array_values($this->normalize(array_map(self::formatRecord(...), $records)));

        return $this->toJson($normalized, true);
    }

    protected static function formatRecord(LogRecord $record): array
    {
        return [
            'dt'      => $record->datetime,
            'message' => $record->message,
            'level'   => $record->level->name,
            'monolog' => [
                'channel' => $record->channel,
                'context' => $record->context,
                'extra'   => $record->extra,
            ],
        ];
    }
}
