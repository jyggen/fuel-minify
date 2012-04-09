<?php

Autoloader::add_core_namespace('jyggen');

Autoloader::add_classes(array(
    'jyggen\\Minify' => __DIR__.'/classes/Minify.php',
    'CURLRequest'    => __DIR__.'/vendor/Curl.php',
	'CSSCompression' => __DIR__.'/vendor/css-compressor/CSSCompression.php',
));