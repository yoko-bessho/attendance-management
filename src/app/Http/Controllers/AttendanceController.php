<?php

namespace App\Http\Controllers;


use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        $statusLabel = Attendance::getUserStatus($user);

        return view('index', compact('now', 'weekdays', 'statusLabel'));
    }


    public function workIn()
    {
        $user = Auth::user();
        $oldTimestamp = Attendance::where('user_id', $user->id)
                            ->whereDate('worked_at', Carbon::today())
                            ->latest()
                            ->first();

        if (!$oldTimestamp) {
            Attendance::create([
                'user_id' => $user->id,
                'start_time' => Carbon::now(),
                'worked_at' => Carbon::today(),
            ]);
        }
        return redirect()->route('attendance');
    }


    public function workOut()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                            ->whereDate('worked_at', '<=', Carbon::today())
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        if ($attendance) {
            $attendance->update([
                'end_time' => Carbon::now(),
            ]);
        }

        return redirect()->route('attendance');
    }


    public function breakStart()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                            ->whereDate('worked_at', Carbon::today())
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        if (!$attendance) {
            return redirect()->route('attendance')->with('error', '先に出勤してください。');
        }

        $breakStart = $attendance->breakTimes()
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        if (!$breakStart) {
            $attendance->breakTimes()->create([
                'start_time' => Carbon::now(),
            ]);
        }
        return redirect()->route('attendance');
    }


    public function breakEnd()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                            ->whereDate('worked_at', Carbon::today())
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        if (!$attendance) {
            return redirect()->route('attendance')->with('error', '先に出勤してください。');
        }

        $breakEnd = $attendance->breakTimes()
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        if ($breakEnd) {
            $breakEnd->update([
                'end_time' => Carbon::now(),
            ]);
        }
        return redirect()->route('attendance');
    }


    public function attendanceList(Request $request, $id = null)
    {
        $targetUserId = $id ?? Auth::user()->id;
        $targetUser = User::find($targetUserId);

        $month = $request->input('month')
            ? Carbon::parse($request->input('month'))
            : Carbon::now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $targetUserId)
            ->whereBetween('worked_at', [$startOfMonth, $endOfMonth])
            ->with('breakTimes')
            ->get();

        $dates = collect();
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates->push($date->copy());
        }

        $attendanceMap = $attendances->keyBy(function ($attendance) {
            return Carbon::parse($attendance->worked_at)->format('Y-m-d');
        });

        $previousMonth = $month->copy()->subMonth();
        $nextMonth = $month->copy()->addMonth();

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        return view('attendance-list', compact('month', 'previousMonth', 'nextMonth', 'dates', 'attendanceMap', 'weekdays', 'targetUser'));
    }

    public function attendanceDetail($date, $user = null)
    {
        $isAdmin = Auth::user()->role === 'admin';
        $targetUser = $user ? User::find($user) : Auth::user();
        $attendance = Attendance::with('user', 'breakTimes')
            ->where('user_id', $targetUser->id)
            ->whereDate('worked_at', $date)
            ->first();

        $stampRequest = StampCorrectionRequest::where('user_id', $targetUser->id)
            ->where('request_date', $date)
            ->latest()
            ->first();

        $display = app(\App\Services\AttendanceDisplayService::class)
            ->build($attendance, $stampRequest);

        $mode = 'detail';

        return View('attendance-detail', array_merge([
            'attendance' => $attendance,
            'date' => $date,
            'stampRequest' => $stampRequest,
            'targetUser' => $targetUser,
            'mode' => $mode,
            'isAdmin' => $isAdmin,
        ], $display));

    }

}


