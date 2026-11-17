# PSR-15 HTTP Server Handler

A PHP 8.3+ implementation of the [PSR-15](https://www.php-fig.org/psr/psr-15/) `RequestHandlerInterface`: a sequential middleware pipeline with a fallback handler.

It only depends on PSR interfaces. Bring any PSR-7 / PSR-17 implementation (for instance `gadwinn/psr-http-message`).

## Installation

```bash
composer require gadwinn/psr-http-server-handler
```

## Usage

### Middleware pipeline

```php
use G\Http\Server\{FallbackResponseRequestHandler, RequestHandler};

// $responseFactory: any Psr\Http\Message\ResponseFactoryInterface
$handler = new RequestHandler(new FallbackResponseRequestHandler($responseFactory));

$handler->addMiddlewares(
    new AuthMiddleware(),
    new LoggingMiddleware(),
    new RouteMiddleware(),
);

$response = $handler->handle($serverRequest);
```

Middlewares run in the order they were added. Handling a request does not consume the
pipeline: the same `RequestHandler` can handle any number of requests, which makes it safe
in long-running workers (FrankenPHP worker mode, RoadRunner, ...).

### Fallback handler

The fallback is the handler called when no middleware returned a response. It is mandatory
and can be any `RequestHandlerInterface`, typically your router or controller dispatcher.

`FallbackResponseRequestHandler` is a ready-made one returning an empty response, `404` by
default:

```php
new FallbackResponseRequestHandler($responseFactory);      // 404
new FallbackResponseRequestHandler($responseFactory, 204); // any other status code
```

### Implementing a middleware

```php
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

final class MyMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // On the way in: called before the next middleware, usually to modify the request
        $response = $handler->handle($request);
        // On the way back: called after the next middleware, usually to modify the response
        return $response;
    }
}
```

### Removing a middleware

```php
$handler->addMiddlewares($middlewareA, $middlewareB);
$handler->removeMiddleware($middlewareA); // true on success, false if not found
```

## Development

```bash
composer install
composer test-all   # security audit, code style, static analysis, unit tests, magic numbers
```

## License

MIT, see [LICENSE](LICENSE).
