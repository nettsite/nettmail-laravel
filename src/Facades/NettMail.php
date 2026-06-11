<?php

namespace NettSite\NettMail\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nettsite\NettMail\Core\NettMail
 */
class NettMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nettsite\NettMail\Core\NettMail::class;
    }
}
