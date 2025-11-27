<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AdminStaffInformationAcquisitionTest extends TestCase
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
     * 管理者は全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function admin_can_view_all_staff_information()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.staff.list'));

        $response->assertStatus(200);

        $staffs = User::where('role', 'staff')->get();

        foreach ($staffs as $staff) {
            $response->assertSee($staff->name);
            $response->assertSee($staff->email);
        }

        $response->assertDontSee($this->admin->name);
    }

    /**
     * @test
     * ユーザーの勤怠情報が正しく表示される
     */
    public function admin_can_view_a_specific_staff_monthly_attendance()
    {
        $targetStaff = User::where('role', 'staff')->first();
        $this->assertNotNull($targetStaff);

        // Seederが2025-11-15を基準にデータを作成するため、その月を指定
        $targetMonth = '2025-11';
        $attendances = $targetStaff->attendances()->where('worked_at', 'like', $targetMonth . '%')->get();
        $this->assertNotEmpty($attendances, 'Test requires seeded attendance data for the target month.');

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.staff.list', [
            'id' => $targetStaff->id,
            'month' => $targetMonth
        ]));

        $response->assertStatus(200);
        $response->assertSee($targetStaff->name);
        $response->assertSee('2025/11');

        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->worked_at->format('m/d'));
            $response->assertSee($attendance->start_time->format('H:i'));
            $response->assertSee($attendance->end_time->format('H:i'));
        }
    }


    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function admin_can_view_previous_month_attendance_for_a_staff()
    {
        $baseDate = Carbon::create(2025, 11, 15);
        Carbon::setTestNow($baseDate);

        $targetStaff = User::where('role', 'staff')->first();
        $this->assertNotNull($targetStaff);

        $baseMonth = Carbon::now()->startOfMonth();
        $previousMonth = $baseMonth->copy()->subMonth();

        //データ存在確認
        $attendancesInPreviousMonth = $targetStaff->attendances()->where('worked_at', 'like', $previousMonth->format('Y-m') . '%')->get();
        $this->assertNotEmpty($attendancesInPreviousMonth);

        $attendancesInBaseMonth = $targetStaff->attendances()->where('worked_at', 'like', $baseMonth->format('Y-m') . '%')->get();
        $this->assertNotEmpty($attendancesInBaseMonth);

        //当月ページから前月ページへ
        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.attendance.staff.list', [
            'id' => $targetStaff->id,
            'month' => $baseMonth->format('Y-m')
        ]));
        $response->assertStatus(200);

        $previousUrl = route('admin.attendance.staff.list', [
            'id' => $targetStaff->id,
            'month' => $previousMonth->format('Y-m')
        ]);
        $response = $this->get($previousUrl);
        $response->assertStatus(200);
        $response->assertSee($targetStaff->name);
        $response->assertSee('2025/10'); // 前月が表示されている

        foreach ($attendancesInPreviousMonth as $attendance) {
            $response->assertSee($attendance->worked_at->format('m/d'));
        }

        foreach ($attendancesInBaseMonth as $attendance) {
            $response->assertDontSee($attendance->worked_at->format('m/d'));
        }
        Carbon::setTestNow();

    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function admin_can_view_next_month_attendance_for_a_staff()
    {
        $baseDate = Carbon::create(2025, 10, 15);
        Carbon::setTestNow($baseDate);

        $targetStaff = User::where('role', 'staff')->first();
        $this->assertNotNull($targetStaff);

        $baseMonth = Carbon::now()->startOfMonth();
        $nextMonth = $baseMonth->copy()->addMonth();

        //データ存在確認
        $attendancesInNextMonth = $targetStaff->attendances()->where('worked_at', 'like', $nextMonth->format('Y-m') . '%')->get();
        $this->assertNotEmpty($attendancesInNextMonth);

        $attendancesInBaseMonth = $targetStaff->attendances()->where('worked_at', 'like', $baseMonth->format('Y-m') . '%')->get();
        $this->assertNotEmpty($attendancesInBaseMonth);

        //当月ページから翌月ページへ
        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.attendance.staff.list', [
            'id' => $targetStaff->id,
            'month' => $baseMonth->format('Y-m')
        ]));
        $response->assertStatus(200);

        $nextUrl = route('admin.attendance.staff.list', [
            'id' => $targetStaff->id,
            'month' => $nextMonth->format('Y-m')
        ]);
        $response = $this->get($nextUrl);
        $response->assertStatus(200);
        $response->assertSee($targetStaff->name);
        $response->assertSee('2025/11'); // 翌月が表示されている

        foreach ($attendancesInNextMonth as $attendance) {
            $response->assertSee($attendance->worked_at->format('m/d'));
        }

        foreach ($attendancesInBaseMonth as $attendance) {
            $response->assertDontSee($attendance->worked_at->format('m/d'));
        }
        Carbon::setTestNow();

    }

    /**
     * @test
     * 管理者は「詳細」ボタンでその日の勤怠詳細画面に遷移できる
     */
    public function admin_can_navigate_to_detail_page_from_staff_list()
    {
        $targetStaff = User::where('role', 'staff')->first();
        $this->assertNotNull($targetStaff);

        $attendance = $targetStaff->attendances()->first();
        $this->assertNotNull($attendance);
        $date = $attendance->worked_at->format('Y-m-d');

        $this->actingAs($this->admin, 'admin');

        $detailUrl = route('admin.attendance.detail', [
            'date' => $date,
            'id' => $targetStaff->id,
        ]);
        $response = $this->get($detailUrl);

        $response->assertStatus(200);
        $response->assertSee($targetStaff->name);
        $response->assertSee($date);
        $response->assertSee($attendance->start_time->format('H:i'));
    }
}