<?php

declare(strict_types=1);

namespace G\Http\Server;

use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

/**
 * PSR-15 middleware pipeline.
 *
 * Middlewares run in the order they were added; the fallback handler answers when none of
 * them returned a response. Handling a request does not alter the pipeline, so one instance
 * can serve any number of requests (long-running workers included).
 *
 * @version 1.0.0
 */
class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var list<MiddlewareInterface>
     */
    protected array $middlewares = [];

    /**
     * @param RequestHandlerInterface $fallback Handler called once every middleware has run.
     */
    public function __construct(
        protected RequestHandlerInterface $fallback
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new MiddlewareRunner($this->middlewares, $this->fallback))->handle($request);
    }

    /**
     * @return list<MiddlewareInterface>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return RequestHandler
     */
    public function addMiddlewares(MiddlewareInterface ...$middleware): self
    {
        foreach ($middleware as $m) {
            $this->middlewares[] = $m;
        }
        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return bool
     */
    public function removeMiddleware(MiddlewareInterface $middleware): bool
    {
        $position = array_search($middleware, $this->middlewares, true);
        if ($position === false) {
            return false;
        }

        array_splice($this->middlewares, $position, 1);
        return true;
    }
}
