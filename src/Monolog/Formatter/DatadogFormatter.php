<?php

namespace MonologDatadog\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class DatadogFormatter extends JsonFormatter
{
    /**
     * Map Monolog\Level levels to Datadog status type
     */
    private const DATADOG_LEVEL_MAP = [
        100 => 'info',
        200 => 'info',
        250 => 'warning',
        300 => 'warning',
        400 => 'error',
        500 => 'error',
        550 => 'error',
        600 => 'error',
    ];
    /**
     * @var bool LogRecord $record
     */
    protected bool $includeStacktraces = true;

    public function format(LogRecord $record): string
    {
        $normalized = $this->normalize($record);
        $r = $normalized->toArray();

        if (isset($r['context']) && $r['context'] === []) {
            $r['context'] = new \stdClass;
        }

        if (isset($r['extra']) && $r['extra'] === []) {
            $r['extra'] = new \stdClass;
        }
        $r['status'] = static::DATADOG_LEVEL_MAP[$record->level->value];
        return $this->toJson($normalized, true);
    }
}
