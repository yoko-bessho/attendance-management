<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Models\Attendance;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('work-in', [AttendanceController::class, 'workIn'])->name('work-in');
    Route::post('work-out', [AttendanceController::class, 'workOut'])->name('work-out');
    Route::post('break-start', [AttendanceController::class, 'breakStart'])->name('break-start');
    Route::post('break-end', [AttendanceController::class, 'breakEnd'])->name('break-end');
    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList'])->name('attendance.list');
});