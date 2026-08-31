<?php

use App\Http\Controllers\VerifyEmailController as ControllersVerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/email/verify/{id}/{hash}', [ControllersVerifyEmailController::class, 'verify'])->name('verification.verify');

Route::get('/email/verify', function () {
    if (auth()->user()->hasVerifiedEmail()) {
        return redirect()->route('filament.auth.auth.login');
    }

    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::post('/logout', function (Request $request) {
    Session::flush();
    Auth::logout();
    $request->session()->regenerate();

    return redirect()->route('filament.auth.auth.login');
})->middleware('auth')->name('auth.logout');
