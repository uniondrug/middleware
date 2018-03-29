<?php
/**
 * PoweredByMiddleware.php
 *
 */

namespace Uniondrug\Middleware\Middlewares;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Middleware;

class PoweredByMiddleware extends Middleware
{
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        $response = $next($request);
        if ($response instanceof ResponseInterface) {
            $response->setHeader('X-Powered-BY', $this->config->path('middleware.powered_by', 'UnionDrug'));
        }

        return $response;
    }
}
