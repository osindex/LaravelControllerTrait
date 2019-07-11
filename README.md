# laravel-controller-trait

## install
```
composer require osi/laravel-controller-trait
```
## useage
```
php artisan trait:controller
php artisan trait:model

```
## filter
```
/message?filter={"created_at":{"from":"2016-02-20","to":"2016-02-24 23:59:59"}, "id":{"operation":"not in", "value":[2,3,4]}}
/message?filter={"id":{"from":2,"to":5}}
/message?filter={"id":{"to":5}} or /message?filter={"id":{"operation":"<=","value":5}}
/message?filter={"updated_at":{"isNull":true}}
/message?filter={"answer":{"operation":"like","value":"Partial search string"}}
/message?filter={"answer":"Full search string"}
/message?filter={"user.name":"asd"} # 关联搜索
/message?filter={"id":1}

# 暂时只支持单字段排序
/message?sort=id
/message?sort=-id
/message?sort=user.name

# 关联搜索
/message?expand=user 
response: { "id": 1, "message": "some message", "user_id": 1, ... "user": { "id": 1, "name": "Some username", ... } }

# 关联搜索子集，获取特定字段
/message?expand=archives,user.recordable:id/status

# 【1.8】新增scope搜索
//User Model
<?php

新增允许的filterScopes属性
protected $filterScopes = ['QueryLike'];
// laravel实现姓名或电话搜索
public function scopeQueryLike($query, $param)
{
    return $query->where(function ($querySec) use ($param) {
        return $querySec->where('name', 'like', '%' . $param . '%')->orWhere('phone', 'like', '%' . $param . '%');
    });
}
/user?filter={"QueryLike":2333}

# 【1.9】新增JSON搜索(jsoncontains,jsonlength) 
##注：目前仅有jsonlength 支持type属性
/message?filter={"json->paramA":"233"}
/message?filter={"json->array":{"operation":"jsonlength","type":">","value":5}}
/message?filter={"json->array":{"operation":"jsoncontains","value":5}}

```
## func
```
Don not code normal controller func.
```
