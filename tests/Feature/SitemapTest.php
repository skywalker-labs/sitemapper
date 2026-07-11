<?php

use SkywalkerLabs\Sitemap\Sitemap;
use SkywalkerLabs\Sitemap\Config\SitemapConfig;
use Psr\SimpleCache\CacheInterface;

beforeEach(function () {
    $this->tempDir = __DIR__ . '/../temp';
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
});

test('sitemap chunking works correctly', function () {
    $config = new SitemapConfig(chunkLimit: 2);
    $sitemap = new Sitemap($config);

    $sitemap->add('/page1')
            ->add('/page2')
            ->add('/page3')
            ->add('/page4')
            ->add('/page5');

    $sitemap->store('xml', 'test-sitemap', $this->tempDir);

    expect(file_exists($this->tempDir . '/test-sitemap-1.xml'))->toBeTrue();
    expect(file_exists($this->tempDir . '/test-sitemap-2.xml'))->toBeTrue();
    expect(file_exists($this->tempDir . '/test-sitemap-3.xml'))->toBeTrue();
    expect(file_exists($this->tempDir . '/test-sitemap-index.xml'))->toBeTrue();

    $indexContent = file_get_contents($this->tempDir . '/test-sitemap-index.xml');
    expect($indexContent)->toContain('test-sitemap-1.xml');
    expect($indexContent)->toContain('test-sitemap-2.xml');
    expect($indexContent)->toContain('test-sitemap-3.xml');
});

test('sitemap gzip compression works', function () {
    $config = new SitemapConfig(useGzip: true);
    $sitemap = new Sitemap($config);
    $sitemap->add('/gzip-page');

    $sitemap->store('xml', 'gzip-test', $this->tempDir);

    $filePath = $this->tempDir . '/gzip-test.xml.gz';
    expect(file_exists($filePath))->toBeTrue();

    // Verify it is a valid gzip file
    $content = file_get_contents($filePath);
    $uncompressed = gzdecode($content);
    expect($uncompressed)->toContain('/gzip-page');
});

test('caching mechanism works', function () {
    $config = new SitemapConfig(useCache: true);
    $sitemap = new Sitemap($config);
    $sitemap->add('/cached-page');

    $cache = new class implements CacheInterface {
        public $data = [];
        public function get(string $key, mixed $default = null): mixed
        {
            return $this->data[$key] ?? $default;
        }
        public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
        {
            $this->data[$key] = $value;
            return true;
        }
        public function delete(string $key): bool
        {
            unset($this->data[$key]);
            return true;
        }
        public function clear(): bool
        {
            $this->data = [];
            return true;
        }
        public function getMultiple(iterable $keys, mixed $default = null): iterable
        {
            return [];
        }
        public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
        {
            return true;
        }
        public function deleteMultiple(iterable $keys): bool
        {
            return true;
        }
        public function has(string $key): bool
        {
            return isset($this->data[$key]);
        }
    };

    $sitemap->setCache($cache);

    $output1 = $sitemap->render('xml');

    // Add another item, but output should remain same due to cache
    $sitemap->add('/new-page');
    $output2 = $sitemap->render('xml');

    expect($output1)->toEqual($output2);
    expect($output2)->not->toContain('/new-page');
});

test('ping gracefully handles failures', function () {
    $sitemap = new Sitemap();
    $result = $sitemap->ping('http://invalid-sitemap.url');
    expect($result)->toBeFalse();
});
