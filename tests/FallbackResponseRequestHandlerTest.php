<?php

declare(strict_types=1);

namespace G\Http\Server\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use G\Http\Server\FallbackResponseRequestHandler;

final class FallbackResponseRequestHandlerTest extends TestCase
{
    public function testFallbackResponseIsNotFoundByDefault(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->expects($this->once())->method('createResponse')->with(404)->willReturn($response);

        $handler = new FallbackResponseRequestHandler($factory);

        $this->assertSame($response, $handler->handle($this->createStub(ServerRequestInterface::class)));
    }

    public function testFallbackResponseWithCustomStatusCode(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->expects($this->once())->method('createResponse')->with(204)->willReturn($response);

        $handler = new FallbackResponseRequestHandler($factory, 204);

        $this->assertSame($response, $handler->handle($this->createStub(ServerRequestInterface::class)));
    }
}
