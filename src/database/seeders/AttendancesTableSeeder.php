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

        for ($i = 0; $i < 10; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');

            Attendance::create([
                'user_id' => $user->id,
                'worked_at' => Carbon::parse("$date"),
                'start_time' => Carbon::parse("$date . 09:00:00"),
                'end_time' => Carbon::parse("$date . 18:00:00"),
            ]);
        }
    }
}
