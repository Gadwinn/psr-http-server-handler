<?php

declare(strict_types=1);

namespace G\Http\Server;

use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * One step of a middleware pipeline: runs the middleware at its position and hands the
 * following step to it as the "next" handler.
 *
 * Immutable, so the same step can be handled several times (retries, long-running workers).
 *
 * @internal Created by RequestHandler; not part of the public API.
 */
final class MiddlewareRunner implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middlewares
     * @param RequestHandlerInterface $fallback Handler called once every middleware has run.
     * @param int $position Index of the middleware this step runs.
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly RequestHandlerInterface $fallback,
        private readonly int $position = 0
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->middlewares[$this->position] ?? null;
        if ($middleware === null) {
            return $this->fallback->handle($request);
        }

        return $middleware->process(
            $request,
            new self($this->middlewares, $this->fallback, $this->position + 1)
        );
    }
}
