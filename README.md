# laravel-controller-trait

## install
```
composer require osi/laravel-controller-trait
```
## useage
###artisan
```
php artisan trait:controller
php artisan trait:model

```
###controller&&route
```

use Osi\LaravelControllerTrait\Traits\ControllerBaseTrait; // trait
use App\Admin; //model file
class AdminsController extends Controller
{
    use ControllerBaseTrait;

    public function __construct(Admin $model)
    {
        $this->model = $model;
        $this->resource = '\Osi\LaravelControllerTrait\Resources\Resource';
        $this->collection = '\Osi\LaravelControllerTrait\Resources\Collection';
        $this->functions = get_class_methods(self::class);
    }
}

Route::resources(['admins' => 'AdminsController']);
#以上完成，即提供了常规的增删改查方法

#【1.10】新增批量更新
post:api/admins/batch
request()->all(): [
	['id'=>1,'field'=>'xxx','field2'=>xxx],
	['id'=>2,'field'=>'x2x','field2'=>x2x]
]

#【1.11】剥离基础返回类

use Osi\LaravelControllerTrait\Traits\ResponseBaseTrait; // trait 附带以下方法

dataSuccess
created
accepted
noContent
badRequest
unauthorized
forbidden
unprocesableEtity
success

```
## filter
```
/message?filter={"created_at":{"from":"2016-02-20","to":"2016-02-24 23:59:59"}, "id":{"operation":"not in", "value":[2,3,4]}}
/message?filter={"id":{"from":2,"to":5}}
/message?filter={"id":{"to":5}} or /message?filter={"id":{"operation":"<=","value":5}}
/message?filter={"updated_at":{"isNull":true}}
/message?filter={"answer":{"operation":"like","value":"Partial search string"}}
/message?filter={"answer":"Full search string"}
/message?filter={"user.name":"asd"} # 关联搜索 whereHas
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

# 【1.11】 filterExpand 用法
## 一般我们使用expand对应with方法 如 `model->with('app')` === `?expand=app`
因此 可以使用 filterExpand 完成 `model->with(['app'=>function($q) use($id){$q->where('id',$id)}])` 的类似方法
/message?expand=app&filterExpand={'app.created_at': { 'operation': '>=', 'value': 'now()' },'app.id': 1}

# 【2.0】 collection 集合增加筛选及分页方法
#collect()->setFilterAndRelationsAndSort($request)->paginate((int) $request->pageSize ?? 15)
集合的查询相对数据库较为简单 仅包括集合支持的相关方法 具体查阅以下函数
setFilter
```
## func
```
Don not code normal controller func.
```
