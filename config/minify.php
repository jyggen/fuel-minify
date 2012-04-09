<?php
return array(
	'algorithm'     => 'crc32b',
	'cacheFile'     => 'minify.sfv',
	'cacheDir'      => APPPATH.'cache'.DS.'minify'.DS,
	'outputDir'     => 'assets/',
	'publicDir'     => null,
	'absolutePaths' => true,
	'allowedExts'   => array('js', 'css'),
	'minifyFile'    => 'compressed',
	'useLocalJS'    => false,
	'htmlCSS'       => '<link rel="stylesheet" media="screen" href="%s">',
	'htmlJS'        => '<script src="%s"></script>',
	'compressCode'  => true,
	'cssLevel'      => 'sane',
	'useRewrite'    => false
);