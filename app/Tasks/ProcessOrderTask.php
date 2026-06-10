<?php

declare(strict_types=1);

namespace App\Tasks;

use AndyDefer\Task\AbstractTask;

final class ProcessOrderTask extends AbstractTask
{
    protected function before(): void
    {
        // Before execution - initialization, checks
        if (!$this->hasLaravel()) {
            $this->error('Laravel is not available!');
            throw new \RuntimeException('Laravel required');
        }
    }
    
    protected function process(): void
    {
        // Your business logic here
        $this->info('Task executed successfully!');
    }
    
    protected function after(bool $success, ?string $error = null): void
    {
        // After execution - cleanup, notifications
        if ($success) {
            $this->info('Task completed successfully');
        } else {
            $this->error("Task failed: {$error}");
        }
    }
}