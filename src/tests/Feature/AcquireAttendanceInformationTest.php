<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;

class AcquireAttendanceInformationTest extends TestCase
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
     * 自分が行った勤怠情報が全て表示されている
     * 1. 勤怠情報が登録されたユーザーにログインする
     * 2. 勤怠一覧ページを開く
     * 3. 自分の勤怠情報がすべて表示されていることを確認する
     */
    public function attendanceList_allAttendanceInformationDisplayed()
    {
        $this->actingAs($this->user);
        $attendances = $this->user->attendances()->get();

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->worked_at)->format('m/d');
            $startTime = $attendance->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '';
            $endTime = $attendance->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '';
            $breakTime = $attendance->formatted_break_time;
            $workTime = $attendance->formatted_work_time;

            $response->assertSee($date);
            $response->assertSee($startTime);
            $response->assertSee($endTime);
            $response->assertSee($breakTime);
            $response->assertSee($workTime);
        }
    }

    /**
     * @test
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     * 1. ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     */
    public function attendanceList_currentMonthDisplayedOnScreenTransition()
    {
        $this->actingAs($this->user);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        $currentMonth = Carbon::now()->format('Y/m');
        $response->assertSee($currentMonth);
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「前月」ボタンを押す
     */
    public function attendanceList_previousMonthDisplayedWhenPreviousMonthButtonPressed()
    {
        $this->actingAs($this->user);

        $previousMonth = Carbon::now()->subMonth()->format('Y-m');

        $url = route('attendance.list', ['month' => $previousMonth]);

        $response = $this->get($url);
        $response->assertStatus(200);

        $response->assertSee(Carbon::now()->subMonth()->format('Y/m'));

        $previousAttendances = $this->user->attendances()
            ->where('worked_at', 'like', "{$previousMonth}%")
            ->get();

        foreach ($previousAttendances as $attendance) {
            $response->assertSee(Carbon::parse($attendance->worked_at)->format('m/d'));
        }

        $otherAttendances = $this->user->attendances()
            ->where('worked_at', 'not like', "{$previousMonth}%")
            ->get();

        foreach ($otherAttendances as $attendance) {
            $response->assertDontSee(Carbon::parse($attendance->worked_at)->format('m/d'));
        }
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     * "1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「翌月」ボタンを押す
    */
    public function attendanceList_nextMonthDisplayedWhenNextMonthButtonPressed()
    {
        $this->actingAs($this->user);

        $nextMonth = Carbon::now()->addMonth()->format('Y-m');

        $url = route('attendance.list', ['month' => $nextMonth]);

        $response = $this->get($url);
        $response->assertStatus(200);

        $response->assertSee(Carbon::now()->addMonth()->format('Y/m'));

        $nextAttendances = $this->user->attendances()
            ->where('worked_at', 'like', "{$nextMonth}%")
            ->get();

        foreach ($nextAttendances as $attendance) {
            $response->assertSee(Carbon::parse($attendance->worked_at)->format('m/d'));
        }

        $otherAttendances = $this->user->attendances()
            ->where('worked_at', 'not like', "{$nextMonth}%")
            ->get();

        foreach ($otherAttendances as $attendance) {
            $response->assertDontSee(Carbon::parse($attendance->worked_at)->format('m/d'));
        }
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「詳細」ボタンを押下する
     */
    public function attendanceList_navigateToAttendanceDetailWhenDetailButtonPressed()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest();

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        $detailUrl = route('attendance.detail', ['date' => Carbon::parse($attendance->worked_at)->format('Y-m-d')]);

        $response = $this->get($detailUrl);
        $response->assertStatus(200);
    }

}
