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
    const TRACE_ID = 'UDS_TRACE_ID';
    const SPAN_ID = 'UDS_SPAN_ID';
    const PARENT_SPAN_ID = 'UDS_PARENT_SPAN_ID';

    public function handle(RequestInterface $request, DelegateInterface $next)
    {
        // 0. 从请求中获取跟踪信息，或者初始化一个请求
        $rTime = microtime(1);
        $req = $request->getHeader('REQUEST_URI');
        $rip = $request->getClientAddress(true);

        // 1. 跟踪链ID
        $traceId = $request->getHeader('X_TRACE_ID');
        if (!$traceId) {
            $traceId = $this->security->getRandom()->hex(10);
            $_SERVER['HTTP_X_TRACE_ID'] = $traceId;
        }

        // 2. 保存上一个节点ID
        $parentSpanId = $request->getHeader('X_SPAN_ID');
        if (!$parentSpanId) { // 请求的起点
            $parentSpanId = '';
        }

        // 3. 重新设置节点ID
        $spanId = $this->security->getRandom()->hex(10);
        $_SERVER['HTTP_X_SPAN_ID'] = $spanId;

        // 4. 正常处理后续事宜
        $response = $next($request);
        $response->setHeader('X_SPAN_ID', $spanId);

        // 5. 记录时间
        $sTime = microtime(1);
        $tTime = $sTime - $rTime;

        // 5. 记录信息
        $this->di->getLogger('trace')->debug(sprintf("[TraceMiddleware] traceId=%s, spanId=%s, pSpanId=%s, ss=%s, sr=%s, t=%s, req=%s",
            $traceId, $spanId, $parentSpanId, $sTime, $rTime, $tTime, $req
        ));

        // 6. 发送到中心
        try {
            if ($this->di->has('traceClient')) {
                $this->traceClient->send([
                    'traceId'      => $traceId,         // traceId 每个请求的唯一ID
                    'parentSpanId' => $parentSpanId,    // parentId 上一级节点的spanId
                    'spanId'       => $spanId,          // id 当前节点的spanId
                    'timestamp'    => $rTime,           // timestamp 开始时间，时间戳
                    'duration'     => $tTime,           // duration 耗时
                    'ip'           => $rip,             // 请求方的IP地址
                    'service'      => $this->config->path('app.appName', 'UniondrugService'), // 当前服务的名称，在app.conf里面配置
                    'sr'           => $rTime,           // ServerReceive, 收到请求的时间
                    'ss'           => $sTime,           // ServerSend，完成后发送的时间
                    'req'          => $req,             // 请求的路径
                ]);
            }
        } catch (\Exception $e) {
            $this->di->getLogger('trace')->error(sprintf("[TraceMiddleware] Send to trace server failed: %s", $e->getMessage()));
        }

        // 7. 返回
        return $response;
    }
}
