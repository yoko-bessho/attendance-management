<?php
namespace App\Services;

class AttendanceDisplayService
{
    public function build($attendance, $stampRequest)
    {
        $displayBreaks = optional(optional($attendance)->breakTimes)->map(function ($break) {
            return [
                'id' => $break->id,
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
            ];
        }) ?? collect();

        $displayStartTime = optional($attendance)->start_time;
        $displayEndTime = optional($attendance)->end_time;
        $displayReason = null;

        if ($stampRequest) {
            $jsonBreaks = collect(json_decode($stampRequest->revised_breaks, true)) ?? collect();

            $displayBreaks = $jsonBreaks->map(function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                ];
            });

        $displayStartTime = $stampRequest->revised_start_time;
        $displayEndTime = $stampRequest->revised_end_time;
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