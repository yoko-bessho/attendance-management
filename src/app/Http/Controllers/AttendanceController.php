<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
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

    public function workIn(Request $request)
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
}
