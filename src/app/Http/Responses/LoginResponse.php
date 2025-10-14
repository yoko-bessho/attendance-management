<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Providers\RouteServiceProvider;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        // ユーザーの役割に応じてリダイレクト先を決定
        if ($user->role === 'admin') {
            $home = '/admin'; // 管理者用の遷移先（仮）
        } else {
            $home = RouteServiceProvider::HOME; // 一般ユーザー用の遷移先 ('/attendance')
        }

        return $request->wantsJson()
                    ? new JsonResponse('', 204)
                    : redirect()->intended($home);
    }
}
