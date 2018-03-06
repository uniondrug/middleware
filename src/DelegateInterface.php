<?php
/**
 * DelegateInterface.php
 *
 */

namespace Uniondrug\Middleware;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;

interface DelegateInterface
{
    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function next(RequestInterface $request);

    /**
     * Dispatch the next available middleware and return the response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function process(RequestInterface $request);

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request);
}
