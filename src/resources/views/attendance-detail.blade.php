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
                    @if (!$stampRequest)
                    <input class="time-input" type="time" name="start_time" value="{{ $displayStartTime ? \Carbon\Carbon::parse($displayStartTime)->format('H:i') : '' }}">
                    <span>　〜　</span>
                    <input class="time-input" type="time" name="end_time" value="{{ $displayEndTime ? \Carbon\Carbon::parse($displayEndTime)->format('H:i') : '' }}">
                    @else
                    <span>
                        {{ $displayStartTime ? \Carbon\Carbon::parse($displayStartTime)->format('H:i') : '' }}
                       　〜　
                        {{ $displayEndTime ? \Carbon\Carbon::parse($displayEndTime)->format('H:i') : '' }}
                    </span>
                    <input type="hidden" name="start_time" value="{{ $displayStartTime ? \Carbon\Carbon::parse($displayStartTime)->format('H:i') : '' }}">
                    <input type="hidden" name="end_time" value="{{ $displayEndTime ? \Carbon\Carbon::parse($displayEndTime)->format('H:i') : '' }}">
                    @endif
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>休憩</label></th>
                <td>
                    @foreach($displayBreaks as $key => $break)
                    <div class="break-time__group">
                        @if (!$stampRequest)
                        <input type="hidden" name="breaks[{{ $key }}][id]" value="{{ data_get($break, 'id') }}">
                        <input class="time-input" type="time" name="breaks[{{ $key }}][start_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'start_time'))->format('H:i') }}">
                        <span>　〜　</span>
                        <input class="time-input" type="time" name="breaks[{{ $key }}][end_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'end_time'))->format('H:i') }}">
                        @else
                        <span>
                            {{ \Carbon\Carbon::parse(data_get($break, 'start_time'))->format('H:i') }}
                            　〜　
                            {{ \Carbon\Carbon::parse(data_get($break, 'end_time'))->format('H:i') }}
                        </span>
                        <input type="hidden" name="breaks[{{ $key }}][id]" value="{{ data_get($break, 'id') }}">
                        <input type="hidden" name="breaks[{{ $key }}][start_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'start_time'))->format('H:i') }}">
                        <input type="hidden" name="breaks[{{ $key }}][end_time]" value="{{ \Carbon\Carbon::parse(data_get($break, 'end_time'))->format('H:i') }}">
                        @endif
                    </div>
                    @endforeach
                </td>
            </tr>
            <tr class="attendance-detail-table__row">
                <th><label>休憩2</label></th>
                <td>
                    @if (!$stampRequest)
                    <div class="break-time__group">
                        <input type="time" name="breaks[new][start_time]" class="time-input">
                        <span>　〜　</span>
                        <input type="time" name="breaks[new][end_time]" class="time-input">
                    </div>
                    @else
                    <span></span>
                    @endif
                </td>
            </tr>

            <tr class="attendance-detail-table__row">
                <th><label>備考</label></th>
                @if (!$stampRequest)
                <td><textarea class="textarea" name="reason" rows="4" >{{ $displayReason }}</textarea>
                @else
                <td>
                    <span>{{ $displayReason }}</span>
                </td>
                <input type="hidden" name="reason" value="{{ $displayReason }}">
                @endif
                </td>
            </tr>
        </table>

        <div class="form-action">
        @if ($stampRequest && $stampRequest->status === \App\Enums\StampCorrectionRequestsStatus::APPROVAL)
            <div class="form-action__button--approved">承認済み</div>
        @elseif (!$stampRequest)
            @if (Auth::user()->role == 'admin')
                <button class="form-action__button" type="submit">修正</button>
            @else
                <button class="form-action__button" type="submit">申請</button>
            @endif
        @else {{-- $stampRequest が存在し、かつ status が APPROVAL ではない場合 --}}
            @if ($stampRequest->status === \App\Enums\StampCorrectionRequestsStatus::PENDING && Auth::user()->role == 'staff')
                <div class="pending-notice">
                    <p>* 承認待ちのため申請できません</p>
                </div>
            @endif
        @endif
        </div>
    </form>

    @if ($stampRequest && $stampRequest->status === \App\Enums\StampCorrectionRequestsStatus::PENDING && Auth::user()->role == 'admin')
        <form class="form-action" method="POST" action="{{ route('admin.approval', ['attendance_correct_request_id' => $stampRequest->id]) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="form-action__button">承認</button>
        </form>
    @endif

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

</div>
@endsection