<?php

use Nettsite\NettMail\Core\Contracts\MailDriverContract;
use Nettsite\NettMail\Core\Contracts\StorageAdapterContract;
use Nettsite\NettMail\Core\Drivers\PhpMailDriver;
use Nettsite\NettMail\Core\NettMail as CoreNettMail;
use NettSite\NettMail\Facades\NettMail;
use NettSite\NettMail\Storage\EloquentAdapter;

it('resolves the configured driver', function () {
    expect($this->app->make(MailDriverContract::class))->toBeInstanceOf(PhpMailDriver::class);
});

it('binds the storage adapter to the eloquent adapter', function () {
    expect($this->app->make(StorageAdapterContract::class))->toBeInstanceOf(EloquentAdapter::class);
});

it('resolves the core NettMail instance via the facade', function () {
    expect(NettMail::getFacadeRoot())->toBeInstanceOf(CoreNettMail::class);
});
