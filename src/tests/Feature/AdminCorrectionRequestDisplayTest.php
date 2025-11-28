<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Enums\StampCorrectionRequestsStatus;
use Carbon\Carbon;


class AdminCorrectionRequestDisplayTest extends TestCase
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
     * 管理者は承認待ちの修正申請が全て表示されていることを確認できる
     */
    public function admin_can_view_all_approved_correction_requests()
    {
        $staff1 = User::factory()->create(['role' => 'staff']);
        $staff2 = User::factory()->create(['role' => 'staff']);

        $attendance1 = Attendance::factory()->create(['user_id' => $staff1->id]);
        $attendance2 = Attendance::factory()->create(['user_id' => $staff2->id]);

        $request1 = StampCorrectionRequest::factory()->create([
            'user_id' => $staff1->id,
            'attendance_id' => $attendance1->id,
            'status' => StampCorrectionRequestsStatus::PENDING,
            'reason' => '申請理由1'
        ]);

        $request2 = StampCorrectionRequest::factory()->create([
            'user_id' => $staff2->id,
            'attendance_id' => $attendance2->id,
            'status' => StampCorrectionRequestsStatus::PENDING,
            'reason' => '申請理由2'
        ]);

        $approvedRequest = StampCorrectionRequest::factory()->create(['status' => StampCorrectionRequestsStatus::APPROVAL]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('request.list', ['tab' => 'pending']));

        $response->assertStatus(200);

        $response->assertSee($request1->user->name);
        $response->assertSee($request1->reason);
        $response->assertSee($request2->user->name);
        $response->assertSee($request2->reason);

        $response->assertDontSee($approvedRequest->reason);
    }

    /**
     * @test
     * 承認済みの修正申請が全て表示されている
     */
        public function admin_can_view_all_pending_correction_requests()
    {
        $staff1 = User::factory()->create(['role' => 'staff']);
        $staff2 = User::factory()->create(['role' => 'staff']);

        $attendance1 = Attendance::factory()->create(['user_id' => $staff1->id]);
        $attendance2 = Attendance::factory()->create(['user_id' => $staff2->id]);

        $request1 = StampCorrectionRequest::factory()->create([
            'user_id' => $staff1->id,
            'attendance_id' => $attendance1->id,
            'status' => StampCorrectionRequestsStatus::APPROVAL,
            'reason' => '申請理由1'
        ]);

        $request2 = StampCorrectionRequest::factory()->create([
            'user_id' => $staff2->id,
            'attendance_id' => $attendance2->id,
            'status' => StampCorrectionRequestsStatus::APPROVAL,
            'reason' => '申請理由2'
        ]);

        $pendingRequest = StampCorrectionRequest::factory()->create(['status' => StampCorrectionRequestsStatus::PENDING]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('request.list', ['tab' => 'approval']));

        $response->assertStatus(200);

        $response->assertSee($request1->user->name);
        $response->assertSee($request1->reason);
        $response->assertSee($request2->user->name);
        $response->assertSee($request2->reason);

        $response->assertDontSee($pendingRequest->reason);
    }

    /**
     * @test
     * 修正申請の詳細内容が正しく表示されている
     */
    public function admin_can_view_a_specific_correction_request_detail()
    {
        $request = StampCorrectionRequest::factory()->create([
            'request_date' => '2025-11-15',
            'revised_start_time' => '2025-11-15 09:30:00',
            'revised_end_time' => '2025-11-15 18:30:00',
            'reason' => '詳細確認テスト'
        ]);

        $yearString = Carbon::parse($request->revised_start_time)->format('Y年');
        $dateString = Carbon::parse($request->revised_start_time)->format('m月d日');

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('approval.form', [
            'attendance_correct_request_id' => $request->id
        ]));

        $response->assertStatus(200);

        $response->assertSee($request->user->name);
        $response->assertSee($yearString);
        $response->assertSee($dateString);
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('詳細確認テスト');
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われる
     */
    public function admin_can_approve_a_correction_request()
    {
        $request = StampCorrectionRequest::factory()->create([
            'status' => StampCorrectionRequestsStatus::PENDING,
            'revised_start_time' => '2025-11-15 09:00:00',
            'revised_end_time' => '2025-11-15 18:00:00',
        ]);
        $attendance = $request->attendance;

        $this->actingAs($this->admin, 'admin');

        $response = $this->patch(route('admin.approval', [
            'attendance_correct_request_id' => $request->id
        ]));

        $response->assertRedirect(route('admin.attendance.detail', [
            'date' => Carbon::parse($request->request_date)->format('Y-m-d'), 'id' => $request->user_id
        ]));

        $this->assertEquals('approval', $request->fresh()->status->value);

        $this->assertEquals('09:00:00', $attendance->fresh()->start_time->format('H:i:s'));
        $this->assertEquals('18:00:00', $attendance->fresh()->end_time->format('H:i:s'));
    }
}
