<?php
/**
 * FaviconIcoMiddleware.php
 *
 */

namespace Uniondrug\Middleware\Middlewares;

use Phalcon\Http\RequestInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Exception;
use Uniondrug\Middleware\Middleware;

class FaviconIcoMiddleware extends Middleware
{
    /**
     * @param \Phalcon\Http\RequestInterface          $request
     * @param \Uniondrug\Middleware\DelegateInterface $next
     *
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Uniondrug\Middleware\Exception
     */
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        if ($request->getURI() === '/favicon.ico') {
            throw new Exception('access favicon.ico');
        }

        return $next($request);
    }
}
