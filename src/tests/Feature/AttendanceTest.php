<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @test
     * 出勤ボタンが正しく機能する
     */
    public function stampClock_workInButton_functionsCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        $this->post('/work-in');
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /**
     * @test
     * 出勤は一日一回のみできる
     */
    public function stampClock_multipleWorkIns_notPermitted()
    {
        $user = User::where('email', 'general1@example.com')->first();
        $this->actingAs($user);
        $this->post('/work-in');
        $this->post('/work-out');
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertDontSee('出勤');
    }

    /**
     * @test
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function stampClock_workInTime_displayedInAttendanceList()
    {
        $today = Carbon::today();
        Carbon::setTestNow($today->copy()->setTime(9, 0, 0));

        $user = User::where('email', 'general1@example.com')->first();
        $this->actingAs($user);
        $this->post('/work-in');
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('09:00');
    }

    /**
     * @test
     * 休憩ボタンが正しく機能する
     */
    public function stampClock_breakButton_functionsCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $this->actingAs($user)->post('/work-in');
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $this->actingAs($user)->post('/break-start');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');
    }

    /**
     * @test
     * 休憩は一日に何回でもできる
     */
    public function stampClock_multipleBreaks_permitted()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $this->actingAs($user);
        $this->post('/work-in');
        $this->post('/break-start');
        $this->post('/break-end');
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩入');
    }
    /**
     * @test
     * 休憩戻ボタンが正しく機能する
     */
    public function stampClock_breakEndButton_functionsCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $this->actingAs($user);
        $this->post('/work-in');
        $this->post('/break-start');
        $this->post('/break-end');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');
    }

    /**
     * @test
     * 休憩戻は一日に何回でもできる
     */
    public function stampClock_multipleBreakEnds_permitted()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $this->actingAs($user);
        $this->post('/work-in');
        $this->post('/break-start');
        $this->post('/break-end');
        $this->post('/break-start');

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩戻');
    }

    /**
     * @test
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function stampClock_breakTime_displayedInAttendanceList()
    {
        $today = Carbon::today();
        Carbon::setTestNow($today->copy()->setTime(9, 0, 0));

        $user = User::where('email', 'general1@example.com')->first();

        $this->actingAs($user);
        $this->post('/work-in');
        $this->post('/break-start');

        Carbon::setTestNow($today->copy()->setTime(9, 30, 0));
        $this->post('/break-end');

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('0:30');
    }

    /**
     * @test
     * 退勤ボタンが正しく機能する
     */
    public function stampClock_workOutButton_functionsCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();
        $this->actingAs($user)->post('/work-in');

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $this->post('/work-out');
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    /**
     * @test
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function stampClock_workOutTime_displayedInAttendanceList()
    {
        $today = Carbon::today();
        Carbon::setTestNow($today->copy()->setTime(9, 0, 0));

        $user = User::where('email', 'general1@example.com')->first();
        $this->actingAs($user);
        $this->post('/work-in');

        Carbon::setTestNow($today->copy()->setTime(18, 0, 0));
        $this->post('/work-out');

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('18:00');
    }
}
