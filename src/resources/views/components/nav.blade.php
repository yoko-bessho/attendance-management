<nav>
    <ul class="header-nav">
        <li class="header-nav__item">
            <a class="header-nav__link" href="">勤怠</a>
        </li>
        <li class="header-nav__item">
            <a class="header-nav__link" href="">退勤一覧</a>
        </li>
        <li class="header-nav__item">
            <a class="header-nav__link" href="">申請</a>
        </li>
        <li class="header-nav__item">
            <form class="form" action="/logout" method="POST">
                @csrf
                <button class="header-nav__button">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>
