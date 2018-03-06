<?php
/**
 * Phalcon的Dispatcher自身调用Controller::Action的调用，改写后，以Middleware的形式加入到中间件的链式调用中。
 *
 * @author XueronNi
 * @date   2018-01-22
 */

namespace Uniondrug\Middleware\Middlewares;

use Phalcon\Http\RequestInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Middleware;

class DispatcherMiddleware extends Middleware
{
    /**
     * @var string
     */
    protected $handler;

    /**
     * @var string
     */
    protected $actionMethod;

    /**
     * @var array
     */
    protected $params;

    /**
     * DispatcherMiddleware constructor.
     *
     * @param       $handler
     * @param       $actionMethod
     * @param array $params
     */
    public function __construct($handler, $actionMethod, $params = [])
    {
        $this->handler = $handler;
        $this->actionMethod = $actionMethod;
        $this->params = $params;
    }

    /**
     * @param \Phalcon\Http\RequestInterface         $request
     * @param \Uniondrug\Middleware\DelegateInterface $next
     *
     * @return mixed|\Phalcon\Http\ResponseInterface
     */
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        return call_user_func_array([$this->handler, $this->actionMethod], $this->params);
    }
}
