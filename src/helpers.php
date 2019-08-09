<?php

if (!function_exists('request_intersect')) {
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

if (!function_exists('make_tree')) {
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
if (!function_exists('make_tree')) {

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
                        $field = json_encode($field, JSON_UNESCAPED_UNICODE);
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
