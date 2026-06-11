<?php

use Illuminate\Support\Facades\Route;
use NettSite\NettMail\Http\Controllers\WebhookController;

Route::post(config('nettmail.routes.prefix').'/webhooks/{provider}', [WebhookController::class, 'handle'])
    ->name('nettmail.webhooks.handle');
