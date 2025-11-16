<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use Carbon\Carbon;

class BreakTimesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            $date = $attendance->worked_at->format('Y-m-d');

            $param = [
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse("$date . 12:00:00"),
            'end_time' => Carbon::parse("$date . 13:00:00"),
            ];

            DB::table('break_times')->insert($param);
        }
    }
}
