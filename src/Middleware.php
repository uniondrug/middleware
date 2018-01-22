<?php
/**
 * Middleware.php
 *
 */

namespace UniondrugMiddleware;

use Phalcon\Di\Injectable;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;

abstract class Middleware extends Injectable implements MiddlewareInterface
{
    /**
     * @param RequestInterface  $request
     * @param DelegateInterface $next
     *
     * @return ResponseInterface
     */
    abstract public function handle(RequestInterface $request, DelegateInterface $next);

    /**
     * @param \Phalcon\Http\RequestInterface $request
     * @param DelegateInterface              $next
     *
     * @return Response|mixed|ResponseInterface
     * @throws \Exception
     */
    public function process(RequestInterface $request, DelegateInterface $next)
    {
        try {
            $response = call_user_func_array([$this, 'handle'], [$request, $next]);
            if ($response === false || $response instanceof ResponseInterface) {
                return $response;
            }
            if (is_string($response)) {
                return new Response($response);
            }

            return $response;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @param \Phalcon\Http\RequestInterface $request
     * @param DelegateInterface              $delegate
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __invoke(RequestInterface $request, DelegateInterface $delegate)
    {
        return $this->process($request, $delegate);
    }
}
