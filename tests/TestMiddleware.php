<?php

declare(strict_types=1);

namespace G\Http\Server\Tests;

use ArrayObject;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

/**
 * Records when it is entered and left, and optionally calls the next handler several times.
 */
final class TestMiddleware implements MiddlewareInterface
{
    /**
     * @param ArrayObject<int,string> $log
     */
    public function __construct(
        private readonly string $name,
        private readonly ArrayObject $log,
        private readonly int $calls = 1
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $this->log->append($this->name . ':in');
        for ($i = 0; $i < $this->calls; $i++) {
            $response = $handler->handle($request);
        }
        $this->log->append($this->name . ':out');

        return $response;
    }
}
