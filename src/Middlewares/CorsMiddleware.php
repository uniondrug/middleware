<?php
/**
 * CorsMiddleware.php
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
        try {
            $response = $next($request);
        } catch(\Exception $e) {
            $response = $this->serviceServer->withError($e->getMessage(), $e->getCode());
        }
        // date:2018/09/14
        // author:付义兵
        // 跨域设置迁移至NGINX
        // PHP代码中的设置已经多余, 不需要再处理
        // if ($response instanceof ResponseInterface) {
        //    $response->setHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'));
        //    $response->setHeader('Access-Control-Allow-Headers', 'Authorization, Origin, X-Requested-With, Content-Type, Accept, Cookie');
        //    $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, PUT, OPTIONS');
        //    $response->setHeader('Access-Control-Allow-Credentials', 'true');
        // }
        return $response;
    }
}
