<?php

namespace Osi\LaravelControllerTrait\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Validator;

trait ControllerBaseTrait
{
    protected $model;
    protected $resource;
    protected $collection;
    protected $functions;
    protected $rulePostfix = 'Rule';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // dd(tap($this->model->setFilterAndRelationsAndSort($request)->toSql()));
        $data = tap($this->model->timestamps ? $this->model->latest() : $this->model)->setFilterAndRelationsAndSort($request)
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
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        return new $this->resource($this->model::query()->setFilterAndRelationsAndSort($request)->findOrFail($id));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $ruleName = __FUNCTION__ . $this->rulePostfix;
            // 可以用异常捕获 也可以用返回值判断
            if (in_array($ruleName, $this->functions)) {
                // dd($ruleName);
                $this->$ruleName($request->all());
            }
        } catch (ValidationException $v) {
            return $this->unprocesableEtity($v->errors());
            // 422
        } catch (\Exception $e) {
            return $this->badRequest('未知错误');
            // 400
        }
        $this->model::create($request->all());
        return $this->created();
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $ruleName = __FUNCTION__ . $this->rulePostfix;
            // 可以用异常捕获 也可以用返回值判断
            if (in_array($ruleName, $this->functions)) {
                $this->$ruleName($request->all());
            }
        } catch (ValidationException $v) {
            return $this->unprocesableEtity($v->errors());
            // 422
        } catch (\Exception $e) {
            return $this->badRequest('未知错误');
            // 400
        }

        $model = $this->model::query()->findOrFail($id);
        $attributes = request_intersect(array_keys($model->getOriginal()));
        $model->update($attributes);

        return $this->accepted();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id = explode(',', $id);
        $this->model::destroy($id);
        return $this->noContent();
    }

    public function rules($requestAll, $rules, $messages = [])
    {
        $validator = Validator::make($requestAll, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function option(Request $request)
    {
        $select = $request->get('select') ?? '*';
        if ($select !== '*') {
            $select = explode(',', $select);
        }
        //请求中有all字段则移除作用域
        if ($request->has('all')) {
            $data = //tap($this->model->timestamps ? $this->model->latest() : $this->model)
            tap(property_exists($this->model, 'isSoftDeletes') ? $this->model->withTrashed() : $this->model)
                ->setFilterAndRelationsAndSort($request)
                ->select($select)
                ->withoutGlobalScopes() //字典移除查询作用域
                ->get();
        } else {
            $data = //tap($this->model->timestamps ? $this->model->latest() : $this->model)
            tap(property_exists($this->model, 'isSoftDeletes') ? $this->model->withTrashed() : $this->model)
                ->setFilterAndRelationsAndSort($request)
                ->select($select)
                ->get();
        }
        return $this->dataSuccess($data);
    }

    public function dataSuccess($data)
    {
        return $this->success([JsonResource::$wrap => $data]);
    }
    /**
     * 201
     *
     * @author osindex<yaoiluo@gmail.com>
     * @param string $content
     * @return Response
     */
    protected function created($content = '')
    {
        return new Response($content, Response::HTTP_CREATED);
    }

    /**
     * 202
     *
     * @author osindex<yaoiluo@gmail.com>
     * @return Response
     */
    protected function accepted()
    {
        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * 204
     *
     * @author osindex<yaoiluo@gmail.com>
     * @return Response
     */
    protected function noContent()
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * 400
     *
     * @author osindex<yaoiluo@gmail.com>
     * @param $message
     * @param array $headers
     * @param int $options
     * @return \Illuminate\Http\JsonResponse
     */
    protected function badRequest($message, array $headers = [], $options = 0)
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_BAD_REQUEST, $headers, $options);
    }

    /**
     * 401
     *
     * @author osindex<yaoiluo@gmail.com>
     * @param string $message
     * @param array $headers
     * @param int $options
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorized($message = '', array $headers = [], $options = 0)
    {
        return response()->json([
            'message' => $message ? $message : 'Token Signature could not be verified.',
        ], Response::HTTP_UNAUTHORIZED, $headers, $options);
    }

    /**
     * 403
     *
     * @author osindex<yaoiluo@gmail.com>
     * @param string $message
     * @param array $headers
     * @param int $options
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbidden($message = '', array $headers = [], $options = 0)
    {
        return response()->json([
            'message' => $message ? $message : 'Insufficient permissions.',
        ], Response::HTTP_FORBIDDEN, $headers, $options);
    }

    /**
     * 422
     *
     * @author osindex<yaoiluo@gmail.com>
     * @param array $errors
     * @param array $headers
     * @param string $message
     * @param int $options
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unprocesableEtity(array $errors = [], array $headers = [], $message = '', $options = 0)
    {
        return response()->json([
            'message' => $message ? $message : '422 Unprocessable Entity',
            'errors' => $errors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY, $headers, $options);
    }

    /**
     * 200
     *
     * @author osindex<yaoiluo@gmail.com>
     * @param array $data
     * @param array $headers
     * @param int $options
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success(array $data, array $headers = [], $options = 0)
    {
        return response()->json($data, Response::HTTP_OK, $headers, $options);
    }
}
