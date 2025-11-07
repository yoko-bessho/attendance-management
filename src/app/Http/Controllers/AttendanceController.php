<?php

namespace App\Http\Controllers;


use App\Enums\StampCorrectionRequestsStatus;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
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


    public function attendanceList(Request $request)
    {
        $month = $request->input('month')
            ? Carbon::parse($request->input('month'))
            : Carbon::now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', Auth::id())
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

        return view('attendance-list', compact('month', 'previousMonth', 'nextMonth', 'dates', 'attendanceMap', 'weekdays'));
    }

    public function attendanceDetail($date, $user = null)
    {
        $user = $user ? User::find($user) : Auth::user();
        $attendance = Attendance::with('user', 'breakTimes')
            ->where('user_id', $user->id)
            ->whereDate('worked_at', $date)
            ->first();

        $stampRequest = StampCorrectionRequest::where('user_id', $user->id)
            ->where('request_date', $date)
            ->latest()
            ->first();

        $displayStartTime = optional($attendance)->start_time;
        $displayEndTime = optional($attendance)->end_time;
        $displayBreaks = optional($attendance)->breakTimes ?? collect();
        $displayReason = '';
        $disabled = false;

        if($stampRequest) {
            if ($stampRequest->status == StampCorrectionRequestsStatus::PENDING) {
                $displayStartTime = $stampRequest->revised_start_time;
                $displayEndTime = $stampRequest->revised_end_time;
                $displayBreaks = collect(json_decode($stampRequest->revised_breaks, true) ?? []);
                $displayReason = $stampRequest->reason;
                $disabled = true;
            } else {
                $displayReason = $stampRequest->reason;
                $disabled = false;
            }
        }

        return view('attendance-detail', compact('attendance', 'date', 'stampRequest', 'displayStartTime' , 'displayEndTime', 'displayBreaks', 'displayReason', 'disabled', 'user'));
    }

}


