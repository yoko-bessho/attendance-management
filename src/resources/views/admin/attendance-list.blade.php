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
        <h1 class="header__title">{{ $date->format('Y年m月d日') }}の勤怠</h1>
    </div>
    <div class="day-selector">
        <a href="{{ route('admin.attendance.list', ['date' => $previousDay]) }}" class="day-btn"><img class="previous-arrow" src="{{ asset('img/arrow.png') }}" alt="arrow"> 前日</a>
        <input type="date" class="day-input" name="day" value="{{ $date->format('Y-m-d') }}">
        <a href="{{ route('admin.attendance.list', ['date' => $nextDay]) }}" class="day-btn">翌日 <img class="next-arrow" src="{{ asset('img/arrow.png') }}" alt="arrow"></a>
    </div>
    <table class="attendance-table">
        <thead>
            <tr class="attendance-table__row">
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($staffs as $staff)
                <tr class="attendance-table__row">
                    <td>{{ $staff->name }}</td>
                    @if ($attendance = $staff->attendances->first())
                        <td>{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}</td>
                        <td>{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}</td>
                        <td>{{ $attendance->formatted_break_time }}</td>
                        <td>{{ $attendance->formatted_work_time }}</td>
                        <td>
                            <a class="attendance-detail__button" href="{{ route('admin.modify.attendance', ['id' => $staff->id, 'date' => $date->format('Y-m-d')]) }}">詳細</a>
                        </td>
                    @else
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>
                            <a class="attendance-detail__button" href="{{ route('admin.attendance.detail', ['id' => $staff->id, 'date' => $date->format('Y-m-d')]) }}">詳細</a>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection