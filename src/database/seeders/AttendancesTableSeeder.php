<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where('role', 'staff')->first();
        $baseDate = Carbon::create(2025, 11, 15);

        for ($i = 1; $i < 31; $i++) {
            $date = $baseDate->copy()->subDays($i);

            Attendance::create([
                'user_id' => $user->id,
                'worked_at' => $date,
                'start_time' => $date->copy()->setTime(9, 0, 0),
                'end_time' => $date->copy()->setTime(18, 0, 0),
            ]);
        }
    }
}
