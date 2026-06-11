<?php

namespace NettSite\NettMail\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NettSite\NettMail\NettMail
 */
class NettMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NettSite\NettMail\NettMail::class;
    }
}
