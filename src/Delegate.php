<?php
/**
 * MiddlewareDelegate.php
 *
 */

namespace Uniondrug\Middleware;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;

class Delegate implements DelegateInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param callable $callback function (RequestInterface $request) : ResponseInterface
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Dispatch the next available middleware and return the response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function process(RequestInterface $request)
    {
        return call_user_func($this->callback, $request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request)
    {
        return $this->process($request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function next(RequestInterface $request)
    {
        return $this->process($request);
    }
}
