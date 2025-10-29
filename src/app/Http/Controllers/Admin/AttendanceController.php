<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StampCorrectionRequest;
use App\Enums\StampCorrectionRequestsStatus;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    /**
     * Display the admin attendance list view.
     *
     * @return \Illuminate\View\View
     */
    public function adminAttendanceList(Request $request)
    {
        $date = $request->input('date')
        ? Carbon::parse($request->input('date'))
        : Carbon::today();

        $previousDay = $date->copy()->subDay(1)->format('Y-m-d');

        $nextDay = $date->copy()->addDay(1)->format('Y-m-d');

        $staffs = User::where('role', 'staff')
            ->with(['attendances' => function ($query) use ($date) {
            $query->whereDate('worked_at', $date->toDateString())
            ->with('breakTimes');
            }])->get();

        return view('admin.attendance-list', compact('staffs', 'date', 'previousDay', 'nextDay'));
    }

    public function adminAttendanceDetail(User $user, $date)
    {
        $attendance = Attendance::with('user', 'breakTimes', 'stampCorrectionRequests')
            ->where('user_id', $user->id)
            ->whereDate('worked_at', $date)
            ->first();

        $pendingRequest = null;
        if ($attendance && $attendance->stampCorrectionRequests->isNotEmpty()) {
            $pendingRequest = $attendance->stampCorrectionRequests->where('status', StampCorrectionRequestsStatus::PENDING)->first();
        }

        $date = Carbon::parse($date);

        return view('attendance-detail', compact('user', 'attendance', 'date', 'pendingRequest'));
    }
}
