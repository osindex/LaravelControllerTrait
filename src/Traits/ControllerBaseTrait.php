<?php

namespace Osi\LaravelControllerTrait\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Osi\LaravelControllerTrait\Traits\ResponseBaseTrait;
use Validator;

trait ControllerBaseTrait
{
    use ResponseBaseTrait;
    protected $model;
    protected $resource = '\Osi\LaravelControllerTrait\Resources\Resource';
    protected $collection = '\Osi\LaravelControllerTrait\Resources\Collection';
    protected $functions = []; // get_class_methods(self::class)
    protected $rulePostfix = 'Rule';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // dd(tap($this->model->setFilterAndRelationsAndSort($request)->toSql()));
        $data = ($this->model->timestamps && $this->model->latestIndex ? $this->model->latest() : $this->model)->setFilterAndRelationsAndSort($request)
            ->paginate((int) $request->pageSize ?? 15);
        return new $this->collection($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        return new $this->resource($this->model::query()->setFilterAndRelationsAndSort($request)->findOrFail($id));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $ruleName = __FUNCTION__ . $this->rulePostfix;
            // 可以用异常捕获 也可以用返回值判断
            if (method_exists($this, $ruleName)) {
                // dd($ruleName);
                $data = $this->$ruleName($request);
            }
        } catch (ValidationException $v) {
            return $this->unprocesableEtity($v->errors());
            // 422
        } catch (\Exception $e) {
            if (config('app.debug')) {
                return $this->badRequest($e->getMessage());
            }
            return $this->badRequest('未知错误');
            // 400
        }
        $res = $this->model::create($data);
        $this->afterCreate($res, $request);
        return $this->created($res);
    }

    public function afterCreate($data, $request)
    {
        // 也可以用model事件
        # code...
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return new $this->resource($this->model::query()
                ->setFilterAndRelationsAndSort($request)
                ->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $ruleName = __FUNCTION__ . $this->rulePostfix;
            // 可以用异常捕获 也可以用返回值判断
            if (method_exists($this, $ruleName)) {
                $this->$ruleName($request);
            }
        } catch (ValidationException $v) {
            return $this->unprocesableEtity($v->errors());
            // 422
        } catch (\Exception $e) {
            if (config('app.debug')) {
                return $this->badRequest($e->getMessage());
            }
            return $this->badRequest('未知错误');
            // 400
        }

        $model = $this->model::query()->findOrFail($id);
        $attributes = requestIntersect(array_keys($model->getOriginal()));
        $res = $model->update($attributes);
        $this->afterUpdate($model, $request);
        return $this->accepted($model);
    }

    public function afterUpdate($data, $request)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id = explode(',', $id);
        $this->model::destroy($id);
        return $this->noContent();
    }

    public function rules($request, $rules, $messages = [])
    {
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function option(Request $request)
    {
        //请求中有all字段则移除作用域
        $data = (property_exists($this->model, 'isSoftDeletes') ? $this->model->withTrashed() : $this->model)
            ->setFilterAndRelationsAndSort($request)
            ->when($request->has('all'), function ($q) {
                return $q->withoutGlobalScopes(); //字典移除查询作用域
            })
            ->get();
        if ($request->has('prepend') && method_exists($this->model, 'defaultOptions')) {
            foreach ($this->model->defaultOptions() as $option) {
                $data->prepend($option);
            }
        }
        return $this->dataSuccess($data);
    }

    public function batch(Request $request)
    {
        try {
            if ($request->has('data')) {
                $res = updateBatch($request->data, $this->model->getTable());
            } else {
                $res = updateBatch($request->all(), $this->model->getTable());
            }
        } catch (\Exception $e) {
            return $this->badRequest('未知错误');
        }
        return $res === false ? $this->badRequest('更新失败') : $this->accepted();
    }
}
