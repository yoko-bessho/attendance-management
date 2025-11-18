<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Models\Attendance;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;


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

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->middleware('guest:admin')
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest:admin')
        ->name('login');
});


Route::middleware(['auth','verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('work-in', [AttendanceController::class, 'workIn'])->name('work-in');
    Route::post('work-out', [AttendanceController::class, 'workOut'])->name('work-out');
    Route::post('break-start', [AttendanceController::class, 'breakStart'])->name('break-start');
    Route::post('break-end', [AttendanceController::class, 'breakEnd'])->name('break-end');

    Route::get('attendance/list', [AttendanceController::class, 'attendanceList'])->name('attendance.list');
    Route::get('/attendance/detail/{date}', [AttendanceController::class, 'attendanceDetail'])->name('attendance.detail')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::post('/attendance/detail/{date}', [StampCorrectionRequestController::class, 'requestCorrection'])->name('attendance.request')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'requestList'])->name('request.list');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [StampCorrectionRequestController::class, 'approvalForm'])->name('approval.form');
});


Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'adminAttendanceList'])->name('attendance.list');
    Route::get('/attendance/{date}/{id}', [AttendanceController::class, 'AttendanceDetail'])->name('attendance.detail')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::post('/attendance/{date}/{id}', [StampCorrectionRequestController::class, 'requestCorrection'])->name('modify.attendance')->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::patch('/stamp_correction_request/approve/{attendance_correct_request_id}', [StampCorrectionRequestController::class, 'approval'])->name('approval');
    Route::get('/staff/list', [UserController::class, 'staffList'])->name('staff.list');
    Route::get('/attendance/staff/{id}', [AttendanceController::class, 'attendanceList'])->name('attendance.staff.list');
    Route::post('/attendance/export/{user}', [AdminAttendanceController::class, 'export'])->name('attendance.export');
});