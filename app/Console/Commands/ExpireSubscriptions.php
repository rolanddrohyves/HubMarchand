<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Suspend les boutiques dont l’abonnement est arrivé à échéance';

    public function handle(): int
    {
        Store::with('user')
            ->where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now())
            ->chunkById(100, function ($stores): void {
                foreach ($stores as $store) {
                    if ($store->suspendIfSubscriptionExpired()) {
                        $store->user?->notify(new SubscriptionExpiredNotification($store));
                        $this->info('Boutique suspendue : '.$store->name);
                    }
                }
            });

        return self::SUCCESS;
    }
}
