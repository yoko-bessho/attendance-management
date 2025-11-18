<?php
namespace App\Services;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;

class AttendanceDisplayService
{
    public function build($attendance, $stampRequest)
    {
        $displayStartTime = optional($attendance)->start_time;
        $displayEndTime = optional($attendance)->end_time;
        $displayBreaks = $attendance->breakTimes ?? collect();
        $displayReason = '';

        if($stampRequest) {
            $displayStartTime = $stampRequest->revised_start_time;
            $displayEndTime = $stampRequest->revised_end_time;
            $displayBreaks = collect(json_decode($stampRequest->revised_breaks, true)) ?? collect();
            $displayReason = $stampRequest->reason;
        }

        return [
            'displayStartTime' => $displayStartTime,
            'displayEndTime' => $displayEndTime,
            'displayBreaks' => $displayBreaks,
            'displayReason' => $displayReason,
        ];
    }
}