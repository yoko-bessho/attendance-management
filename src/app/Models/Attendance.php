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

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'worked_at' => 'datetime',
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


    public function getTotalBreakSecondsAttribute()
    {
        $totalBreakSeconds = 0;
        foreach ($this->breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $start = \Carbon\Carbon::parse($break->start_time);
                $end = \Carbon\Carbon::parse($break->end_time);
                $totalBreakSeconds += $start->diffInSeconds($end);
            }
        }
        return $totalBreakSeconds;
    }

    public function getFormattedBreakTimeAttribute()
    {
        return gmdate('G:i', $this->total_break_seconds);
    }

    public function getFormattedWorkTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return '0:00';
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        $totalWorkSeconds = $start->diffInSeconds($end);
        $actualWorkSeconds = $totalWorkSeconds - $this->total_break_seconds;
        
        if ($totalWorkSeconds < 0) {
            $actualWorkSeconds = 0;
        }

        return gmdate('G:i', $actualWorkSeconds);
    }


    public static function getUserStatus($user)
    {
        $statusLabel = [
            'work_in' => true,
            'work_out' => false,
            'break_start' => false,
            'break_end' => false,
            'message' => false,
        ];

        $attendance = self::where('user_id', $user->id)
                            ->whereDate('worked_at', Carbon::today())
                            ->whereNull('end_time')
                            ->latest()
                            ->first();

        if (!$attendance) {
            $attendance = self::where('user_id', $user->id)
                                ->whereDate('worked_at', Carbon::today())
                                ->latest()
                                ->first();
        }

        if ($attendance) {
            $statusLabel['work_in'] = false;

            if (is_null($attendance->end_time)) {
                $latestBreak = $attendance->breakTimes()->latest()->first();
                if ($latestBreak && is_null($latestBreak->end_time)) {
                    $statusLabel['break_end'] = true;
                } else {
                    $statusLabel['work_out'] = true;
                    $statusLabel['break_start'] = true;
                }
            } else {
                $statusLabel['message'] = true;
            }
        }
        
        return $statusLabel;
    }
}
