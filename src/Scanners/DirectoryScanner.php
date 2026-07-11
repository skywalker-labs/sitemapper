<?php

namespace SkywalkerLabs\Sitemap\Scanners;

use SkywalkerLabs\Sitemap\Sitemap;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DirectoryScanner
{
    /**
     * Scan a directory for files and add them to the sitemap.
     *
     * @param Sitemap $sitemap
     * @param string $directoryPath The root directory to scan.
     * @param string $baseUrl The base URL to prepend to files.
     * @param array<string> $extensions Allowed file extensions (e.g., ['html', 'php']).
     * @return void
     */
    public static function scan(Sitemap $sitemap, string $directoryPath, string $baseUrl, array $extensions = ['html']): void
    {
        $directoryPath = rtrim($directoryPath, '/\\');
        $baseUrl = rtrim($baseUrl, '/');

        if (!is_dir($directoryPath)) {
            throw new \InvalidArgumentException("Invalid directory path: {$directoryPath}");
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryPath));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensions, true)) {
                    $path = $file->getPathname();

                    // Normalize path and remove base directory
                    $relativePath = str_replace($directoryPath, '', $path);
                    $relativePath = str_replace('\\', '/', $relativePath);

                    // Remove index.html or index.php
                    if (preg_match('#/index\.(html|php)$#i', $relativePath)) {
                        $relativePath = preg_replace('#/index\.(html|php)$#i', '/', $relativePath);
                    }

                    $url = $baseUrl . $relativePath;
                    $lastMod = date('c', $file->getMTime());

                    $sitemap->add(loc: $url, lastmod: $lastMod);
                }
            }
        }
    }
}
