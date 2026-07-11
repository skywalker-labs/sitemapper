<?php

use SkywalkerLabs\Sitemap\Sitemap;
use SkywalkerLabs\Sitemap\Scanners\DirectoryScanner;

beforeEach(function () {
    $tempDir = __DIR__ . '/../temp_scan';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
});

afterEach(function () {
    $tempDir = __DIR__ . '/../temp_scan';
    $files = glob($tempDir . '/*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    if (is_dir($tempDir)) {
        rmdir($tempDir);
    }
});

test('directory scanner finds files', function () {
    $sitemap = new Sitemap();
    $tempDir = __DIR__ . '/../temp_scan';
    
    file_put_contents($tempDir . '/about.html', 'about');
    file_put_contents($tempDir . '/contact.html', 'contact');
    file_put_contents($tempDir . '/style.css', 'css');

    DirectoryScanner::scan($sitemap, $tempDir, 'https://example.com');

    $items = $sitemap->getModel()->getItems();
    $urls = array_column($items, 'loc');

    expect($urls)->toContain('https://example.com/about.html');
    expect($urls)->toContain('https://example.com/contact.html');
    expect($urls)->not->toContain('https://example.com/style.css'); // CSS not in default extensions
});
