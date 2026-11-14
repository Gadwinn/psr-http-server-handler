<?php

declare(strict_types=1);

namespace G\Http\Server;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};

/**
 * PSR-15 handler returning an empty response with a fixed status code.
 *
 * Meant to sit at the end of a middleware pipeline, for requests no middleware answered.
 *
 * @version 1.0.0
 */
class FallbackResponseRequestHandler implements RequestHandlerInterface
{
    public const DEFAULT_STATUS_CODE = 404;

    /**
     * @param ResponseFactoryInterface $responseFactory Any PSR-17 response factory.
     * @param int $statusCode Status code of the returned response.
     */
    public function __construct(
        protected ResponseFactoryInterface $responseFactory,
        protected int $statusCode = self::DEFAULT_STATUS_CODE
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse($this->statusCode);
    }
}
