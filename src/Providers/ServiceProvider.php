<?php

namespace Osi\LaravelControllerTrait\Providers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class LaravelControllerTraitServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Osi\LaravelControllerTrait\Commands\TraitControllerCommand',
        'Osi\LaravelControllerTrait\Commands\TraitModelCommand',
    ];

    public function boot()
    {
        if (!preg_match('/lumen/i', app()->version())) {
            $this->publishes([
                __DIR__ . '/../Config/trait.php' => config_path('trait.php'),
            ], 'config');
        }
        // $this->app->alias(\Osi\LaravelControllerTrait\Traits\ControllerBaseTrait::class, 'ControllerBaseTrait');
        // $this->app->alias(\Osi\LaravelControllerTrait\Models\FilterAndSorting::class, 'FilterAndSorting');
        if (!Collection::hasMacro('paginate')) {
            Collection::macro('paginate',
                function ($perPage = 15, $page = null, $options = []) {
                    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
                    return (new LengthAwarePaginator(
                        $this->forPage($page, $perPage), $this->count(), $perPage, $page, $options))
                        ->withPath('');
                });
        }
        if (!Collection::hasMacro('setFilterAndRelationsAndSort')) {
            // dd(scopeSetFilterAndRelationsAndSort($this, $query, $params));
            Collection::macro('setFilterAndRelationsAndSort',
                function ($query, $params = []) {
                    return scopeSetFilterAndRelationsAndSort($this, $query, $params);
                });
        }
    }

    public function register()
    {
        $this->commands($this->commands);
        $registrar = new \Osi\LaravelControllerTrait\Providers\AppRoutingResourceRoute($this->app['router']);
        $this->app->bind('Illuminate\Routing\ResourceRegistrar', function () use ($registrar) {
            return $registrar;
        });
    }
}
