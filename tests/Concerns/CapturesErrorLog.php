<?php

namespace Goedemiddag\BetterStackLogs\Tests\Concerns;

trait CapturesErrorLog
{
    private string $errorLogPath;

    private string|false $previousErrorLog;

    private string|false $previousLogErrors;

    protected function captureErrorLog(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'betterstack-error-log-');

        $this->errorLogPath = $path === false ? '' : $path;

        $this->previousErrorLog = ini_get('error_log');
        $this->previousLogErrors = ini_get('log_errors');

        ini_set('error_log', $this->errorLogPath);
        ini_set('log_errors', '1');
    }

    protected function releaseErrorLog(): void
    {
        ini_set('error_log', $this->previousErrorLog === false ? '' : $this->previousErrorLog);
        ini_set('log_errors', $this->previousLogErrors === false ? '1' : $this->previousLogErrors);

        if (is_file($this->errorLogPath)) {
            unlink($this->errorLogPath);
        }
    }

    protected function capturedErrorLog(): string
    {
        return is_file($this->errorLogPath) ? (string) file_get_contents($this->errorLogPath) : '';
    }
}
