<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * Display the admin attendance list view.
     *
     * @return \Illuminate\View\View
     */
    public function adminAttendanceList(Request $request)
    {
        $date = $request->input('date')
        ? Carbon::parse($request->input('date'))
        : Carbon::today();

        $previousDay = $date->copy()->subDay(1)->format('Y-m-d');

        $nextDay = $date->copy()->addDay(1)->format('Y-m-d');

        $staffs = User::where('role', 'staff')
            ->with(['attendances' => function ($query) use ($date) {
            $query->whereDate('worked_at', $date->toDateString())
            ->with('breakTimes');
            }])->get();

        return view('admin.attendance-list', compact('staffs', 'date', 'previousDay', 'nextDay'));
    }


    public function export(Request $request, $userId)
    {
        $targetUser = User::findOrFail($userId);

        $month = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('worked_at', [$startOfMonth, $endOfMonth])
            ->with('breakTimes')
            ->get();

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        $csvHeader = [
            '日付', '出勤', '退勤', '休憩', '合計',
        ];

        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        foreach ($period as $date) {

            $dateStr = $date->format('m/d') . '(' . $weekdays[$date->dayOfWeek] . ')';

            $attendance = $attendances->first(function ($item) use ($date) {
                return Carbon::parse($item->worked_at)->isSameDay($date);
            });

            $dateStr = $date->format('m/d') . '(' . $weekdays[$date->dayOfWeek] . ')';

            $csv[] = [
                $dateStr,
                $attendance?->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '',
                $attendance?->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '',
                $attendance?->formated_break_time ?? '',
                $attendance?->formated_work_time ?? '',
            ];
        }

        $fileName = "{$targetUser->name}_{$month->format('Y年m月')}_勤怠.csv";

        $response = new StreamedResponse(function () use ($csvHeader, $csv) {
            $createCsvFile = fopen('php://output', 'w');

            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader, $csv);

            fputcsv($createCsvFile, $csvHeader);
            foreach ($csv as $line) {
                fputcsv($createCsvFile, $line);
            }

        fclose($createCsvFile);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);

        return $response;
    }

}
