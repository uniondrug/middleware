<?php
/**
 * 中间件的分发入口。通过改写Phalcon的Dispatcher实现中间件的链式调用。
 *
 * @author XueronNi
 * @date   2018-01-22
 *
 */

namespace UniondrugMiddleware;

use Phalcon\Mvc\Dispatcher as PhalconDispatcher;
use UniondrugMiddleware\Middlewares\DispatcherMiddleware;

class Dispatcher extends PhalconDispatcher
{
    /**
     * @desc   重写 callActionMethod 方法
     * @author limx
     *
     * @param mixed  $handler
     * @param string $actionMethod
     * @param array  $params
     *
     * @return mixed
     */
    public function callActionMethod($handler, $actionMethod, ?array $params = [])
    {
        // 中间件管理器
        if ($this->getDi()->has('middlewareManager')) {
            $middlewareManager = $this->getDI()->getShared('middlewareManager');
        } else {
            $middlewareManager = new MiddlewareManager();
        }

        // 中间件分发器
        $middlewareDispatcher = new MiddlewareDispatcher();

        // 将当期请求对应的中间件一一纳入分发器中
        $middlewares = $middlewareManager->getMiddlewares($handler, $actionMethod);
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $middlewareDispatcher->before($middleware);
            } else if (is_string($middleware)) {
                if (!$middlewareManager->has($middleware)) {
                    throw new \RuntimeException(sprintf('Middleware %s not defined', $middleware));
                }
                $definition = $middlewareManager->get($middleware);
                if (is_array($definition)) {
                    foreach ($definition as $item) {
                        $middlewareDispatcher->before(is_string($item) ? new $item : $item);
                    }
                } else {
                    $middlewareDispatcher->before(is_string($definition) ? new $definition : $definition);
                }
            } else {
                throw new \RuntimeException(sprintf('Middleware %s not supported', $middleware));
            }
        }

        // Default Dispatcher method
        $middlewareDispatcher->before(new DispatcherMiddleware($handler, $actionMethod, $params));

        return $middlewareDispatcher->dispatch($this->getDi()->getRequest());
    }
}
