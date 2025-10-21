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
<div class="attendance-detail__container">
    <div class="attendance__header">
        <h1 class="header__title">勤怠詳細・修正</h1>
    </div>

    <form class="attendance-detail-form" action="" method="POST">
        @csrf
        <table class="attendance-detail-table">
            <tr class="attendance-detail-table__row">
                <th><label for="">名前</label></th>
                <td>　{{ Auth::user()->name }}</td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label for="">日付</label></th>
                <td>{{ \Carbon\Carbon::parse($date)->format('　　Y年　　　　　　　　m月d日')}}</td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label for="">出勤・退勤</label></th>
                <td>
                    <input class="time-input" type="time" name="start_time" value="{{ optional($attendance)->start_time ? \Carbon\Carbon::parse(optional($attendance)->start_time)->format('H:i') : '' }}">
                    <span>　〜　</span>
                    <input class="time-input" type="time" name="end_time" value="{{ optional($attendance)->end_time ? \Carbon\Carbon::parse(optional($attendance)->end_time)->format('H:i') : '' }}">
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label for="">休憩</label></th>
                <td>
                    @if(optional($attendance)->breakTimes)
                        @foreach($attendance->breakTimes as $key => $break)
                        <div class="break-time__group">
                            <input class="time-input" type="time" name="breaks[{{ $key }}][start_time]" value="{{ \Carbon\Carbon::parse($break->start_time)->format('H:i') }}">
                            <span>　〜　</span>
                            <input class="time-input" type="time" name="breaks[{{ $key }}][end_time]" value="{{ \Carbon\Carbon::parse($break->end_time)->format('H:i') }}">
                        </div>
                        @endforeach
                    @endif
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label for="">休憩2</label></th>
                <td>
                    <div class="break-time__group">
                        <input type="text" name="breaks[new][start_time]" class="time-input" placeholder="" onfocus="this.type='time'">
                        <span>　〜　</span>
                        <input type="text" name="breaks[new][end_time]" class="time-input" placeholder="" onfocus="this.type='time'">
                    </div>
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label for="">備考</label></th>
                <td><textarea class="textarea" name="note" rows="4">{{ optional($attendance)->note }}</textarea></td>
            </tr>
        </table>
        <div class="form-action">
            <button class="form-aciton__button" type="submit" class="btn">修正</button>
        </div>
    </form>
</div>
@endsection