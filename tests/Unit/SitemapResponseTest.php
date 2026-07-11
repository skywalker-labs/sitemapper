<?php

use SkywalkerLabs\Sitemap\Responses\SitemapResponse;
use SkywalkerLabs\Sitemap\Sitemap;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

test('SitemapResponse creates valid PSR-7 response', function () {
    $sitemap = new Sitemap();
    $sitemap->add('/psr-page');

    $response = new class implements ResponseInterface {
        /** @var array<string, mixed> */
        public array $headers = [];
        public ?StreamInterface $body = null;
        public function withHeader(string $name, $value): ResponseInterface
        {
            $this->headers[$name] = $value;
            return $this;
        }
        public function withBody(StreamInterface $body): ResponseInterface
        {
            $this->body = $body;
            return $this;
        }
        public function getProtocolVersion(): string
        {
            return '1.1';
        }
        public function withProtocolVersion(string $version): ResponseInterface
        {
            return $this;
        }
        public function getHeaders(): array
        {
            return $this->headers;
        }
        public function hasHeader(string $name): bool
        {
            return isset($this->headers[$name]);
        }
        public function getHeader(string $name): array
        {
            return [$this->headers[$name]];
        }
        public function getHeaderLine(string $name): string
        {
            return (string) ($this->headers[$name] ?? '');
        }
        public function withAddedHeader(string $name, $value): ResponseInterface
        {
            return $this;
        }
        public function withoutHeader(string $name): ResponseInterface
        {
            return $this;
        }
        public function getBody(): StreamInterface
        {
            return $this->body;
        }
        public function getStatusCode(): int
        {
            return 200;
        }
        public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
        {
            return $this;
        }
        public function getReasonPhrase(): string
        {
            return 'OK';
        }
    };

    $responseFactory = new class ($response) implements ResponseFactoryInterface {
        public function __construct(private ResponseInterface $res)
        {
        }
        public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
        {
            return $this->res;
        }
    };

    $streamFactory = new class implements StreamFactoryInterface {
        public function createStream(string $content = ''): StreamInterface
        {
            return new class ($content) implements StreamInterface {
                public function __construct(private string $content)
                {
                }
                public function __toString(): string
                {
                    return $this->content;
                }
                public function close(): void
                {
                }
                public function detach()
                {
                    return null;
                }
                public function getSize(): ?int
                {
                    return null;
                }
                public function tell(): int
                {
                    return 0;
                }
                public function eof(): bool
                {
                    return true;
                }
                public function isSeekable(): bool
                {
                    return false;
                }
                public function seek(int $offset, int $whence = SEEK_SET): void
                {
                }
                public function rewind(): void
                {
                }
                public function isWritable(): bool
                {
                    return false;
                }
                public function write(string $string): int
                {
                    return 0;
                }
                public function isReadable(): bool
                {
                    return true;
                }
                public function read(int $length): string
                {
                    return $this->content;
                }
                public function getContents(): string
                {
                    return $this->content;
                }
                public function getMetadata(?string $key = null)
                {
                    return null;
                }
            };
        }
        public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
        {
            throw new Exception();
        }
        public function createStreamFromResource($resource): StreamInterface
        {
            throw new Exception();
        }
    };

    $res = SitemapResponse::create($sitemap, $responseFactory, $streamFactory, 'xml');

    expect($res->hasHeader('Content-Type'))->toBeTrue();
    expect($res->getHeaderLine('Content-Type'))->toBe('application/xml');

    $bodyContent = (string) $res->getBody();
    expect($bodyContent)->toContain('<loc>/psr-page</loc>');
});
