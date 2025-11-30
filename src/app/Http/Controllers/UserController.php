<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function staffList()
    {
        $users = User::where('role', 'staff')->get();

        return view('admin.staff-list', compact('users'));
    }
}
