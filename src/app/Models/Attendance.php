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
        $today = Carbon::today();
        $attendance = self::where('user_id', $user->id)
                            ->whereDate('worked_at', $today)
                            ->latest()
                            ->first();

        $statusLabel = [
            'work_in' => true,
            'work_out' => false,
            'break_start' => false,
            'break_end' => false,
            'work_finished' => false,
        ];

        if ($attendance) {
            $statusLabel['work_in'] = false;

            if (is_null($attendance->end_time)) {
                $statusLabel['work_out'] = true;

                $latestBreak = BreakTime::where('attendance_id', $attendance->id)
                                        ->latest()
                                        ->first();

                if ($latestBreak && is_null($latestBreak->end_time)) {
                    $statusLabel['break_end'] = true;
                } else {
                    $statusLabel['break_start'] = true;
                }
            } else {
                $statusLabel['work_finished'] = true;
            }
        }
        return $statusLabel;
    }
}
