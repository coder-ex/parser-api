<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \App\Services\Libs pack(string $table, string $field, ?string $typeDB = null)
 * @method static array \App\Services\Libs unpack(string $table, ?string $typeDB = null)
 *
 * @see \App\Services\Libs
 */
class Backup extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'backup';
    }
}
