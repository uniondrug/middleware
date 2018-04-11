<?php
/**
 * CorsMiddleware.php
 *
 */

namespace Uniondrug\Middleware\Middlewares;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Middleware;

class CorsMiddleware extends Middleware
{
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        $response = $next($request);
        if ($response instanceof ResponseInterface) {
            $response->setHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'));
            $response->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Cookie');
            $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, PUT, OPTIONS');
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
