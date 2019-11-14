<?php

if (!function_exists('requestIntersect')) {
    /**
     * request intersect
     *
     * @param $keys
     * @return array|ø
     */
    function requestIntersect($keys, $filter = false)
    {
        $return = request()->only(is_array($keys) ? $keys : func_get_args());
        return $filter ? array_filter($return) : $return;
    }
}

if (!function_exists('makeTree')) {
    /**
     * @param array $list
     * @param int $parentId
     * @return array
     */
    function makeTree(array $list, $parentId = 0)
    {
        $tree = [];
        if (empty($list)) {
            return $tree;
        }

        $newList = [];
        foreach ($list as $k => $v) {
            $newList[$v['id']] = $v;
        }

        foreach ($newList as $value) {
            if ($parentId == $value['parent_id']) {
                $tree[] = &$newList[$value['id']];
            } elseif (isset($newList[$value['parent_id']])) {
                $newList[$value['parent_id']]['children'][] = &$newList[$value['id']];
            }
        }

        return $tree;
    }
}
if (!function_exists('updateBatch')) {

    /*
     * ----------------------------------
     * update batch
     * ----------------------------------
     * multiple update in one query
     *
     * multipleData ( required | array of array [['id'=>1,'title'=>'new']...])
     * * [0] as primaryKey for where primaryKey in ([0]...)
     * tablename( required | string )
     */
    function updateBatch($multipleData = [], $tableName = "articles")
    {

        if ($tableName && !empty($multipleData)) {
            // column or fields to update
            $updateColumn = array_keys($multipleData[0]);
            $referenceColumn = $updateColumn[0]; //e.g id
            unset($updateColumn[0]);
            $whereIn = "";

            $q = "UPDATE `" . $tableName . "` SET ";
            foreach ($updateColumn as $uColumn) {
                $q .= '`' . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $field = $data[$uColumn];
                    if (is_array($field)) {
                        // $field = json_encode($field, JSON_UNESCAPED_UNICODE);
                        $field = addslashes(json_encode($field));
                    }
                    $q .= "WHEN " . $referenceColumn . " = " . $data[$referenceColumn] . " THEN '" . $field . "' ";
                }
                $q .= "ELSE `" . $uColumn . "` END, ";
            }
            foreach ($multipleData as $data) {
                $whereIn .= "'" . $data[$referenceColumn] . "', ";
            }
            $q = rtrim($q, ", ") . " WHERE " . $referenceColumn . " IN (" . rtrim($whereIn, ', ') . ")";

            // Update
            return \DB::update(DB::raw($q));

        } else {
            return false;
        }
    }
}
if (!function_exists('scopeSetFilterAndRelationsAndSort')) {
    /**
     * collection 通用 filter
     * @param  [type] $query   [description]
     * @param  [type] $request [description]
     * @param  array  $params  [description]
     * @return [type]          [description]
     */
    function scopeSetFilterAndRelationsAndSort($query, $request = null, $params = [])
    {
        $query = setFilter($query, $params, $request);
        $query = setSort($query, $request);
        return $query;
    }
}

if (!function_exists('setFilter')) {
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
    function setFilter($query, $params, $request = null)
    {
        $filter = getFilter($params, $request);
        if ($filter) {
            foreach ($filter as $key => $value) {
                $keys_array = explode('.', $key);
                $relation = null;
                $table_name = null;
                $field_name = $keys_array[0];
                $query = addFilterCondition($query, $field_name, $value);
            }
        }
        return $query;
    }
}
if (!function_exists('getFilter')) {
/**
 * 获取过滤选项
 *
 * @param $params
 * @param $request
 * @param string $filterField
 * @return array
 * @since 1.0.3
 */
    function getFilter($params, $request, $filterField = 'filter')
    {
        $filter = [];
        if (isset($params) && is_array($params)) {
            $filter = $params;
        }
        if ($request->input($filterField)) {
            $filter = array_merge($filter, json_decode($request->input($filterField), true));
        }
        return $filter;
    }
}
if (!function_exists('addFilterCondition')) {
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
    function addFilterCondition($query, $key, $value, $table_name = null)
    {
        $allow_operations = ['=', '>', '<', '>=', '<=', '<>', 'not in', 'in', 'like'];
        if ($table_name) {
            $key = $table_name . '.' . $key;
        }
        if (is_array($value)) {
            if (isset($value['isNull']) && $value['isNull'] === true) {
                $query = $query->whereNull($key);
            } elseif (isset($value['isNull']) && $value['isNull'] === false) {
                $query = $query->whereNotNull($key);
            }
            $pattern = "/^(\d{2}).(\d{2}).(\d{4})$/";
            if (isset($value['operation']) && in_array(strtolower($value['operation']), $allow_operations) && isset($value['value'])) {
                if (strtolower($value['operation']) == 'in' && is_array($value['value'])) {
                    $query = $query->whereIn($key, $value['value']);
                } elseif (strtolower($value['operation']) == 'not in' && is_array($value['value'])) {
                    $query = $query->whereNotIn($key, $value['value']);
                } elseif (strtolower($value['operation']) == 'like') {
                    $query = $query->where($key, 'like', "%{$value['value']}%");
                } else {
                    $value['value'] = preg_match($pattern, $value['value']) ? (new \DateTime($value['value']))->format("Y-m-d") : $value['value'];
                    $query = $query->where($key, $value['operation'], \DB::raw($value['value']));
                }
            } elseif (isset($value['from']) || isset($value['to'])) {
                if (isset($value['from']) && $value['from']) {
                    $from = preg_match($pattern, $value['from']) ? (new \DateTime($value['from']))->format("Y-m-d") : $value['from'];
                    $query = $query->where($key, '>=', $from);
                }
                if (isset($value['to']) && $value['to']) {
                    $to = preg_match($pattern, $value['to']) ? (new \DateTime($value['to']))->format("Y-m-d") : $value['to'];
                    $query = $query->where($key, '<=', $to);
                }
            }
        } else {
            $query = $query->where($key, $value);
        }
        return $query;
    }
}
if (!function_exists('setSort')) {
/**
 * 指定排序
 *
 * @param Builder $query
 * @param Request|null $request
 * @return mixed
 * @since 1.0.0
 */
    function setSort($query, $request = null)
    {
        if ($request && $request->has('sort')) {
            $sort = $request->get('sort');
            $sign = substr($sort, 0, 1);
            if ($sign == '-') {
                $sort = trim($sort, '-');
                return $query->sortByDesc($sort);
            } else {
                return $query->sortBy($sort);
            }
        }
        return $query;
    }
}
