<?php

namespace Novon229\RedLock\Facades;

use Illuminate\Support\Facades\Facade;

class RedLock extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'redlock';
    }
}
