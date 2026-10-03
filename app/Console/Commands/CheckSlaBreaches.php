<?php

namespace App\Console\Commands;

use App\Services\Sla\SlaBreachMonitor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sla:check')]
#[Description('Flag tickets that missed an SLA target and escalate them')]
class CheckSlaBreaches extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SlaBreachMonitor $monitor): int
    {
        $breached = $monitor->run();

        $this->components->info("{$breached} ticket(s) breached their SLA.");

        return self::SUCCESS;
    }
}
