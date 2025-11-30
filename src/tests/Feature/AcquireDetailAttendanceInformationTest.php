<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;

class AcquireDetailAttendanceInformationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::where('email', 'general1@example.com')->first();
    }

    /**
     * @test
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function attendanceDetail_nameIsLoginUserName()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();

        $detailUrl = route('attendance.detail', ['date' => Carbon::parse($attendance->worked_at)->format('Y-m-d')]);

        $response = $this->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    /**
     * @test
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function attendanceDetail_dateIsSelectedDate()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();

        $detailUrl = route('attendance.detail', ['date' => Carbon::parse($attendance->worked_at)->format('Y-m-d')]);

        $response = $this->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee(Carbon::parse($attendance->worked_at)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->worked_at)->format('m月d日'));
    }

    /**
     * @test
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function attendanceDetail_startEndTimeIsLoginUserStampTime()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();

        $detailUrl = route('attendance.detail', ['date' => Carbon::parse($attendance->worked_at)->format('Y-m-d')]);

        $response = $this->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee(Carbon::parse($attendance->start_time)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance->end_time)->format('H:i'));
    }

    /**
     * @test
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function attendanceDetail_breakTimeIsLoginUserStampTime()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();

        $detailUrl = route('attendance.detail', ['date' => Carbon::parse($attendance->worked_at)->format('Y-m-d')]);

        $response = $this->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee($attendance->formated_break_time);
    }

}
