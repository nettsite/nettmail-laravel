<?php

use Illuminate\Support\Facades\Route;

$prefix = config('nettmail.routes.prefix');
$middleware = config('nettmail.routes.middleware');

Route::group(['prefix' => $prefix, 'middleware' => $middleware, 'as' => 'nettmail.'], function (): void {
    Route::livewire('dashboard', 'nettmail::dashboard')->name('dashboard');
});
