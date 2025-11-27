<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@example.com')->first();
    }

    /**
     * @test
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function admin_can_view_all_staff_attendance()
    {
        $today = Carbon::today();
        $staffs = User::where('role', 'staff')->get();

        foreach ($staffs as $staff) {
            $attendance = Attendance::factory()->create([
                'user_id' => $staff->id,
                'worked_at' => $today,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ]);

            BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);
        }

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list', ['date' => $today->format('Y-m-d')]));

        $response->assertStatus(200);

        foreach ($staffs as $staff) {
            $response->assertSee($staff->name);
        }
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
    }

    /**
     * @test
     * 遷移した際に現在の日付が表示される
     */
    public function admin_can_view_currentDate()
    {
        $today = Carbon::today();

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list'));

        $response->assertStatus(200);
        $response->assertSee($today->format('Y-m-d'));
    }

    /**
     * @test
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function admin_can_view_previous_day_of_attendanceInformation()
    {
        $testNow = Carbon::create(2025, 11, 15);
        Carbon::setTestNow($testNow);

        $previousDay = Carbon::yesterday()->format('Y-m-d');

        $attendance = Attendance::firstWhere('worked_at', $previousDay);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list',['date' => $previousDay]));

        $response->assertStatus(200);
        $response->assertSee($previousDay);
        $response->assertSee($attendance->start_time->format('H:i'));
        $response->assertSee($attendance->end_time->format('H:i'));

        Carbon::setTestNow();
    }

    /**
     * @test
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function admin_can_view_next_day_of_attendanceInformation()
    {
        //  Seederが作成する日付範囲内の日付を基準にする
        $baseDate = Carbon::create(2025, 11, 13);
        Carbon::setTestNow($baseDate);

        $nextDay = Carbon::tomorrow();
        $nextDayString = $nextDay->format('Y-m-d');

        $attendance = Attendance::whereDate('worked_at', $nextDay)->first();
        $this->assertNotNull($attendance);

        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.attendance.list', ['date' => $nextDayString]));

        $response->assertStatus(200);
        $response->assertSee($nextDayString);
        $response->assertSee($attendance->user->name);
        $response->assertSee($attendance->start_time->format('H:i'));
        $response->assertSee($attendance->end_time->format('H:i'));

        Carbon::setTestNow();
    }

}
