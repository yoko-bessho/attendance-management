<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use App\Enums\StampCorrectionRequestsStatus;
use App\Http\Requests\AttendanceCorrectionRequest;

class StampCorrectionRequestController extends Controller
{
        public function requestCorrection(AttendanceCorrectionRequest $request, $date)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('worked_at', $date)
            ->first();

        $breaks = collect($request->input('breaks', []))->filter(function ($break) {
            return !empty($break['start_time']) && !empty($break['end_time']);
        })->values();

        StampCorrectionRequest::create([
            'attendance_id' => optional($attendance)->id,
            'user_id' => Auth::id(),
            'request_date' => $date,
            'reason' => $request->input('reason'),
            'revised_start_time' => $request->filled('start_time')
                ? Carbon::createFromFormat('Y-m-d H:i', "{$date} {$request->start_time}")
                : null,
            'revised_end_time' => $request->filled('end_time')
                ? Carbon::createFromFormat('Y-m-d H:i', "{$date} {$request->end_time}")
                : null,
            'revised_breaks' => $breaks->isNotEmpty() ? $breaks->toJson() : null,
            'status' => StampCorrectionRequestsStatus::PENDING,
        ]);

        return redirect()->route('attendance.list')->with('success', '修正申請を送信しました。');
    }

}
