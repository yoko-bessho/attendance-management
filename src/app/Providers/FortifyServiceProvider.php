<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Laravel\Fortify\Http\Requests\LoginRequest::class, LoginRequest::class);

        $this->app->singleton(\Laravel\Fortify\Contracts\LoginResponse::class, LoginResponse::class);

        $this->app->singleton(\Laravel\Fortify\Contracts\LogoutResponse::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            if (request()->is('admin/login')) {
                return view('admin/auth/login');
            }
            return view('auth.login');
        });

        Fortify::authenticateUsing(function (Request $request) {
            $guard = str_contains($request->path(), 'admin') ? 'admin' : 'web';

            $credentials = $request->only(Fortify::username(), 'password');

            $attemptResult = Auth::guard($guard)->attempt($credentials);

            if ($attemptResult) {
                $user = Auth::guard($guard)->user();

                if ($guard === 'admin' && $user->role !== 'admin') {
                    Auth::guard($guard)->logout();
                    return null;
                }

                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email.$request->ip());
        });
    }
}
