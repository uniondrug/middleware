# Uniondrug Middleware 中间件组件

基于Phalcon的uniondrug/framework项目中，增加中间件处理流程的支持。

## 感谢

`https://github.com/limingxinleo/x-phalcon-middleware`
`https://github.com/fastdlabs/middleware`

## 安装

```
$ composer require uniondrug/middleware
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
            \Uniondrug\Middleware\MiddlewareServiceProvider::class,
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
        // 应用定义的中间件
        'middlewares' => [
            'test1' => \App\Middlewares\Test1Middleware::class,
            'test2' => \App\Middlewares\Test2Middleware::class,
            'test3' => \App\Middlewares\Test3Middleware::class,
            'test4' => \App\Middlewares\Test4Middleware::class,
            'test5' => \App\Middlewares\Test5Middleware::class,
        ],

        // 全局中间件，会应用在全部路由，优先级在应用定义之前
        'global'      => [
            'cors', 'cache', 'favicon', 'trace',
        ],

        // 全局中间件，会应用在全部路由，优先级在应用定义之后
        'globalAfter' => [
            'powered',
        ],

        // 以下是中间件用到的配置参数
        'cache'       => [
            'lifetime' => 60,
        ],
        'powered_by'  => 'Un',
    ],
];
```


### 开发中间件

创建中间件。中间件必须从`Uniondrug\Middleware\Middleware`继承。实现`handle`方法。该方法有两个参数：`request`是Phalcon的`Phalcon\Http\Request`对象，`next`是下一个中间件代理。
在处理过程中，可以直接返回一个`Phalcon\Http\Response`对象，终止后续的中间件，或者返回下一个中间件代理的处理结果（链式传递）。

```php
<?php
/**
 * Test1Middleware.php
 *
 */

namespace App\Middlewares;

use Phalcon\Http\RequestInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Middleware;

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

中间件开发好后，需要在`middleware.php`配置文件中注册一个别名，在使用过程中以别名调用。

### 使用中间件

中间件在`控制器`中使用。在`控制器`中有两种方法定义需要使用哪些中间件。

1、在beforeExecuteRoute()方法中配置。

通过`middlewareManager`组件的`bind`方法，指派对应的中间件。

其中第一个参数是`控制器`本身，

第二个参数是一组`中间件`别名，

可选的第三个参数可以指明`中间件`的绑定范围：
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
 * @Middleware('test1')
 */
class IndexController extends Controller
{
    public function beforeExecuteRoute($dispatcher)
    {
        $this->middlewareManager->bind($this, ['test2']);
        $this->middlewareManager->bind($this, ['test3'], ['only' => ['indexAction']]);
    }

    /**
     * @Middleware('test4', 'test5')
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

-  -> 配置文件定义的`global`定义的中间件
-  -> `bind()`方法绑定到`控制器`上的中间件
-  -> `控制器`的注解上定义的中间件
-  -> `bind()`方法绑定到的`Action方法`上的中间件
-  -> `Action方法`的注解定义的中间件
-  -> 配置文件定义的`globalAfter`定义的中间件

`bind()`方法定义超过一个`中间件`时，从后到先倒序执行。

比如上面例子里面的`indexAction`被调用时，`中间件`的执行顺序是：

`前置调用`：

* test2 -> test1 -> test3 -> test4 -> test5

`后置调用`

* test5 -> test4 -> test3 -> test1 -> test2

### 前置调用 & 后置调用

> NOTE：`Phalcon`的Request对象不同于Psr的HttpRequest对象，它只是PHP原生超全局变量$_GET/$_POST/$_SERVER/$_REQUEST的封装，所以如果想在Middleware中对请求对象进行
改写并且让他影响后续使用，那么直接操作超全局变量。

```
class Test1Middleware extends Middleware
{
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        echo "Test1.0\n"; // 在 $next($request) 之前的代码，将在请求被最终Controller::Action处理之前调用
        $_POST['added_var'] = 'new value'; // 改写请求参数，往Request中添加一个新的POST参数。
                                           // 这样下一个Middleware乃至Controller::Action中使用 `$request` 对象的 `getPost()` 方法就能获取到新的参数值了。
        $response = $next($request);
        echo "Test1.1\n"; // 在 $next($request) 之后的代码，将在请求被最终Controller::Action处理之后调用
        return $response;
    }
}
```

### 内置中间件

组件自带了几个实用的中间件，在`middleware.php`配置文件中增加配置即可使用。

* `cors` 跨域资源共享
* `trace` 跟踪服务
* `cache` 缓存中间件，只对GET请求有效
* `powered` 增加PoweredBy头
* `favicon` 过滤favicon.ico请求
