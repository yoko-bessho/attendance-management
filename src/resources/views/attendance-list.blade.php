@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('nav')
    @if (Auth::check())
        @component('components.nav')
        @endcomponent
    @endif
@endsection

@section('content')
<div class="attendance__container">
    <div class="attendance__header">
        <h1 class="header__title">勤怠一覧</h1>
    </div>
    <div class="month-selector">
        <a href="{{ route('attendance.list', ['month' => $previousMonth->format('Y-m')]) }}" class="month-btn"><img class="previous-arrow" src="{{ asset('img/arrow.png') }}" alt="arrow"> 前月</a>
        <input type="month" class="month-input"
        name="month"
        value="{{ $month->format('Y-m') }}">
        <a href="{{ route('attendance.list', ['month' => $nextMonth->format('Y-m')]) }}" class="month-btn">翌月 <img class="next-arrow" src="{{ asset('img/arrow.png') }}" alt="arrow"></a>
    </div>
    <table class="attendance-table">
        <thead>
            <tr class="attendance-table__row">
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dates as $date)
                @php
                    $currentAttendance = $attendanceMap->get($date->format('Y-m-d'));
                @endphp
                <tr class="attendance-table__row">
                    <td>{{ $date->format('m/d') }} （{{ $weekdays[$date->format('w')] }}）</td>
                    <td>{{ optional($currentAttendance)->start_time ? \Carbon\Carbon::parse($currentAttendance->start_time)->format('H:i') : '' }}</td>
                    <td>{{ optional($currentAttendance)->end_time ? \Carbon\Carbon::parse($currentAttendance->end_time)->format('H:i') : '' }}</td>
                    <td>{{ optional($currentAttendance)->formatted_break_time }}</td>
                    <td>{{ optional($currentAttendance)->formatted_work_time }}</td>
                    <td><a class="attendance-detail" href="#">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

