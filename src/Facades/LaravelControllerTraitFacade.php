<?php
namespace Osi\LaravelControllerTrait\Facades;

use Illuminate\Support\Facades\Facade as LaravelFacade;
use Osi\LaravelControllerTrait\Models\LaravelControllerTrait;

/**
 * Facade for Laravel.
 */
class LaravelControllerTraitFacade extends LaravelFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LaravelControllerTrait::class;
    }
}
