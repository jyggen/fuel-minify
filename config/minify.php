<?php
/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade Minify! without losing your custom config.
 */

return array(

	// algorithm to use for file checksums
	'algorithm' => 'crc32b',

	// name of the file to store checksums in
	'cacheFile' => 'minify.sfv',

	// folder to cache downloaded and minified code
	'cacheDir' => APPPATH.'cache'.DS.'minify'.DS,

	// folder to save compressed files
	'outputDir' => 'assets/',

	// folder to use within html tags, if differs from outputDir
	'publicDir' => null,

	// if html tags should use absolute paths or not
	'absolutePaths' => true,

	// file extensions allowed by Minify!
	'allowedExts' => array('js', 'css'),

	// basename of the file containing the compressed code
	'minifyFile' => 'compressed',

	// use local version of closure compiler (not yet implemented)
	'useLocalJS' => false,

	// template for css code
	'htmlCSS' => '<link rel="stylesheet" href="%s">',

	// template for javascript code
	'htmlJS' => '<script src="%s"></script>',

	// if the code should be compressed
	'compressCode' => true,

	// which level of css compression to use
	// safe (99% safe):
	// 	Safe mode does zero combinations or organizing. It's the best mode if you use a lot of hacks.
	// sane (90% safe):
	// 	Sane mode does most combinations(multiple long hand notations to single shorthand), but still keeps most declarations in their place.
	// small (65% safe):
	// 	Small mode reorganizes the whole sheet, combines as much as it can, and will break most comment hacks. 
	// full (64% safe):
	//	Full mode does everything small does, but also converts hex codes to their short color name alternatives.
	'cssLevel' => 'sane',

	// if rewrite-based cache busting should be used or not, true is recommended for optimal caching.
	// true : compressed.20120409.css
	// false: compressed.css?1333946018
	//
	// requires the following (or similar) rules in your .htacces:
	// <IfModule mod_rewrite.c>
	//   RewriteCond %{REQUEST_FILENAME} !-f
	//   RewriteCond %{REQUEST_FILENAME} !-d
	//   RewriteRule ^(.+)\.(\d+)\.(js|css)$ $1.$3 [L]
	// </IfModule>
	'useRewrite' => false

);
