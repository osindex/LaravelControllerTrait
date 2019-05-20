<?php

namespace Osi\LaravelControllerTrait\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class TraitControllerCommand extends GeneratorCommand {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $name = 'trait:controller';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new controller class with trait';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'TraitController';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub() {
		$stub = null;

		if ($this->option('notModel')) {
			$stub = '/stubs/controller.stub';
		} else {
			$stub = '/stubs/controller.model.stub';
		}

		return __DIR__ . $stub;
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace) {
		return $rootNamespace . '\Http\Controllers';
	}

	/**
	 * Build the class with the given name.
	 *
	 * Remove the base controller import if we are already in base namespace.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function buildClass($name) {
		$controllerNamespace = $this->getNamespace($name);

		$replace = [];

		$replace = $this->buildModelReplacements($replace);

		$replace["use {$controllerNamespace}\Controller;\n"] = '';

		return str_replace(
			array_keys($replace), array_values($replace), parent::buildClass($name)
		);
	}

	/**
	 * Build the model replacement values.
	 *
	 * @param  array  $replace
	 * @return array
	 */
	protected function buildModelReplacements(array $replace) {
		$model = $this->option('model');
		if (!$model) {
			$model = str_replace('Controller', '', $this->getNameInput());
		}
		$modelClass = $this->parseModel($model);

		if (!class_exists($modelClass)) {
			if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
				$this->call('trait:model', ['name' => $modelClass, '-m' => true]);
			}
		}

		return array_merge($replace, [
			'DummyFullModelClass' => $modelClass,
			'DummyModelClass' => class_basename($modelClass),
			'DummyModelVariable' => lcfirst(class_basename($modelClass)),
		]);
	}

	/**
	 * Get the fully-qualified model class name.
	 *
	 * @param  string  $model
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function parseModel($model) {
		if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
			throw new InvalidArgumentException('Model name contains invalid characters.');
		}

		$model = trim(str_replace('/', '\\', config('trait.model.prefix') . $model), '\\');

		if (!Str::startsWith($model, $rootNamespace = $this->laravel->getNamespace())) {
			$model = $rootNamespace . $model;
		}

		return $model;
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions() {
		return [
			['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model.'],
			['notModel', 'nm', InputOption::VALUE_NONE, 'Generate a resource controller class.'],
		];
	}
}
