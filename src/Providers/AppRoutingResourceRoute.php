<?php

namespace Osi\LaravelControllerTrait\Providers;

use Illuminate\Routing\ResourceRegistrar;

class AppRoutingResourceRoute extends ResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['index', 'create', 'option', 'store', 'show', 'edit', 'update', 'destroy'];
    // remove option
    /**
     * The verbs used in the resource URIs.
     *
     * @var array
     */
    protected static $verbs = [
        'create' => 'create',
        'option' => 'option',
        'edit' => 'edit',
    ];
    /**
     * Add the option method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceOption($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name) . '/' . static::$verbs['option'];
        $action = $this->getResourceAction($name, $controller, 'option', $options);
        return $this->router->get($uri, $action);
    }
}
