<?php
/**
 * MiddlewareInterface.php
 *
 */

namespace UniondrugMiddleware;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * @param \Phalcon\Http\RequestInterface $request
     * @param DelegateInterface              $next
     *
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, DelegateInterface $next);

    /**
     * @param \Phalcon\Http\RequestInterface $request
     * @param DelegateInterface              $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, DelegateInterface $next);
}
