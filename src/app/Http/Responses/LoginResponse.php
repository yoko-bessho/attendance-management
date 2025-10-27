<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
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

        if (Auth::guard('admin')->check()) {
            $home = '/admin/attendance/list';
        } else {
            $home = RouteServiceProvider::HOME;
        }

        return $request->wantsJson()
                    ? new JsonResponse('', 204)
                    : redirect()->intended($home);
    }
}
