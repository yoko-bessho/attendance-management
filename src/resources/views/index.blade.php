@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('nav')
    @if (Auth::check())
        @component('components.nav')
        @endcomponent
    @endif
@endsection

@section('content')
<div class="attendance__content">
    <div class="attendance__status">
        @if($statusLabel['work_in'])
            <span>勤務外</span>
        @elseif($statusLabel['work_out'] || ['break_start'] ['break_end'])
            <span>勤務中</span>
        @elseif($statusLabel['break_end'])
            <span>休憩中</span>
        @else
            <span>退勤済</span>
        @endif
    </div>

    <div class="attendance__panel">
        <h2 class="attendance__date">
            {{ $now->year }}年{{ $now->month }}月{{ $now->day }}日({{ $weekdays[$now->dayOfWeek] }})
        </h2>
        <h2 class="current-time">
            {{ $now->format('H:i') }}</h2>

        <div class="attendance__form">
            @if($statusLabel['work_in'])
            <form class="attendance__button" action="/work-in" method="POST">
            @csrf
                <button class="attendance__button-submit" type="submit">出勤</button>
            </form>
            @elseif($statusLabel['work_out'] || ['break_start'])
            <form class="attendance__button">
            @csrf
                <button class="attendance__button-submit" type="submit">退勤</button>
            </form>
            <form class="attendance__button" action="">
            @csrf
                <button class="break__button-submit" type="submit">休憩入</button>
            </form>
            @elseif($statusLabel['break_end'])
            <form class="attendance__button">
            @csrf
                <button class="break__button-submit" type="submit">休憩戻</button>
            </form>
            @else
            <p>お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>
@endsection

