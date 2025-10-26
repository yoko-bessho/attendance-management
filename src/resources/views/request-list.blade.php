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
<div class="request-list__container">
    <div class="attendance__header">
        <h1 class="header__title">申請一覧</h1>
    </div>
    <div class="tab-header">
        <a class="tab-link {{ $tab === 'pending' ? 'active' : '' }}" href="{{ route('request.list', ['tab' => 'pending']) }}">承認待ち</a>
        <a class="tab-link {{ $tab === 'approval' ? 'active' : '' }}" href="{{ route('request.list', ['tab' => 'approval']) }}">承認済み</a>
    </div>

    <div class="tab-content">
    <table class="request-table">
            <thead>
                <tr class="request-table__row">
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr class="request-table__row">
                    <td>{{ $request->status->label() }}</td>
                    <td>{{ Auth::user()->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->request_date)->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ $request->created_at->format('Y/m/d')}}</td>
                    <td><a class="attendance-detail__button" href="{{ route('attendance.detail', ['date' => $request->request_date]) }}">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection