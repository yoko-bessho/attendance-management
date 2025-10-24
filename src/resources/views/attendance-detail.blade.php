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
    @php
        $isPending = (bool) $pendingRequest;
        $disabled = $isPending ? 'disabled' : '';

        if ($isPending) {
            $displayStartTime = $pendingRequest->revised_start_time;
            $displayEndTime = $pendingRequest->revised_end_time;
            $displayReason = $pendingRequest->reason;
            $displayBreaks = json_decode($pendingRequest->revised_breaks) ?? [];
        } else {
            $displayStartTime = optional($attendance)->start_time;
            $displayEndTime = optional($attendance)->end_time;
            $displayReason = ''; // 申請理由は常に新規入力
            $displayBreaks = optional($attendance)->breakTimes ?? [];
        }
    @endphp
    @if ($errors->any())
        <div class="alert alert-danger" style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="attendance-detail-form" action="{{ route('attendance.request', ['date' => $date]) }}" method="POST">
        @csrf
        <table class="attendance-detail-table">
            <tr class="attendance-detail-table__row">
                <th><label>名前</label></th>
                <td>{{ Auth::user()->name }}</td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>日付</label></th>
                <td>{{ \Carbon\Carbon::parse($date)->format('Y年m月d日')}}</td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>出勤・退勤</label></th>
                <td>
                    <input class="time-input" type="time" name="start_time" value="{{ $displayStartTime ? \Carbon\Carbon::parse($displayStartTime)->format('H:i') : '' }}" {{ $disabled }}>
                    <span>　〜　</span>
                    <input class="time-input" type="time" name="end_time" value="{{ $displayEndTime ? \Carbon\Carbon::parse($displayEndTime)->format('H:i') : '' }}" {{ $disabled }}>
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>休憩</label></th>
                <td>
                    @foreach($displayBreaks as $key => $break)
                    <div class="break-time__group">
                        <input class="time-input" type="time" name="breaks[{{ $key }}][start_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'start_time'))->format('H:i') }}" {{ $disabled }}>
                        <span>　〜　</span>
                        <input class="time-input" type="time" name="breaks[{{ $key }}][end_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'end_time'))->format('H:i') }}" {{ $disabled }}>
                    </div>
                    @endforeach
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>休憩2</label></th>
                <td>
                    <div class="break-time__group">
                        <input type="time" name="breaks[new][start_time]" class="time-input" {{ $disabled }}>
                        <span>　〜　</span>
                        <input type="time" name="breaks[new][end_time]" class="time-input" {{ $disabled }}>
                    </div>
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>備考</label></th>
                <td><textarea class="textarea" name="reason" rows="4" {{ $disabled }}>{{ $displayReason }}</textarea></td>
            </tr>
        </table>
        
        @if(!$isPending)
        <div class="form-action">
            <button class="form-aciton__button" type="submit">修正</button>
        </div>
        @endif
    </form>

    @if($isPending)
        <div class="pending-notice">
            <p>* 承認待ちのため申請できません</p>
        </div>
    @endif

</div>
@endsection