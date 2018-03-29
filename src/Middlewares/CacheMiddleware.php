<?php
/**
 * CacheMiddleware.php
 *
 */

namespace Uniondrug\Middleware\Middlewares;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Middleware;

class CacheMiddleware extends Middleware
{
    /**
     * @param \Phalcon\Http\RequestInterface          $request
     * @param \Uniondrug\Middleware\DelegateInterface $next
     *
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        // 只在开启缓存的情况下
        if (!$this->di->has('cache')) {
            return $next->process($request);
        }

        // 只对GET请求可以Cache
        if ('GET' !== $request->getMethod()) {
            return $next->process($request);
        }

        // 缓存针对url做KEY
        $uri = $request->getURI();
        $query = $request->getQuery();
        if (!empty($query)) {
            ksort($query);
            $query = http_build_query($query);
            $uri = $uri . '?' . $query;
        }
        $key = md5($uri);

        $cache = $this->cache->get($key);
        if ($cache) {
            $response = new Response($cache['content'], 200);
            foreach ($cache['headers'] as $k => $v) {
                $response->setHeader($k, $v);
            }

            return $response;
        }

        $response = $next->process($request);
        if ($response instanceof ResponseInterface && 200 === $response->getStatusCode()) {
            $ttl = $this->config->path('middleware.cache.lifetime', 60);
            $expireAt = time() + $ttl;
            $content = $response->getContent();
            $headers = $response->getHeaders()->toArray();
            $this->cache->save($key, ['headers' => $headers, 'content' => $content], $ttl);
            $response->setExpires(new \DateTime('@' . $expireAt));
            $response->setHeader('X-Cache', $key);
        }

        return $response;
    }

}
