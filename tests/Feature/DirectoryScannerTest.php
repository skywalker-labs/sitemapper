<?php

use SkywalkerLabs\Sitemap\Sitemap;
use SkywalkerLabs\Sitemap\Scanners\DirectoryScanner;

beforeEach(function () {
    $this->tempDir = __DIR__ . '/../temp_scan';
    if (!is_dir($this->tempDir)) {
        mkdir($this->tempDir, 0777, true);
    }
});

afterEach(function () {
    $files = glob($this->tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($this->tempDir);
});

test('directory scanner finds files', function () {
    $sitemap = new Sitemap();

    file_put_contents($this->tempDir . '/about.html', 'about');
    file_put_contents($this->tempDir . '/contact.html', 'contact');
    file_put_contents($this->tempDir . '/style.css', 'css');

    DirectoryScanner::scan($sitemap, $this->tempDir, 'https://example.com');

    $items = $sitemap->getModel()->getItems();
    $urls = array_column($items, 'loc');

    expect($urls)->toContain('https://example.com/about.html');
    expect($urls)->toContain('https://example.com/contact.html');
    expect($urls)->not->toContain('https://example.com/style.css'); // CSS not in default extensions
});
