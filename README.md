# UniondrugMiddleware 中间件组件

基于Phalcon的uniondrug/framework项目中，增加中间件处理流程的支持。

## 感谢

`https://github.com/limingxinleo/x-phalcon-middleware`
`https://github.com/fastdlabs/middleware`

## 安装

```
$ composer requre uniondrug/middleware
```

## 使用

### 修改配置文件，引入中间件组件

在`app.php`配置文件中增加中间件组件的注册

```php
<?php
return [
    'default' => [
        ...
        'providers'           => [
            \UniondrugMiddleware\MiddlewareServiceProvider::class,
        ],
        ...
    ],
];

```

添加`middlewares.php`配置文件，定义中间件

```php
<?php
/**
 * middleware.php
 *
 */
return [
    'default' => [
        'test1' => \App\Middlewares\Test1Middleware::class,
        'test2' => \App\Middlewares\Test2Middleware::class,
        'test3' => \App\Middlewares\Test3Middleware::class,
        'test4' => \App\Middlewares\Test4Middleware::class,
        'test5' => \App\Middlewares\Test5Middleware::class,
    ]
];
```


### 开发中间件

创建中间件。中间件必须从`UniondrugMiddleware\Middleware`继承。实现`handle`方法。该方法有两个参数：`request`是Phalcon的`Phalcon\Http\Request`对象，`next`是下一个中间件代理。
在处理过程中，可以直接返回一个`Phalcon\Http\Response`对象，终止后续的中间件，或者返回下一个中间件代理的处理结果（链式传递）。

```php
<?php
/**
 * Test1Middleware.php
 *
 */

namespace App\Middlewares;

use Phalcon\Http\RequestInterface;
use UniondrugMiddleware\DelegateInterface;
use UniondrugMiddleware\Middleware;

class Test1Middleware extends Middleware
{
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        echo "Test1.0\n";
        $response = $next($request);
        echo "Test1.1\n";
        return $response;
    }
}
```

中间件开发好后，需要在`middlewares.php`配置文件中注册一个别名，在使用过程中以别名调用。

### 使用中间件

中间件在`控制器`中使用。在`控制器`中有两种方法定义需要使用哪些中间件。

1、在beforeExecuteRoute()方法中配置。

通过`middlewareManager`组件的`bind`方法，指派对应的中间件。
其中第一个参数是`控制器`本身，第二个参数是一组`中间件`别名，可选的第三个参数可以指明`中间件`的绑定范围：
`only` 指只有在列表中的 `action` 使用该组中间件
`except` 指除了列表中的 `action` 以外的所有方法使用该组`中间件`
如果`only`/`except`都不指定，那么整个`控制器`的方法都会使用改组`中间件`

2.通过注解的方法定义中间件

注解`Middleware`定义当前控制器或者方法使用的中间别名。可以定义多个。

```php
<?php
/**
 * IndexController.php
 *
 */
namespace App\Controllers;

use Phalcon\Mvc\Controller;

/**
 * Class IndexController
 *
 * @package App\Controllers
 * @Middleware('test2')
 */
class IndexController extends Controller
{
    public function beforeExecuteRoute($dispatcher)
    {
        $this->middlewareManager->bind($this, ['test3', 'test4']);
        $this->middlewareManager->bind($this, ['test5'], ['only' => ['indexAction']]);
    }

    /**
     * @Middleware('test1')
     */
    public function indexAction()
    {
        //var_dump($this->middlewareManager);
        return $this->response->setJsonContent(['msg' => memory_get_usage()]);
    }

    public function showAction()
    {
        return $this->response->setJsonContent(['msg' => 'show']);
    }
}

```

### 中间件调用的顺序
`Action方法`的注解定义的中间件 -> `bind()`方法绑定到的`Action方法`上的中间件 -> `控制器`的注解上定义的中间件 -> `bind()`方法绑定到`控制器`上的中间件。

`bind()`方法定义超过一个`中间件`时，从后到先倒序执行。

比如上面例子里面的`indexAction`被调用时，`中间件`的执行顺序是：

test1 -> test5 -> test2 -> test4 -> test3