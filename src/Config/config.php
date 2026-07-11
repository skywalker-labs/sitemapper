<?php

/* Simple configuration file for skywalker-labs sitemapper package */
return [
    'use_cache' => false,
    'cache_key' => 'sitemapper.' . (function_exists('config') ? config('app.url') : ''),
    'cache_duration' => 3600,
    'escaping' => true,
    'use_limit_size' => false,
    'max_size' => null,
    'use_styles' => true,
    'styles_location' => '/vendor/sitemapper/styles/',
    'use_gzip' => false
];
