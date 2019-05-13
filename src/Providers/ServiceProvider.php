<?php

namespace Osi\LaravelControllerTrait\Providers;

use Illuminate\Support\ServiceProvider;

class LaravelControllerTraitServiceProvider extends ServiceProvider {
	protected $commands = [
		'Osi\LaravelControllerTrait\Commands\TraitModelCommand',
		'Osi\LaravelControllerTrait\Commands\TraitControllerCommand',
	];
	public function boot() {
		// if (isNotLumen()) {
		// 	$this->publishes([
		// 		__DIR__ . '/../config/xx.php' => config_path('xx.php'),
		// 	], 'config');

		// 	if (!class_exists('CreateLaravelControllerTraitTable')) {
		// 		$timestamp = date('Y_m_d_His', time());

		// 		$this->publishes([
		// 			__DIR__ . '/../database/migrations/xx.php.stub' => $this->app->databasePath() . "/migrations/{$timestamp}_xx.php",
		// 		], 'migrations');
		// 	}
		// }
	}

	public function register() {
		// if (isNotLumen()) {
		// 	$this->mergeConfigFrom(
		// 		__DIR__ . '/../config/xx.php',
		// 		'xx'
		// 	);
		// }
		$this->commands($this->commands);
		// 在容器中注册
		// $this->app->singleton('LaravelControllerTrait', function () {
		// 	return new \Osi\LaravelControllerTrait\Models\LaravelControllerTrait;
		// });
		// $this->app->singleton('AdminOneResource', function () {
		// 	return new \Osi\LaravelControllerTrait\Traits\AdminOneResource;
		// });
		// $this->app->singleton('AdminOneResource', function () {
		// 	return new \Osi\LaravelControllerTrait\Traits\AdminManyResource;
		// });
		// $this->app->alias(\Osi\LaravelControllerTrait\Models\LaravelControllerTrait::class, 'LaravelControllerTrait');
		// $this->app->alias(\Osi\LaravelControllerTrait\Traits\AdminManyResource::class, 'AdminManyResource');
		// $this->app->alias(\Osi\LaravelControllerTrait\Traits\AdminOneResource::class, 'AdminOneResource');
	}

}
