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
    // 勤怠修正申請、管理者による修正の場合そのまま登録処理
    public function requestCorrection(AttendanceCorrectionRequest $request, $date, $id = null)
    {
        $targetUserId = $id ?? Auth::user()->id;

        $attendance = Attendance::where('user_id', $targetUserId)
            ->whereDate('worked_at', $date)
            ->first();

        $breaks = collect($request->input('breaks', []))
        ->filter(fn ($break) => !empty($break['start_time']) && !empty($break['end_time']))
        ->values();

        $isAdmin = Auth::user()->role === 'admin';
        $status = $isAdmin
            ? StampCorrectionRequestsStatus::APPROVAL
            : StampCorrectionRequestsStatus::PENDING;

        $reason = $request->input('reason') . (Auth::user()->role === 'admin' ? '(管理者入力:' . Auth::user()->name . ')' : '');

        $stampRequest = StampCorrectionRequest::create([
            'attendance_id' => optional($attendance)->id,
            'user_id' => $targetUserId,
            'request_date' => $date,
            'reason' => $reason,
            'revised_start_time' => $request->filled('start_time')
                ? Carbon::createFromFormat('Y-m-d H:i', "{$date} {$request->start_time}")
                : null,
            'revised_end_time' => $request->filled('end_time')
                ? Carbon::createFromFormat('Y-m-d H:i', "{$date} {$request->end_time}")
                : null,
            'revised_breaks' => $breaks->isNotEmpty() ? $breaks->toJson() : null,
            'status' => $status,
        ]);

        if ($isAdmin) {
            Attendance::updateOrCreate(
                [
                    'user_id' => $targetUserId,
                    'worked_at' => $stampRequest->request_date,
                ],
                [
                    'start_time' => $stampRequest->revised_start_time,
                    'end_time' => $stampRequest->revised_end_time,
                ]
            );

            if ($attendance) {
                foreach ($breaks as $break) {
                    $attendance->breakTimes()->updateOrCreate(
                        ['id' => data_get($break, 'id')],
                        [
                            'start_time' => Carbon::parse("{$date} {$break['start_time']}"),
                            'end_time' => Carbon::parse("{$date} {$break['end_time']}"),
                        ]
                    );
                }
            }

            return redirect()->route('admin.attendance.detail', [
                'id' => $targetUserId,
                'date' => Carbon::parse($date)->format('Y-m-d'),
            ])
                ->with('success', '勤怠修正を登録しました');
        }

        return redirect()->route('attendance.detail', [
            'id' => $targetUserId,
            'date' => Carbon::parse($date)->format('Y-m-d'),
        ]);
    }


    public function requestList(Request $request)
    {
        $tab = $request->query('tab', 'pending');
        $status = StampCorrectionRequestsStatus::fromTab($tab);

        $user = Auth::user();

        if ($user->role === 'admin') {
            $requests = StampCorrectionRequest::with('user')
                ->whereDate('request_date', '<=', Carbon::today())
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $requests = StampCorrectionRequest::where('user_id', $user->id)
                ->whereDate('request_date', '<=', Carbon::today())
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('request-list', compact('requests', 'tab'));
    }


    public function approvalForm($attendance_correct_request_id)
    {
        $stampRequest = StampCorrectionRequest::with('user', 'attendance.breakTimes')->find($attendance_correct_request_id);

        $attendance = Attendance::where('user_id', $stampRequest->user_id)
            ->whereDate('worked_at', $stampRequest->request_date)
            ->first();
        $display = app(\App\Services\AttendanceDisplayService::class)
            ->build($attendance, $stampRequest);

        $isAdmin = Auth::user()->role === 'admin';

        return view('attendance-detail',array_merge([
            'mode' => 'approval',
            'isAdmin' => $isAdmin,
            'stampRequest' => $stampRequest,
            'attendance' => $attendance,
            'date' => $stampRequest->request_date,
            'targetUser' => $stampRequest->user,
            'actionRoute' => route('admin.approval', [
                'attendance_correct_request_id' => $stampRequest->id,
            ]),
            'isApprovalForm' => true,
        ], $display));

    }


    public function approval($attendance_correct_request_id)
    {
        $stampRequest = StampCorrectionRequest::findOrFail($attendance_correct_request_id);

        Attendance::updateOrCreate(
            [
                'user_id' => $stampRequest->user_id,
                'worked_at' => $stampRequest->request_date,
            ],
            [
                'start_time' => $stampRequest->revised_start_time,
                'end_time' => $stampRequest->revised_end_time,
            ]
        );

        if ($stampRequest->revised_breaks) {
            $breaks = json_decode($stampRequest->revised_breaks, true);
            $attendance = $stampRequest->attendance;

            if ($attendance) {
                foreach ($breaks as $break) {
                    $breakId = data_get($break, 'id');
                    $breakData = [
                        'start_time' => Carbon::parse("{$stampRequest->request_date} {$break['start_time']}"),
                        'end_time' => Carbon::parse("{$stampRequest->request_date} {$break['end_time']}"),
                    ];

                    if ($breakId) {
                        $attendance->breakTimes()->where('id', $breakId)->update($breakData);
                    } else {
                        $attendance->breakTimes()->create($breakData);
                    }
                }
            }
        }
        $stampRequest->status = StampCorrectionRequestsStatus::APPROVAL;
        $stampRequest->save();

        return redirect()->route('admin.attendance.detail', [
            'date' => $stampRequest->request_date, 'id' => $stampRequest->user_id])->with('success', '勤怠修正申請を承認し勤怠データに反映しました');
    }
}

