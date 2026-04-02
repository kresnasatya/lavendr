<?php

namespace App\Console\Commands;

use App\Enums\RechargeMode;
use App\Models\RechargeSetting;
use App\Models\RoleLimit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RechargeBalanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:recharge {--role= : Only recharge for a specific role ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recharge employee balances based on recharge settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $this->info("Running balance recharge at {$currentTime}");

        $query = RechargeSetting::with('role');

        if ($roleId = $this->option('role')) {
            $query->where('role_id', $roleId);
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->warn('No recharge settings found.');

            return Command::SUCCESS;
        }

        foreach ($settings as $setting) {
            $this->info("Processing role: {$setting->role->name}");

            if (! $this->shouldRechargeNow($setting, $currentTime)) {
                $this->line('  - Skipped (not scheduled for this time)');

                continue;
            }

            $roleLimit = RoleLimit::where('role_id', $setting->role_id)->first();

            if (! $roleLimit) {
                $this->warn('  - No role limit configured, skipping');

                continue;
            }

            $targetBalance = $roleLimit->daily_point_limit;

            $this->line("  - Target balance: {$targetBalance} pts");

            $updated = $this->rechargeRole($setting->role_id, $targetBalance);

            $this->info("  - Recharged {$updated} employee(s)");
        }

        $this->info('Balance recharge completed.');

        return Command::SUCCESS;
    }

    /**
     * Determine if recharge should happen now based on settings.
     */
    protected function shouldRechargeNow(RechargeSetting $setting, string $currentTime): bool
    {
        return match ($setting->mode) {
            RechargeMode::Daily => $currentTime === $setting->recharge_time,
            RechargeMode::DualPeriod => $currentTime === $setting->breakfast_time
                || $currentTime === $setting->lunch_time,
            default => false,
        };
    }

    /**
     * Recharge all employee balances for a given role.
     */
    protected function rechargeRole(int $roleId, int $targetBalance): int
    {
        return DB::table('employee_balances')
            ->join('model_has_roles', 'employee_balances.user_id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.role_id', $roleId)
            ->where('model_has_roles.model_type', '=', User::class)
            ->update(['employee_balances.current_balance' => $targetBalance]);
    }
}
