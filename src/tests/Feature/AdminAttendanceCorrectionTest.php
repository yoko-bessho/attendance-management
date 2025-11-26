<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceCorrectionTest extends TestCase
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
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function admin_can_view_selected_detailScreen()
    {
        $testNow = Carbon::create(2025, 11, 15);
        Carbon::setTestNow($testNow);

        $targetUser = User::find(2); // 一般ユーザー
        $targetDate = Carbon::yesterday();
        $targetDateString = $targetDate->format('Y-m-d');

        $attendance = Attendance::where('user_id', $targetUser->id)
                                ->whereDate('worked_at', $targetDate)
                                ->first();
        $this->assertNotNull($attendance, "Attendance for user {$targetUser->id} on {$targetDateString} not found.");

        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.attendance.detail', ['date' => $targetDateString, 'id' => $targetUser->id]));

        $response->assertStatus(200);
        $response->assertSee($targetDateString);
        $response->assertSee($targetUser->name);

        Carbon::setTestNow();
    }

    /**
     * @test
     * 管理者は出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function admin_cannot_set_start_time_after_end_time()
    {
        $targetUser = User::where('role', 'staff')->first();
        $attendance = $targetUser->attendances()->first();
        $date = $attendance->worked_at->format('Y-m-d');

        $this->actingAs($this->admin, 'admin');

        $correctionData = [
            'start_time' => '18:00',
            'end_time' => '09:00',
            'reason' => '管理者による修正（テスト）',
        ];

        $requestUrl = route('admin.modify.attendance', ['date' => $date, 'id' => $targetUser->id]);

        $response = $this->post($requestUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['end_time' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * @test
     * 管理者は休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function admin_cannot_set_break_start_time_after_end_time()
    {
        $targetUser = User::where('role', 'staff')->first();
        $attendance = $targetUser->attendances()->first();
        $break = $attendance->breakTimes()->first();
        $date = $attendance->worked_at->format('Y-m-d');

        $this->actingAs($this->admin, 'admin');

        $correctionData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                0 => [
                    'id' => $break->id,
                    'start_time' => '19:00', // 不正な値
                    'end_time' => '19:30',
                ]
            ],
            'reason' => '管理者による修正（テスト）',
        ];

        $requestUrl = route('admin.modify.attendance', ['date' => $date, 'id' => $targetUser->id]);

        $response = $this->post($requestUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['breaks.0.start_time' => '休憩時間が不適切な値です']);
    }

    /**
     * @test
     * 管理者は休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function admin_cannot_set_break_end_time_after_end_time()
    {
        $targetUser = User::where('role', 'staff')->first();
        $attendance = $targetUser->attendances()->first();
        $break = $attendance->breakTimes()->first();
        $date = $attendance->worked_at->format('Y-m-d');

        $this->actingAs($this->admin, 'admin');

        $correctionData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                0 => [
                    'id' => $break->id,
                    'start_time' => '17:00',
                    'end_time' => '18:30', // 不正な値
                ]
            ],
            'reason' => '管理者による修正（テスト）',
        ];

        $requestUrl = route('admin.modify.attendance', ['date' => $date, 'id' => $targetUser->id]);

        $response = $this->post($requestUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['breaks.0.end_time' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * @test
     * 管理者は備考欄が未入力の場合、エラーメッセージが表示される
     */
    public function admin_gets_error_if_reason_is_empty()
    {
        $targetUser = User::where('role', 'staff')->first();
        $attendance = $targetUser->attendances()->first();
        $date = $attendance->worked_at->format('Y-m-d');

        $this->actingAs($this->admin, 'admin');

        $correctionData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => '', // 備考を空にする
        ];

        $requestUrl = route('admin.modify.attendance', ['date' => $date, 'id' => $targetUser->id]);

        $response = $this->post($requestUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['reason' => '備考を記入してください']);
    }
}
