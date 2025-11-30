<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\StampCorrectionRequest;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;

class AttendanceCorrectionTest extends TestCase
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
     *出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function attendanceCorrection_startTimeAfterEndTime_errorMessageDisplayed()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();

        $detailUrl = route('attendance.detail', ['date' => Carbon::parse($attendance->worked_at)->format('Y-m-d')]);
        $response = $this->get($detailUrl);
        $response->assertStatus(200);

        $correctionData = [
            'attendance_id' => $attendance->id ?? '',
            'user_id' => $this->user->id,
            'request_date' => Carbon::parse($attendance->worked_at)->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '09:00',
            'reason' => '修正理由',
        ];
        $requestlUrl = route('attendance.request', ['date' => Carbon::parse($attendance->worked_at)->format('Y-m-d')]);

        $response = $this->post($requestlUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['end_time' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function attendanceCorrection_breakStartTimeAfterAttendanceEndTime_errorMessageDisplayed()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();

        $break = $attendance->breakTimes()->first();

        $detailUrl = route('attendance.detail', ['date' => $attendance->worked_at->format('Y-m-d')]);

        $this->get($detailUrl)->assertStatus(200);

        $correctionData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                0 => [
                    'id' => $break->id,
                    'start_time' => '19:00',
                    'end_time' => '19:30',
                ]
            ],
            'reason' => '修正理由',
        ];
        $requestUrl = route('attendance.request', ['date' => $attendance->worked_at->format('Y-m-d')]);

        $response = $this->post($requestUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['breaks.0.start_time' => '休憩時間が不適切な値です']);
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function attendanceCorrection_breakEntTimeAfterAttendanceEndTime_errorMessageDisplayed()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();

        $break = $attendance->breakTimes()->first();

        $detailUrl = route('attendance.detail', ['date' => $attendance->worked_at->format('Y-m-d')]);

        $this->get($detailUrl)->assertStatus(200);

        $correctionData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                0 => [
                    'id' => $break->id,
                    'start_time' => '18:00',
                    'end_time' => '19:00',
                ]
            ],
            'reason' => '修正理由',
        ];
        $requestUrl = route('attendance.request', ['date' => $attendance->worked_at->format('Y-m-d')]);

        $response = $this->post($requestUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['breaks.0.end_time' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function attendanceCorrection_reason_errorMessageDisplayed()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->first();

        $detailUrl = route('attendance.detail', ['date' => $attendance->worked_at->format('Y-m-d')]);

        $this->get($detailUrl)->assertStatus(200);

        $correctionData = [
            'start_time' => '10:00',
            'end_time' => '18:00',
            'reason' => '',
        ];

        $requestlUrl = route('attendance.request', ['date' => $attendance->worked_at->format('Y-m-d')]);

        $response = $this->post($requestlUrl, $correctionData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['reason' => '備考を記入してください']);
    }

    /**
     * @test
     * 修正申請処理が実行される
     */

    public function correctionRequest_processing_is_executed()
    {
        $this->actingAs($this->user);
        $attendance = $this->user->attendances()->first();
        $date = $attendance->worked_at->format('Y-m-d');

        $correctionData = [
            'start_time' => '10:00',
            'end_time' => '18:00',
            'reason' => '申請理由',
        ];

        $requestUrl = route('attendance.request', ['date' => $date]);
        $this->post($requestUrl, $correctionData);

        $stampRequest = StampCorrectionRequest::latest()->first();
        $this->assertNotNull($stampRequest);

        $adminUser = User::where('email', 'admin@example.com')->first();
        $this->actingAs($adminUser);

        $approvalFormUrl = route('approval.form', ['attendance_correct_request_id' => $stampRequest->id]);
        $response = $this->get($approvalFormUrl);

        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('18:00');
        $response->assertSee('申請理由');
    }

    /**
     * @test
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function allRequests_submitted_by_logginUsers_are_displayed_pending()
    {
        $this->actingAs($this->user);
        $attendance = $this->user->attendances()->latest()->first();
        $date = $attendance->worked_at->format('Y-m-d');

        $correctionData = [
            'start_time' => '09:30',
            'end_time' => '18:30',
            'reason' => 'テスト申請理由',
        ];

        $requestUrl = route('attendance.request', ['date' => $date]);
        $this->post($requestUrl, $correctionData);
        $requestListlUrl = route('request.list');

        $response = $this->get($requestListlUrl);
        $response->assertStatus(200);

        $response->assertSee('承認待ち');
        $response->assertSee(Carbon::parse($date)->format('Y/m/d'));
        $response->assertSee($this->user->name);
        $response->assertSee('テスト申請理由');
    }

    /**
     * @test
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function allRequests_approved_by_admin_are_displayed_under_approval()
    {
        $this->actingAs($this->user);
        $attendance = $this->user->attendances()->latest()->first();
        $date = $attendance->worked_at->format('Y-m-d');
        $correctionData = [
            'start_time' => '08:30',
            'end_time' => '18:30',
            'reason' => 'テスト申請理由',
        ];
        $requestUrl = route('attendance.request', ['date' => $date]);
        $this->post($requestUrl, $correctionData);

        $stampRequest = StampCorrectionRequest::latest()->first();
        $this->assertNotNull($stampRequest);

        // --- 管理者による承認 ---
        $adminUser = User::where('email', 'admin@example.com')->first();
        $this->actingAs($adminUser, 'admin');
        $approvalUrl = route('admin.approval', ['attendance_correct_request_id' => $stampRequest->id]);
        $response = $this->patch($approvalUrl);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertEquals('approval', $stampRequest->fresh()->status->value, "Failed to update status to 'approval'.");

        // --- 一般ユーザーによる確認 ---
        $this->actingAs($this->user);
        $requestListUrl = route('request.list', ['tab' => 'approval']);
        $response = $this->get($requestListUrl);

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee(Carbon::parse($date)->format('Y/m/d'));
        $response->assertSee($this->user->name);
        $response->assertSee('テスト申請理由');
    }

    /**
     * @test
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function clicking_DetailsButton_take_you_to_attendance_detailScreen()
    {
        $this->actingAs($this->user);

        $attendance = $this->user->attendances()->latest()->first();
        $date = $attendance->worked_at->format('Y-m-d');
        $correctionData = [
            'start_time' => '08:00',
            'end_time' => '19:00',
            'reason' => 'テスト申請理由',
        ];
        $requestUrl = route('attendance.request', ['date' => $date]);
        $this->post($requestUrl, $correctionData);

        $stampRequest = StampCorrectionRequest::where('user_id', $this->user->id)
            ->where('request_date', $date)
            ->latest()
            ->first();

        $this->assertNotNull($stampRequest);

        $detailUrl = route('attendance.detail', ['date' => $stampRequest->request_date]);
        $response = $this->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee('08:00');
    }
}