<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display the admin attendance list view.
     *
     * @return \Illuminate\View\View
     */
    public function showAttendanceList()
    {
        // 後ほど、ここにデータを取得するロジックを追加します
        return view('admin.attendance-list');
    }
}
