<?php

declare(strict_types=1);

namespace G\Http\Server\Tests;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use G\Http\Server\RequestHandler;

final class RequestHandlerTest extends TestCase
{
    /**
     * @var ArrayObject<int,string>
     */
    private ArrayObject $log;
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    protected function setUp(): void
    {
        $this->log = new ArrayObject();
        $this->request = $this->createStub(ServerRequestInterface::class);
        $this->response = $this->createStub(ResponseInterface::class);
    }

    private function createHandler(): RequestHandler
    {
        $log = $this->log;
        $response = $this->response;

        return new RequestHandler(new class ($log, $response) implements RequestHandlerInterface {
            /**
             * @param ArrayObject<int,string> $log
             */
            public function __construct(private ArrayObject $log, private ResponseInterface $response)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->log->append('fallback');
                return $this->response;
            }
        });
    }

    public function testHandleWithNoMiddlewares(): void
    {
        $handler = $this->createHandler();

        $this->assertSame($this->response, $handler->handle($this->request));
        $this->assertSame(['fallback'], $this->log->getArrayCopy());
    }

    public function testHandleRunsMiddlewaresInOrder(): void
    {
        $handler = $this->createHandler()->addMiddlewares(
            new TestMiddleware('a', $this->log),
            new TestMiddleware('b', $this->log),
            new TestMiddleware('c', $this->log)
        );

        $this->assertSame($this->response, $handler->handle($this->request));
        $this->assertSame(
            ['a:in', 'b:in', 'c:in', 'fallback', 'c:out', 'b:out', 'a:out'],
            $this->log->getArrayCopy()
        );
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $shortCircuit = $this->createStub(MiddlewareInterface::class);
        $shortCircuit->method('process')->willReturn($this->response);

        $handler = $this->createHandler()->addMiddlewares(
            new TestMiddleware('a', $this->log),
            $shortCircuit,
            new TestMiddleware('never', $this->log)
        );

        $this->assertSame($this->response, $handler->handle($this->request));
        $this->assertSame(['a:in', 'a:out'], $this->log->getArrayCopy());
    }

    public function testHandleCanBeCalledSeveralTimes(): void
    {
        $handler = $this->createHandler()->addMiddlewares(
            new TestMiddleware('a', $this->log),
            new TestMiddleware('b', $this->log)
        );

        $handler->handle($this->request);
        $handler->handle($this->request);

        $expected = ['a:in', 'b:in', 'fallback', 'b:out', 'a:out'];
        $this->assertSame([...$expected, ...$expected], $this->log->getArrayCopy());
        $this->assertCount(2, $handler->getMiddlewares());
    }

    public function testMiddlewareCanCallNextHandlerSeveralTimes(): void
    {
        $handler = $this->createHandler()->addMiddlewares(
            new TestMiddleware('retry', $this->log, 2),
            new TestMiddleware('b', $this->log)
        );

        $handler->handle($this->request);

        $this->assertSame(
            ['retry:in', 'b:in', 'fallback', 'b:out', 'b:in', 'fallback', 'b:out', 'retry:out'],
            $this->log->getArrayCopy()
        );
    }

    public function testAddMiddlewares(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = $this->createStub(MiddlewareInterface::class);
        $handler = $this->createHandler();

        $this->assertSame($handler, $handler->addMiddlewares($middleware1, $middleware2));

        $this->assertSame([$middleware1, $middleware2], $handler->getMiddlewares());
    }

    public function testRemoveMiddleware(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = $this->createStub(MiddlewareInterface::class);
        $handler = $this->createHandler()->addMiddlewares($middleware1, $middleware2);

        $this->assertTrue($handler->removeMiddleware($middleware1));
        $this->assertSame([$middleware2], $handler->getMiddlewares());
    }

    public function testRemoveMiddlewareWithUnknownMiddleware(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = $this->createStub(MiddlewareInterface::class);
        $handler = $this->createHandler()->addMiddlewares($middleware1, $middleware2);

        $this->assertFalse($handler->removeMiddleware($this->createStub(MiddlewareInterface::class)));
        $this->assertSame([$middleware1, $middleware2], $handler->getMiddlewares());
    }
}
