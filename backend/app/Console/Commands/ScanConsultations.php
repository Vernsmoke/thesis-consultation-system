<?php

namespace App\Console\Commands;

use App\Services\AutomationScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ScanConsultations extends Command
{
    protected $signature = 'consultations:scan';

    protected $description = 'Create bounded, deduplicated consultation automation work; no delivery yet';

    public function handle(AutomationScanner $scanner): int
    {
        try {
            $result = $scanner->scan();
            $this->line(json_encode($result, JSON_UNESCAPED_SLASHES));
            if ($result['log_status'] !== 'recorded') {
                $this->error('Scan committed; automation log unavailable. Inspect durable work before retrying.');

                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (Throwable $error) {
            $safeCodes = ['invalid_scan_configuration', 'missing_review_owner', 'scan_capacity_exceeded'];
            $code = $error instanceof RuntimeException && in_array($error->getMessage(), $safeCodes, true)
                ? $error->getMessage() : 'scan_storage_failure';
            $runId = (string) Str::uuid();
            try {
                Log::channel('automation')->error('scan_failed', ['run_id' => $runId, 'code' => $code, 'environment' => app()->environment()]);
            } catch (Throwable) {
                // The command result still exposes failure if the log sink is down.
            }
            $this->error($code.'; no scan changes committed; run_id='.$runId);

            return self::FAILURE;
        }
    }
}
