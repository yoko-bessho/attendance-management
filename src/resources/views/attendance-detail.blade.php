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
        <h1 class="header__title">勤怠詳細</h1>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger" style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="attendance-detail-form"
        method="POST"
        action="{{ Auth::user()->role === 'admin'
        ? route('admin.modify.attendance', ['user' => $user, 'date' => \Carbon\Carbon::parse($date)->format('Y-m-d')])
        : route('attendance.request', ['date' => \Carbon\Carbon::parse($date)->format('Y-m-d')]) }}">
        @if (Auth::user()->role === 'admin')
            @method('PATCH')
        @endif
        @csrf
        <table class="attendance-detail-table">
            <tr class="attendance-detail-table__row">
                <th><label>名前</label></th>
                <td>{{ $user->name }}</td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>日付</label></th>
                <td>{{ \Carbon\Carbon::parse($date)->format('Y年m月d日')}}</td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>出勤・退勤</label></th>
                <td>
                    <input class="time-input" type="time" name="start_time" value="{{ $displayStartTime ? \Carbon\Carbon::parse($displayStartTime)->format('H:i') : '' }}" {{ $disabled ? 'disabled' : '' }}>
                    <span>　〜　</span>
                    <input class="time-input" type="time" name="end_time" value="{{ $displayEndTime ? \Carbon\Carbon::parse($displayEndTime)->format('H:i') : '' }}" {{ $disabled ? 'disabled' : '' }}>
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>休憩</label></th>
                <td>
                    @foreach($displayBreaks as $key => $break)
                    <div class="break-time__group">
                        <input class="time-input" type="time" name="revised_breaks[{{ $key }}][start_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'start_time'))->format('H:i') }}" {{ $disabled ? 'disabled' : '' }}>
                        <span>　〜　</span>
                        <input class="time-input" type="time" name="[{{ $key }}][end_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'end_time'))->format('H:i') }}" {{ $disabled ? 'disabled' : '' }}>
                    </div>
                    @endforeach
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>休憩2</label></th>
                <td>
                    <div class="break-time__group">
                        <input type="time" name="breaks[new][start_time]" class="time-input" {{ $disabled ? 'disabled' : '' }}>
                        <span>　〜　</span>
                        <input type="time" name="breaks[new][end_time]" class="time-input" {{ $disabled ? 'disabled' : '' }}>
                    </div>
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>備考</label></th>
                <td><textarea class="textarea" name="reason" rows="4" {{ $disabled ? 'disabled' : '' }}>{{ $displayReason }}</textarea></td>
            </tr>
        </table>
        
        @if($disabled == false)
        <div class="form-action">
            <button class="form-aciton__button" type="submit">修正</button>
        </div>
        @endif
    </form>

    @if ($disabled == true)
        <div class="pending-notice">
            <p>* 承認待ちのため申請できません</p>
        </div>
    @endif

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif
</div>
@endsection