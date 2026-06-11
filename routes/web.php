<?php

use Illuminate\Support\Facades\Route;
use NettSite\NettMail\Http\Controllers\OptInController;
use NettSite\NettMail\Http\Controllers\TrackingController;
use NettSite\NettMail\Http\Controllers\UnsubscribeController;
use NettSite\NettMail\Http\Controllers\WebVersionController;

$prefix = config('nettmail.routes.prefix');

Route::get("{$prefix}/track/open/{sendToken}", [TrackingController::class, 'open'])
    ->name('nettmail.track.open');

Route::get("{$prefix}/track/click/{sendToken}/{linkHash}", [TrackingController::class, 'click'])
    ->name('nettmail.track.click');

Route::get("{$prefix}/unsubscribe/{token}", [UnsubscribeController::class, 'show'])
    ->name('nettmail.unsubscribe.show');

Route::get("{$prefix}/unsubscribe/{token}/all", [UnsubscribeController::class, 'unsubscribeAll'])
    ->name('nettmail.unsubscribe.all');

Route::post("{$prefix}/unsubscribe/{token}", [UnsubscribeController::class, 'oneClick'])
    ->name('nettmail.unsubscribe.oneClick');

Route::get("{$prefix}/opt-in/{token}", [OptInController::class, 'confirm'])
    ->name('nettmail.optIn.confirm');

Route::get("{$prefix}/web-version/{sendToken}", [WebVersionController::class, 'show'])
    ->name('nettmail.webVersion.show');
