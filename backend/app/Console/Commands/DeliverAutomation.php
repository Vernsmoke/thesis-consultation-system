<?php

namespace App\Console\Commands;

use App\Models\AutomationItem;
use App\Services\AutomationDelivery;
use Illuminate\Console\Command;
use Throwable;

class DeliverAutomation extends Command
{
    protected $signature = 'automation:work {--id= : Process one specific existing item} {--limit=20 : Maximum items, 1-50}';

    protected $description = 'Process a bounded batch of due local notification work';

    public function handle(AutomationDelivery $delivery): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        $id = $this->option('id');
        if ($limit === false || $limit < 1 || $limit > 50 || ($id !== null && (! ctype_digit($id) || (int) $id < 1))) {
            $this->error('Invalid id or batch limit.');

            return self::FAILURE;
        }
        try {
            $ids = $id !== null ? [(int) $id] : AutomationItem::query()->where(function ($query) {
                $query->where(function ($due) {
                    $due->whereIn('status', ['pending', 'retry_wait'])->where('available_at', '<=', now());
                })->orWhere(function ($expired) {
                    $expired->where('status', 'claimed')->where('claimed_until', '<', now());
                });
            })->orderBy('id')->limit($limit)->pluck('id')->all();
            $logFailure = false;
            foreach ($ids as $workId) {
                $result = $delivery->process($workId);
                $logFailure = $logFailure || $result['log_status'] !== 'recorded';
                $this->line(json_encode($result, JSON_UNESCAPED_SLASHES));
            }

            return $logFailure ? self::FAILURE : self::SUCCESS;
        } catch (Throwable) {
            $this->error('Worker stopped; inspect configuration and durable attempts. Earlier items may have committed; no raw diagnostics printed.');

            return self::FAILURE;
        }
    }
}
