<?php

namespace Osi\LaravelControllerTrait\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Class FilterAndSorting
 * @package App\Traits
 *
 * @version 1.0.5
 * @author Bondarenko Kirill <bondarenko.kirill@gmail.com>
 */
trait FilterAndSorting
{
    /** @var array 关系列表 */
    protected $expands = [];
    /**
     * Boot at first.
     */
    public static function bootFilterAndSorting()
    {
        if (intval(app()->version()) < 10)
            DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    // /**
    //  * 获得当前表名
    //  * @return [type] [description]
    //  */
    // public static function getTableName()
    // {
    //     return (new static )->getTable();
    // }
    /**
     * 过滤设备以及排序嵌套模型的连接
     *
     * @param Builder $query
     * @param Request|null $request
     * @param array $params
     * @return mixed
     * @since 1.0.0
     */
    public function scopeSetFilterAndRelationsAndSort($query, $request = null, $params = [])
    {
        $query = $this->setFilter($query, $params, $request);
        $this->setExpands($query, $request);
        $this->setFilterExpand($query, $params, $request);
        $this->setExpandCount($query, $params, $request);
        $query = $this->setSort($query, $request);
        $query = $this->setLimit($query, $request);
        $query = $this->setOffset($query, $request);
        $query = $this->setSelect($query, $request);
        return $query;
    }
    /**
     * 在嵌套模型筛选数据
     *
     * @param $query
     * @param $params
     * @param null $request
     * @since 1.0.3
     */
    public function setExpandCount($query, $params, $request = null)
    {
        $filter = $this->getFilter($params, $request, 'expandCount');
        if ($filter) {
            foreach ($filter as $countV) {
                // dd($filter, $value);
                if (is_array($countV)) {
                    foreach ($countV as $key => $value) {
                        $keys_array = explode('.', $key);
                        $relation = null;
                        if (count($keys_array) == 2 && in_array($keys_array[0], $this->extraFields())) {
                            // dd($keys_array, $value);
                            $relation = $keys_array[0];
                            $field_name = $keys_array[1];
                            $query->withCount([$relation => function ($q) use ($field_name, $value) {
                                $this->addFilterCondition($q, $field_name, $value);
                            }]);
                        }
                    }
                } elseif ($countV) {
                    $query->withCount($countV);
                }
            }
        }
    }
    /**
     * 在嵌套模型筛选数据
     *
     * @param $query
     * @param $params
     * @param null $request
     * @since 1.0.3
     */
    public function setFilterExpand($query, $params, $request = null)
    {
        $filter = $this->getFilter($params, $request, 'filterExpand');
        if ($filter) {
            foreach ($filter as $key => $value) {
                $keys_array = explode('.', $key);
                $relation = null;
                if (count($keys_array) == 2 && in_array($keys_array[0], $this->extraFields())) {
                    $relation = $keys_array[0];
                    $field_name = $keys_array[1];
                    $query->with([$relation => function ($q) use ($field_name, $value) {
                        $this->addFilterCondition($q, $field_name, $value);
                    }]);
                }
            }
        }
    }
    /**
     * 设置过滤器
     *
     *
     * @param Builder $query
     * @param array $params астомные параметры для фильтра
     * @param Request $request нужен для выборки по фильтрам с клинта
     * @return mixed
     * @since 1.0.0
     */
    public function setFilter($query, $params, $request = null)
    {
        $filter = $this->getFilter($params, $request);
        // dd($filter);
        if ($filter) {
            $scopes = $this->getScopes();
            foreach ($filter as $key => $value) {
                $keys_array = explode('.', $key);
                // dd($keys_array);
                $relation = null;
                $table_name = null;
                //改造$key_array
                $relationStr = '';
                $flag = false;
                if (count($keys_array) > 2) {
                    $new_keys_array = [];
                    $last_field_name = array_pop($keys_array);
                    for ($i = 0; $i < count($keys_array); $i++) {
                        $relationStr .= $keys_array[$i] . '.';
                    }
                    $new_keys_array[0] = trim($relationStr, '.');
                    $new_keys_array[1] = $last_field_name;
                    // dd($new_keys_array);
                    $keys_array = $new_keys_array;
                    $flag = true;
                }
                //改造完毕
                if (count($keys_array) == 2 && in_array($keys_array[0], $this->extraFields())) {
                    $relation = $keys_array[0];
                    if (!$flag) {
                        $table_name = $this->detectTableNameFromRelation($relation);
                    }
                    $field_name = $keys_array[1];
                } else {
                    $field_name = $keys_array[0];
                }
                if ($relation) {
                    if (isset($value['operation']) && $value['operation'] == '<>') {
                        $value['operation'] = '=';
                        $query->whereDoesntHave($relation, function ($qu) use ($field_name, $value, $table_name) {
                            $this->addFilterCondition($qu, $field_name, $value, $table_name);
                        });
                    } else {
                        $query->whereHas($relation, function ($qu) use ($field_name, $value, $table_name) {
                            $this->addFilterCondition($qu, $field_name, $value, $table_name);
                        });
                    }
                } else if (in_array($keys_array[0], $scopes)) {
                    $this->addScope($query, $keys_array[0], $value);
                } else {
                    $this->addFilterCondition($query, $field_name, $value);
                }
            }
        }
        return $query;
    }
    /**
     * 添加到过滤条件
     *
     * @param Builder $query
     * @param string $key Поле по которому фильтровать
     * @param string $value значение по которому фильтровать
     * @param string $table_name
     * @return mixed
     * @since 1.0.0
     */
    public function addFilterCondition(&$query, $key, $value, $table_name = null)
    {
        $allow_operations = ['jsoncontains', 'jsonlength', '=', '>', '<', '>=', '<=', '<>', 'notin', 'in', 'like'];
        if ($table_name) {
            $key = $table_name . '.' . $key;
        }
        if (is_array($value)) {
            if (isset($value['isNull']) && $value['isNull'] === true) {
                $query->whereNull($key);
            } elseif (isset($value['isNull']) && $value['isNull'] === false) {
                $query->whereNotNull($key);
            }
            $pattern = "/^(\d{2}).(\d{2}).(\d{4})$/";
            if (isset($value['operation']) && in_array(strtolower($value['operation']), $allow_operations) && isset($value['value'])) {
                if (strtolower($value['operation']) == 'in' && is_array($value['value'])) {
                    if (in_array(null, $value['value'])) {
                        $query->where(function ($q) use ($key, $value) {
                            return $q->whereIn($key, $value['value'])->orWhereNull($key);
                        });
                    } else {
                        $query->whereIn($key, $value['value']);
                    }
                } elseif (strtolower($value['operation']) == 'jsoncontains') {
                    $query->whereJsonContains($key, $value['value']);
                } elseif (strtolower($value['operation']) == 'jsonlength') {
                    if (isset($value['type'])) {
                        $query->whereJsonLength($key, $value['type'], $value['value']);
                    } else {
                        $query->whereJsonLength($key, $value['value']);
                    }
                } elseif (strtolower(str_replace(' ', '', $value['operation'])) == 'notin' && is_array($value['value'])) {
                    $query->whereNotIn($key, $value['value']);
                } elseif (strtolower($value['operation']) == 'like') {
                    if (strpos($value['value'], '%') === false) {
                        $query->where($key, 'like', "%{$value['value']}%");
                    } else {
                        $query->where($key, 'like', $value['value']);
                    }
                } else {
                    $value['value'] = preg_match($pattern, $value['value']) ? (new \DateTime($value['value']))->format("Y-m-d") : $value['value'];
                    // db:raw 注入隐患
                    $query->where($key, $value['operation'], $value['value']);
                }
            } elseif (isset($value['from']) || isset($value['to'])) {
                if (isset($value['from']) && $value['from']) {
                    $from = preg_match($pattern, $value['from']) ? (new \DateTime($value['from']))->format("Y-m-d") : $value['from'];
                    $query->where($key, '>=', $from);
                }
                if (isset($value['to']) && $value['to']) {
                    $to = preg_match($pattern, $value['to']) ? (new \DateTime($value['to']))->format("Y-m-d") : $value['to'];
                    $query->where($key, '<=', $to);
                }
            }
        } else {
            $query->where($key, $value);
        }
        return $query;
    }
    /**
     * 联系
     *
     * @param Builder $query
     * @param Request|null $request
     * @return mixed
     * @since 1.0.0
     */
    public function setExpands(&$query, $request = null)
    {
        $this->expands = $this->getExpands($request);
        if ($this->expands) {
            //3月11日更新,详见 readme.md
            $with = array_map(function ($v) {
                return str_replace('/', ',', $v);
            }, $this->expands);

            $query->with($with);
        }
        return $this->expands;
    }
    /**
     * 展开
     *
     * @param Request $request
     * @return array
     * @since 1.0.0
     */
    public function getExpands($request = null)
    {
        $expands = [];
        if ($request && $request->has('expand')) {
            //3月11日更新,详见 readme.md
            $expands = explode(',', $request->get('expand'));
        }
        return $expands;
    }
    /**
     * 指定排序
     *
     * @param Builder $query
     * @param Request|null $request
     * @return mixed
     * @since 1.0.0
     */
    public function setSort($query, $request = null)
    {
        if ($request && $request->has('sort')) {
            $sort = $request->get('sort');
            $sign = substr($sort, 0, 1);
            if ($sign == '-') {
                $sort_direction = 'desc';
                $sort = trim($sort, '-');
            } else {
                $sort_direction = 'asc';
            }
            $available_fields = array_keys(DB::getDoctrineSchemaManager()
                ->listTableColumns($query->getModel()->getTable()));
            $sort_arguments = explode('.', $sort);
            $arg_count = count($sort_arguments);
            if ($arg_count == 2) {
                if (in_array($sort_arguments[0], $this->extraFields())) {
                    $query->modelJoin($sort_arguments[0], $sort_arguments[1]);
                    $table_name = $this->detectTableNameFromRelation($sort_arguments[0]);
                    $query->orderBy($table_name . '.' . $sort_arguments[1], $sort_direction);
                }
            } else {
                if (in_array($sort_arguments[0], $available_fields)) {
                    $query->orderBy($sort_arguments[0], $sort_direction);
                }
            }
        }
        return $query;
    }
    /**
     * 确定表的名称
     *
     * @param string $relation
     * @return string
     * @since 1.0.0
     */
    protected function detectTableNameFromRelation($relation)
    {
        return $this->$relation()->getRelated()->getTable();
    }
    /**
     * This determines the foreign key relations automatically to prevent the need to figure out the columns.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $relation_name
     * @param string $sortColumn
     * @param string $operator
     * @param string $type
     * @param bool $where
     * @return \Illuminate\Database\Query\Builder
     * @since 1.0.0
     *
     * @see http://laravel-tricks.com/tricks/automatic-join-on-eloquent-models-with-relations-setup
     */
    public function scopeModelJoin($query, $relation_name, $sortColumn, $operator = '=', $type = 'left', $where = false)
    {
        $relation = $this->$relation_name();
        $table = $relation->getRelated()->getTable();
        $one = $this->getTable() . '.' . $relation->getForeignKey();
        $two = $table . '.' . $relation->getOtherKey();
        if (method_exists($relation, 'getTable')) {
            $three = $relation->getQualifiedParentKeyName();
            $four = $relation->getForeignKey();
            $query->join($relation->getTable(), $three, $operator, $four, $type, $where);
            $one = $table . '.' . $relation->getRelated()->primaryKey;
            $two = $relation->getOtherKey();
        }
        if (empty($query->columns)) {
            $query->select($this->getTable() . ".*");
        }
        $query->addSelect(new Expression("`$table`.`$sortColumn`"));
        return $query->join($table, $one, $operator, $two, $type, $where);
    }
    /**
     * 获取过滤选项
     *
     * @param $params
     * @param $request
     * @param string $filterField
     * @return array
     * @since 1.0.3
     */
    public function getFilter($params, $request, $filterField = 'filter')
    {
        $filter = [];
        if (isset($params[$filterField]) && is_array($params[$filterField])) {
            $filter = $params[$filterField];
        }
        if ($request->input($filterField)) {
            $filter = array_merge($filter, json_decode($request->input($filterField), true));
        }
        return $filter;
    }

    /**
     * 获取limit, 分页的的时候无效
     */

    public function setLimit($query, $request = null)
    {
        if ($request && $request->has('limit')) {
            $query->limit($request->get('limit'));
        }
        return $query;
    }

    /**
     * offset, 分页的的时候无效
     */

    public function setOffset($query, $request = null)
    {
        if ($request && $request->has('offset')) {
            $query->offset($request->get('offset'));
        }
        return $query;
    }
    public function setSelect($query, $request = null)
    {
        if ($request && $request->has('select')) {
            $select = $request->get('select') ?? '*';
            if ($select !== '*') {
                $select = explode(',', $select);
            }
            $query->select($select);
        }
        return $query;
    }
    public function getScopes()
    {
        return $this->filterScopes ?? [];
    }
    public function addScope(&$query, string $scope, $value)
    {
        $query->{$scope}($value);
    }
}
