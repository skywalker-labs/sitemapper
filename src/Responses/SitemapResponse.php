<?php

namespace SkywalkerLabs\Sitemap\Responses;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SkywalkerLabs\Sitemap\Sitemap;

class SitemapResponse
{
    /**
     * Create a PSR-7 Response from the Sitemap.
     *
     * @param Sitemap $sitemap
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     * @param string $format
     * @param string|null $style
     * @return ResponseInterface
     */
    public static function create(
        Sitemap $sitemap,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        string $format = 'xml',
        ?string $style = null
    ): ResponseInterface {
        $content = $sitemap->render($format, $style);

        $isGzip = $sitemap->getConfig()?->isGzipEnabled() ?? false;
        if ($isGzip && $format === 'xml') {
            $compressed = gzencode($content, 9);
            if ($compressed !== false) {
                $content = $compressed;
                $format = 'xml.gz';
            }
        }

        $stream = $streamFactory->createStream($content);
        $response = $responseFactory->createResponse(200)
                                    ->withBody($stream);

        $contentType = match ($format) {
            'xml' => 'application/xml',
            'xml.gz' => 'application/x-gzip',
            'html' => 'text/html',
            'txt' => 'text/plain',
            default => 'application/xml',
        };

        return $response->withHeader('Content-Type', $contentType);
    }
}
