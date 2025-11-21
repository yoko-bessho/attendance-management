<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Database\Seeders\OrderStatusSeeder;
use Database\Seeders\TransactionStatusSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UsersTableSeeder;

class StampClockScreenTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }


    /**
     * @test
     * 現在の日時情報がUIと同じ形式で出力されている
     * 1. 勤怠打刻画面を開く
     * 2. 画面に表示されている日時情報を確認する
     */
    public function stampClock_currentDateTime_displayedInUISameFormat()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $currentDateTime = now();
        $expectedDate = $currentDateTime->format('Y年n月j日(') . ['日', '月', '火', '水', '木', '金', '土'][$currentDateTime->dayOfWeek] . ')';
        $expectedTime = $currentDateTime->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }

    /**
     * @test
     * 勤務外の場合、勤怠ステータスが正しく表示される
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function stampClock_offDutyStatus_displayedCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * @test
     * 出勤中の場合、勤怠ステータスが正しく表示される
     * 1. ステータスが出勤中のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function stampClock_onDutyStatus_displayedCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();

        $this->actingAs($user)->post('/work-in');
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩中の場合、勤怠ステータスが正しく表示される
     * 1. ステータスが休憩中のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function stampClock_onBreakStatus_displayedCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();
        $this->actingAs($user)->post('/work-in');
        $this->actingAs($user)->post('/break-start');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * @test
     * 退勤済の場合、勤怠ステータスが正しく表示される
     * 1. ステータスが退勤済のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function stampClock_offDutyCompletedStatus_displayedCorrectly()
    {
        $user = User::where('email', 'general1@example.com')->first();
        $this->actingAs($user)->post('/work-in');
        $this->actingAs($user)->post('/work-out');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

}
