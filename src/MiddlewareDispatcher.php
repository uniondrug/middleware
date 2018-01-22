<?php
/**
 * 中间件分发器，用于调用中间件处理逻辑。
 *
 * @author XueronNi
 * @date   2018-01-20
 *
 */

namespace UniondrugMiddleware;

use LogicException;
use Phalcon\Di\Injectable;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;
use SplStack;

class MiddlewareDispatcher extends Injectable
{
    /**
     * @var SplStack
     */
    protected $stack;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Dispatcher constructor.
     *
     * @param $stack
     */
    public function __construct(array $stack = [])
    {
        $this->stack = new SplStack();

        foreach ($stack as $value) {
            $this->before($value);
        }
    }

    /**
     * @param MiddlewareInterface $middleware
     *
     * @return $this
     */
    public function after(MiddlewareInterface $middleware)
    {
        $this->stack->unshift($middleware);

        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     *
     * @return $this
     */
    public function before(MiddlewareInterface $middleware)
    {
        $this->stack->push($middleware);

        return $this;
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $resolved = $this->resolve();

        $response = $resolved->process($request);

        return $response;
    }

    /**
     * @return DelegateInterface
     */
    private function resolve()
    {
        if (!$this->stack->isEmpty()) {
            return new Delegate(function (RequestInterface $request) {
                return $this->stack->shift()->process($request, $this->resolve());
            });
        }

        return new Delegate(function () {
            throw new LogicException('unresolved request: middleware stack exhausted with no result');
        });
    }
}
