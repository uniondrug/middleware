<?php
/**
 * MiddleServiceProvider.php
 *
 */

namespace Uniondrug\Middleware;

use Phalcon\Di\ServiceProviderInterface;

class MiddlewareServiceProvider implements ServiceProviderInterface
{
    public function register(\Phalcon\DiInterface $di)
    {
        $di->setShared(
            'middlewareManager',
            function () {
                $middlewares = $this->getConfig()->path('middlewares', []);

                return new MiddlewareManager($middlewares);
            }
        );

        // redefine Dispatcher
        $di->setShared(
            'dispatcher',
            function () {
                return new Dispatcher();
            }
        );
    }
}
