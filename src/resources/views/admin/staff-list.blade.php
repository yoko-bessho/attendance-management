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
<div class="staff-list__container">
    <div class="attendance__header">
        <h1 class="header__title">スタッフ一覧</h1>
    </div>

    <table class="attendance-table">
        <thead>
            <tr class="attendance-table__row">
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr class="attendance-table__row">
                <td>{{ $user['name'] }}</td>
                <td>{{ $user['email'] }}</td>
                <td><a class="attendance-detail__button" href="{{ route('admin.attendance.staff.list', ['id' => $user->id]) }}">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection