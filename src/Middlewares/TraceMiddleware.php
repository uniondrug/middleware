<?php
/**
 * TraceMiddleware.php
 *
 * 配置文件：
 * trace.php
 *
 * trace.service  跟踪服务名称
 * trace.route    跟踪服务路由
 */

namespace Uniondrug\Middleware\Middlewares;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;
use Uniondrug\Middleware\DelegateInterface;
use Uniondrug\Middleware\Middleware;

class TraceMiddleware extends Middleware
{
    /**
     * @param \Phalcon\Http\RequestInterface          $request
     * @param \Uniondrug\Middleware\DelegateInterface $next
     *
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Exception
     */
    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        // 0. 请求基本信息
        $service = $this->config->path('app.appName', 'UniondrugService');
        $rTime = microtime(1);
        $method = $request->getMethod();
        $rip = $request->getClientAddress(true);
        $req = $request->getServer('REQUEST_URI');
        $rua = $request->getUserAgent();
        $query = $request->getHeader('QUERY_STRING');
        $host = $request->getHeader('host');
        $port = $request->getServer('SERVER_PORT');
        $proto = $request->getServer('SERVER_PROTOCOL');

        $scheme = $request->getServer('REQUEST_SCHEME');
        if (!$scheme) {
            if (!empty($port) && $port == '443') {
                $scheme = 'https';
            } else if (!empty($port) && $port == '80') {
                $scheme = 'http';
            } else  if (!empty($proto)){
                $scheme = explode('/', $proto)[0];
            } else {
                $scheme = 'http';
            }
        } else {
            $scheme = strtolower($scheme);
        }

        $requestUri = sprintf("%s://%s", $scheme, $host);
        if (!empty($port)) {
            if (('https' === $scheme && '443' == $port) || ('http' === $scheme && '80' == $port)) {
                //
            } else {
                $requestUri = $requestUri . ':' . $port;
            }
        }
        if (!empty($req)) {
            $requestUri = $requestUri . rtrim($req, '/');
        }
        if (!empty($query)) {
            $requestUri = $requestUri . '?' . $query;
        }

        // 1. 获取上游跟踪链ID，或者启动跟踪ID
        $traceId = $request->getHeader('X-TRACE-ID');
        if (!$traceId) {
            $traceId = $this->security->getRandom()->hex(10);
            $_SERVER['HTTP_X_TRACE_ID'] = $traceId;
        }

        // 2. 保存上一个节点ID
        $parentSpanId = $request->getHeader('X-SPAN-ID');
        if (!$parentSpanId) { // 请求的起点
            $parentSpanId = '';
        }

        // 3. 设置节点ID，生成当前节点ID，并放入当前环境，便于往下传递
        $spanId = $this->security->getRandom()->hex(10);
        $_SERVER['HTTP_X_SPAN_ID'] = $spanId;

        // 4. 正常处理后续事宜
        $exception = null;
        $error = '';
        $statusCode = '';
        try {
            $response = $next($request);

            $statusCode = $response->getStatusCode();
            if (!$statusCode) {
                $statusCode = '200';
            }
            if ($response instanceof ResponseInterface) {
                $response->setHeader('X-TRACE-ID', $traceId);
                $response->setHeader('X-SPAN-ID', $spanId);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $exception = $e;
        }

        // 5. 记录时间
        $sTime = microtime(1);
        $tTime = $sTime - $rTime;

        // 5. 记录信息
        $this->di->getLogger('trace')->debug(sprintf("[TraceMiddleware] service=%s, traceId=%s, spanId=%s, pSpanId=%s, sr=%s, ss=%s, t=%s, method=%s, req=%s, statusCode=%s, ua=%s, error=%s",
            $service, $traceId, $spanId, $parentSpanId, $rTime, $sTime, $tTime, $method, $requestUri, $statusCode, $rua, $error
        ));

        // 6. 发送到中心
        if ($this->config->path('trace.enable', false)) {
            try {
                if ($this->di->has('traceClient')) {
                    $this->traceClient->send([
                        'traceId'      => $traceId,         // traceId 每个请求的唯一ID
                        'parentSpanId' => $parentSpanId,    // parentId 上一级节点的spanId
                        'spanId'       => $spanId,          // id 当前节点的spanId
                        'timestamp'    => $rTime,           // timestamp 开始时间，时间戳
                        'duration'     => $tTime,           // duration 耗时
                        'ip'           => $rip,             // 请求方的IP地址
                        'service'      => $service, // 当前服务的名称，在app.conf里面配置
                        'sr'           => $rTime,           // ServerReceive, 收到请求的时间
                        'ss'           => $sTime,           // ServerSend，完成后发送的时间
                        'ua'           => $rua,              // UserAgent
                        'method'       => $method,
                        'req'          => $requestUri,             // 请求的路径
                        'statusCode'   => $statusCode,
                        'error'        => $error,
                    ]);
                }
            } catch (\Exception $e) {
                $this->di->getLogger('trace')->error(sprintf("[TraceMiddleware] Send to trace server failed: %s", $e->getMessage()));
            }
        }

        // 7. 返回
        if ($exception !== null) {
            throw $exception;
        } else {
            return $response;
        }
    }
}
