<?php
/**
 * CorsMiddleware.php
 *
 */

namespace Uniondrug\Middleware\Middlewares;

use Phalcon\Http\RequestInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Middleware;

class CorsMiddleware extends Middleware
{
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        $response = $next($request);
        $response->setHeader('Access-Control-Allow-Headers', 'Authorization, Origin, X-Requested-With, Content-Type, Accept');
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'PUT, POST, GET, OPTIONS, DELETE');
        return $response;
    }
}
