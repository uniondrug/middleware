<?php
/**
 * 中间件管理器，主要负责中间件的注册、获取。
 *
 * @author XueronNi
 * @date   2018-01-20
 */

namespace Uniondrug\Middleware;

use Phalcon\Config;
use Phalcon\Text;
use Uniondrug\Framework\Injectable;
use Uniondrug\Middleware\Middlewares\CacheMiddleware;
use Uniondrug\Middleware\Middlewares\CorsMiddleware;
use Uniondrug\Middleware\Middlewares\FaviconIcoMiddleware;
use Uniondrug\Middleware\Middlewares\PoweredByMiddleware;
use Uniondrug\Middleware\Middlewares\TraceMiddleware;

/**
 * Class MiddlewareManager
 *
 * @package UniondrugMiddleware
 */
class MiddlewareManager extends Injectable
{
    /**
     * 中间件定义表。
     *
     * @var array
     */
    protected $definitions = [
        'cors'    => CorsMiddleware::class,
        'trace'   => TraceMiddleware::class,
        'cache'   => CacheMiddleware::class,
        'favicon' => FaviconIcoMiddleware::class,
        'powered' => PoweredByMiddleware::class,
    ];

    /**
     * 中间件使用映射。
     *
     * @var array
     */
    protected $middlewareGroup = [];

    /**
     * 是否使用注解定义中间件。如果不允许，则只能显示定义。
     * <code>
     * </code>
     *
     * @var bool
     */
    protected $useAnnotation = true;

    /**
     * MiddlewareManager constructor.
     *
     * @param array $definitions
     */
    public function __construct($definitions = [])
    {
        $this->definitions = array_merge($this->definitions, $definitions);
    }

    /**
     * 注册中间件
     *
     * @param string                                           $name
     * @param string|\Uniondrug\Middleware\MiddlewareInterface $definition
     */
    public function register($name, $definition)
    {
        $this->definitions[$name] = $definition;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function get($name)
    {
        return $this->definitions[$name];
    }

    /**
     * 检查一个中间件是否有注册
     *
     * @param string $alias 中间件名称
     *
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->definitions[$alias]);
    }

    /**
     * 绑定一个中间件到控制器，以及其方法上
     *
     * @param  string|object $controllerName 控制器
     * @param  array|string  $middlewares    需要绑定的中间件名称
     * @param array          $options
     *
     * @return $this
     */
    public function bind($controllerName, $middlewares, $options = [])
    {
        if (is_object($controllerName)) {
            $controllerName = get_class($controllerName);
        }

        if (!empty($options)) {
            if (isset($options['only'])) {
                foreach ($options['only'] as $actionMethod) {
                    if (!method_exists($controllerName, $actionMethod)) {
                        throw new \RuntimeException(sprintf('%s is not a valid action name', $actionMethod));
                    }
                    $groupName = $controllerName . '::' . $actionMethod;
                    $this->bindToGroup($groupName, $middlewares);
                }
            } else if (isset($options['except'])) {
                $methods = get_class_methods($controllerName);
                foreach ($methods as $actionMethod) {
                    if (Text::endsWith($actionMethod, 'Action') && !in_array($actionMethod, $options['except'])) {
                        $groupName = $controllerName . '::' . $actionMethod;
                        $this->bindToGroup($groupName, $middlewares);
                    }
                }
            }
        } else {
            $this->bindToGroup($controllerName, $middlewares);
        }

        return $this;
    }

    /**
     * @param $groupName
     * @param $middlewares
     */
    protected function bindToGroup($groupName, $middlewares)
    {
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }
        if (!isset($this->middlewareGroup[$groupName])) {
            $this->middlewareGroup[$groupName] = [];
        }
        $this->middlewareGroup[$groupName] = array_unique(array_merge($this->middlewareGroup[$groupName], $middlewares));
    }

    /**
     * 是否启用注解
     *
     * @param $useAnnotation
     */
    public function useAnnotation($useAnnotation)
    {
        $this->useAnnotation = $useAnnotation;
    }

    /**
     * @param object $handler      控制器对象
     * @param string $actionMethod 方法
     *
     * @return array|mixed
     */
    public function getMiddlewares($handler, $actionMethod)
    {
        $controllerName = get_class($handler);

        // 全局中间件
        $middlewares = $this->config->path('middleware.global', []);
        if ($middlewares instanceof Config) {
            $middlewares = $middlewares->toArray();
        }

        // 控制器的中间件
        if (isset($this->middlewareGroup[$controllerName])) {
            $middlewares = array_merge($middlewares, $this->middlewareGroup[$controllerName]);
        }

        // 读取控制器上是否有Middleware注解
        if ($this->useAnnotation) {
            $annotationsService = $this->annotations;
            $controllerAnnotations = $annotationsService->get($controllerName)->getClassAnnotations();
            if ($controllerAnnotations && $controllerAnnotations->has('Middleware')) {
                foreach ($controllerAnnotations->getAll('Middleware') as $controllerAnnotation) {
                    $middlewares = array_merge($middlewares, ($controllerAnnotation->getArguments() ?: []));
                }
            }
        }

        // 方法的中间件
        $groupName = $controllerName . '::' . $actionMethod;
        if (isset($this->middlewareGroup[$groupName])) {
            $middlewares = array_merge($middlewares, $this->middlewareGroup[$groupName]);
        }

        // 读取方法上是否有Middleware注解
        if ($this->useAnnotation) {
            $annotationsService = $this->annotations;
            $methodAnnotations = $annotationsService->getMethod($controllerName, $actionMethod);
            if ($methodAnnotations && $methodAnnotations->has('Middleware')) {
                foreach ($methodAnnotations->getAll('Middleware') as $methodAnnotation) {
                    $middlewares = array_merge($middlewares, ($methodAnnotation->getArguments() ?: []));
                }
            }
        }

        // 返回当前 handler/action 对应的中间件名称列表
        return array_unique($middlewares);
    }
}
