<?php

use Illuminate\Support\Facades\Route;

$prefix = config('nettmail.routes.prefix');
$middleware = config('nettmail.routes.middleware');

Route::group(['prefix' => $prefix, 'middleware' => $middleware, 'as' => 'nettmail.'], function (): void {
    Route::livewire('dashboard', 'nettmail::dashboard')->name('dashboard');

    Route::livewire('contacts', 'nettmail::contacts.index')->name('contacts.index');
    Route::livewire('contacts/{contact}', 'nettmail::contacts.show')->name('contacts.show');

    Route::livewire('lists', 'nettmail::lists.index')->name('lists.index');
    Route::livewire('lists/{list}', 'nettmail::lists.show')->name('lists.show');

    Route::livewire('segments', 'nettmail::segments.index')->name('segments.index');
    Route::livewire('segments/{segment}', 'nettmail::segments.show')->name('segments.show');

    Route::livewire('campaigns', 'nettmail::campaigns.index')->name('campaigns.index');
    Route::livewire('campaigns/{campaign}', 'nettmail::campaigns.show')->name('campaigns.show');

    Route::livewire('settings', 'nettmail::settings.index')->name('settings');
});
