<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Attendance;
use App\Enums\StampCorrectionRequestsStatus;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }


    protected $fillable = [
        'attendance_id',
        'user_id',
        'request_date',
        'reason',
        'revised_start_time',
        'revised_end_time',
        'revised_breaks',
        'status',
    ];

    protected $casts = [
        'status' => StampCorrectionRequestsStatus::class,
    ];
}
