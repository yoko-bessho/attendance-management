<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'note',
        'worked_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }


    public static function getUserStatus($user)
    {
        // デフォルト値（勤務外）
        $statusLabel = [
            'work_in' => true,
            'work_out' => false,
            'break_start' => false,
            'break_end' => false,
            'message' => false,
        ];
        // まず「まだ終わっていない最新の勤務」を探す
        $attendance = self::where('user_id', $user->id)
                            ->whereDate('worked_at', Carbon::today())
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        // なければ「今日の最新の勤務」を探す（退勤済みの場合）
        if (!$attendance) {
            $attendance = self::where('user_id', $user->id)
                                ->whereDate('worked_at', Carbon::today())
                                ->latest()
                                ->first();
        }

        if ($attendance) {
            $statusLabel['work_in'] = false;

            if (is_null($attendance->end_time)) {
                // --- 勤務中 or 休憩中 ---
                $latestBreak = $attendance->breakTimes()->latest()->first();
                if ($latestBreak && is_null($latestBreak->end_time)) {
                    $statusLabel['break_end'] = true;
                } else {
                    $statusLabel['work_out'] = true;
                    $statusLabel['break_start'] = true;
                }
            } else {
                // --- 退勤済み ---
                $statusLabel['message'] = true;
            }
        }
        
        return $statusLabel;
    }
}
