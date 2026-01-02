<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\RecurringSchedule;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RecurringScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@example.com')->first();

        if ($user) {
            RecurringSchedule::create([
                'user_id' => $user->id,
                'order_type' => 'subscription',
                'schedule_monday' => true,
                'start_date' => Carbon::today(),
                'default_bags' => 2,
                'notes' => 'Gate code #1234',
                'active' => true,
            ]);
        }
    }
}
