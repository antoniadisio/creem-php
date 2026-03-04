<?php

declare(strict_types=1);

namespace Creem\Tests\Unit;

use Creem\Config;
use Creem\Environment;
use Creem\Exception\AuthenticationException;
use Creem\Exception\CreemException;
use Creem\Exception\NotFoundException;
use Creem\Exception\RateLimitException;
use Creem\Exception\ServerException;
use Creem\Exception\TransportException;
use Creem\Exception\ValidationException;
use Creem\Internal\Http\CreemConnector;
use Creem\Internal\Http\ResponseDecoder;
use RuntimeException;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

test('connector builds expected headers and request configuration', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123', Environment::Test, null, 12.5, 'integration-suite'));
    $pendingRequest = $connector->createPendingRequest(creemConnectorTestRequest());
    $psrRequest = $pendingRequest->createPsrRequest();

    expect((string) $psrRequest->getUri())->toBe('https://test-api.creem.io/v1/ping')
        ->and($psrRequest->getHeaderLine('Accept'))->toBe('application/json')
        ->and($psrRequest->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($psrRequest->getHeaderLine('x-api-key'))->toBe('sk_test_123')
        ->and($psrRequest->getHeaderLine('User-Agent'))->toStartWith('creem-php-sdk/')
        ->and($psrRequest->getHeaderLine('User-Agent'))->toContain('integration-suite')
        ->and($pendingRequest->config()->all()['timeout'])->toBe(12.5);
});

foreach (blankResponseBodies() as $dataset => [$body]) {
    test("response decoder returns empty payloads for blank bodies ({$dataset})", function () use ($body): void {
        $response = creemConnectorSuccessResponse(MockResponse::make($body, 200, ['Content-Type' => 'application/json']));

        expect(ResponseDecoder::decode($response))->toBe([]);
    });
}

foreach (nonObjectJsonPayloads() as $dataset => [$body]) {
    test("response decoder rejects non object json payloads ({$dataset})", function () use ($body): void {
        $response = creemConnectorSuccessResponse(MockResponse::make($body, 200, ['Content-Type' => 'application/json']));

        expect(static fn (): array => ResponseDecoder::decode($response))
            ->toThrow(TransportException::class, 'The Creem API returned an unexpected JSON payload shape.');
    });
}

test('invalid json is normalized to a transport exception', function (): void {
    $response = creemConnectorSuccessResponse(
        MockResponse::make('{"broken"', 200, ['Content-Type' => 'application/json']),
    );

    expect(static fn (): array => ResponseDecoder::decode($response))
        ->toThrow(TransportException::class, 'The Creem API returned an invalid JSON response.');
});

test('transport failures are wrapped and preserve the previous exception', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $mockResponse = MockResponse::make()->throw(
        static fn (PendingRequest $pendingRequest): FatalRequestException => new FatalRequestException(
            new RuntimeException('Socket closed'),
            $pendingRequest,
        ),
    );

    $exception = captureCreemException(
        static fn (): Response => $connector->send(creemConnectorTestRequest(), new MockClient([$mockResponse])),
    );

    expect($exception)->toBeInstanceOf(TransportException::class)
        ->and($exception?->getMessage())->toBe('The Creem API request could not be completed.')
        ->and($exception?->statusCode())->toBeNull()
        ->and($exception?->context())->toBe([])
        ->and($exception?->getPrevious())->toBeInstanceOf(FatalRequestException::class)
        ->and($exception?->getPrevious()?->getPrevious())->toBeInstanceOf(RuntimeException::class)
        ->and($exception?->getPrevious()?->getPrevious()?->getMessage())->toBe('Socket closed');
});

foreach (creemConnectorResponseFailureMappings() as $dataset => [$response, $expectedException, $expectedMessage, $expectedStatus, $expectedContext]) {
    test("http failures are mapped to typed exceptions with preserved context ({$dataset})", function () use (
        $response,
        $expectedException,
        $expectedMessage,
        $expectedStatus,
        $expectedContext,
    ): void {
        $connector = new CreemConnector(new Config('sk_test_123'));
        $exception = captureCreemException(
            static fn (): Response => $connector->send(creemConnectorTestRequest(), new MockClient([$response])),
        );

        expect($exception)->toBeInstanceOf(CreemException::class)
            ->and($exception)->toBeInstanceOf($expectedException)
            ->and($exception?->getMessage())->toBe($expectedMessage)
            ->and($exception?->statusCode())->toBe($expectedStatus)
            ->and($exception?->context())->toBe($expectedContext);

        if ($exception instanceof ValidationException && array_key_exists('errors', $expectedContext)) {
            expect($exception->errors())->toBe($expectedContext['errors']);
        }
    });
}

test('generic client errors map to the base exception type', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make(['detail' => 'Conflict'], 409),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(CreemException::class)
        ->and($exception)->not->toBeInstanceOf(AuthenticationException::class)
        ->and($exception)->not->toBeInstanceOf(NotFoundException::class)
        ->and($exception)->not->toBeInstanceOf(ValidationException::class)
        ->and($exception)->not->toBeInstanceOf(RateLimitException::class)
        ->and($exception)->not->toBeInstanceOf(ServerException::class)
        ->and($exception?->getMessage())->toBe('Conflict')
        ->and($exception?->statusCode())->toBe(409)
        ->and($exception?->context())->toBe(['detail' => 'Conflict']);
});

test('nested validation errors resolve useful messages', function (): void {
    $connector = new CreemConnector(new Config('sk_test_123'));
    $errors = [
        [
            'meta' => [
                'detail' => 'Nested error message',
            ],
        ],
    ];
    $exception = captureCreemException(
        static fn (): Response => $connector->send(
            creemConnectorTestRequest(),
            new MockClient([
                MockResponse::make(['errors' => $errors], 400),
            ]),
        ),
    );

    expect($exception)->toBeInstanceOf(ValidationException::class)
        ->and($exception?->getMessage())->toBe('Nested error message')
        ->and($exception?->statusCode())->toBe(400)
        ->and($exception?->context())->toBe(['errors' => $errors]);

    if ($exception instanceof ValidationException) {
        expect($exception->errors())->toBe($errors);
    }
});

function creemConnectorTestRequest(): Request
{
    return new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/v1/ping';
        }
    };
}

/**
 * @return array<string, array{0: string}>
 */
function blankResponseBodies(): array
{
    return [
        'empty string' => [''],
        'whitespace only' => [" \n\t "],
    ];
}

/**
 * @return array<string, array{0: string}>
 */
function nonObjectJsonPayloads(): array
{
    return [
        'json list' => ['[]'],
        'json scalar' => ['"ok"'],
    ];
}

/**
 * @return array<string, array{
 *     0: MockResponse,
 *     1: class-string<CreemException>,
 *     2: string,
 *     3: int,
 *     4: array<string, mixed>
 * }>
 */
function creemConnectorResponseFailureMappings(): array
{
    return [
        'unauthorized' => [
            MockResponse::make(['message' => 'Unauthorized'], 401),
            AuthenticationException::class,
            'Unauthorized',
            401,
            ['message' => 'Unauthorized'],
        ],
        'forbidden' => [
            MockResponse::make(['message' => 'Forbidden'], 403),
            AuthenticationException::class,
            'Forbidden',
            403,
            ['message' => 'Forbidden'],
        ],
        'not_found' => [
            MockResponse::make(['message' => 'Missing'], 404),
            NotFoundException::class,
            'Missing',
            404,
            ['message' => 'Missing'],
        ],
        'validation_status' => [
            MockResponse::make(['message' => 'Invalid'], 422),
            ValidationException::class,
            'Invalid',
            422,
            ['message' => 'Invalid'],
        ],
        'validation_errors' => [
            MockResponse::make(['errors' => ['name' => ['Name is required.']]], 400),
            ValidationException::class,
            'Name is required.',
            400,
            ['errors' => ['name' => ['Name is required.']]],
        ],
        'rate_limit' => [
            MockResponse::make(['message' => 'Slow down'], 429),
            RateLimitException::class,
            'Slow down',
            429,
            ['message' => 'Slow down'],
        ],
        'server_error' => [
            MockResponse::make('Internal server error', 500),
            ServerException::class,
            'Internal server error',
            500,
            ['body' => 'Internal server error'],
        ],
    ];
}

function creemConnectorSuccessResponse(MockResponse $mockResponse): Response
{
    $connector = new CreemConnector(new Config('sk_test_123'));

    return $connector->send(creemConnectorTestRequest(), new MockClient([$mockResponse]));
}

function captureCreemException(callable $callback): ?CreemException
{
    try {
        $callback();
    } catch (CreemException $exception) {
        return $exception;
    }

    return null;
}
