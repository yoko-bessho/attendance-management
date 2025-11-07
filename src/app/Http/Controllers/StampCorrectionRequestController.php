<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use App\Enums\StampCorrectionRequestsStatus;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\BreakTime;

class StampCorrectionRequestController extends Controller
{
    public function requestCorrection(AttendanceCorrectionRequest $request, $date, $userId = null)
    {
        $targetUserId = $userId ?? Auth::user()->id;

        $attendance = Attendance::where('user_id', $targetUserId)
            ->whereDate('worked_at', $date)
            ->first();

        $breaks = collect($request->input('breaks', []))
        ->filter(fn ($break) => !empty($break['start_time']) && !empty($break['end_time']))
        ->values();

        $status = Auth::user()->role === 'admin'
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

        if (Auth::user()->role === 'admin') {
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
                // DBに保存されている休憩時間のIDリストを取得
                $existingBreakIds = $attendance->breakTimes()->pluck('id')->all();
                
                // リクエストされた休憩時間のIDリストを取得 ($breaksはリクエストから生成済み)
                $requestedBreakIds = $breaks->pluck('id')->filter()->all();

                // 削除すべき休憩時間IDを特定
                $deletableBreakIds = array_diff($existingBreakIds, $requestedBreakIds);
                if (!empty($deletableBreakIds)) {
                    BreakTime::destroy($deletableBreakIds);
                }

                foreach ($breaks as $break) {
                    $breakData = [
                        'start_time' => Carbon::parse("{$date} {$break['start_time']}"),
                        'end_time'   => Carbon::parse("{$date} {$break['end_time']}"),
                    ];

                    // id がある場合は更新、ない場合は作成
                    $attendance->breakTimes()->updateOrCreate(
                        ['id' => data_get($break, 'id')],
                        $breakData
                    );
                }
            }
        }

        $date = Carbon::parse($date)->format('Y-m-d');

        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.attendance.detail', [
                'user' => $targetUserId,
                'date' => $date,
            ])->with('success', '修正申請を登録し勤怠データに反映しました');
        }
        
        return redirect()->route('attendance.detail', [
            'date' => $date,
        ]);
    }


    public function requestList(Request $request)
    {
        $tab = $request->query('tab', 'pending');
        $status = StampCorrectionRequestsStatus::fromTab($tab);

        $user = Auth::user();
        $requests = StampCorrectionRequest::where('user_id', $user->id)
            ->whereDate('request_date', '<=', Carbon::today())
            ->whereDate('created_at', '>=', Carbon::now()->subMonth())
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('request-list', compact('requests', 'tab'));    }

}

