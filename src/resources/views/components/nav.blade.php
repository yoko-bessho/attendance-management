<nav>
    @if (Auth::user()->role === 'admin')
    <ul class="header-nav">
        <li class="header-nav__item">
            <a class="header-nav__link" href="">勤怠一覧</a>
        </li>
        <li class="header-nav__item">
            <a class="header-nav__link" href="">スタッフ一覧</a>
        </li>
        <li class="header-nav__item">
            <a class="header-nav__link" href="">申請一覧</a>
        </li>
        <li class="header-nav__item">
            <form class="form" action="/logout" method="POST">
                @csrf
                <button class="header-nav__button">ログアウト</button>
            </form>
        </li>
    </ul>
    @else
        <ul class="header-nav">
        <li class="header-nav__item">
            <a class="header-nav__link" href="{{ route('attendance') }}">勤怠</a>
        </li>
        <li class="header-nav__item">
            <a class="header-nav__link" href="{{ route('attendance.list') }}">勤怠一覧</a>
        </li>
        <li class="header-nav__item">
            <a class="header-nav__link" href="{{ route('request.list') }}">申請</a>
        </li>
        <li class="header-nav__item">
            <form class="form" action="/logout" method="POST">
                @csrf
                <button class="header-nav__button">ログアウト</button>
            </form>
        </li>
    </ul>
    @endif
</nav>
