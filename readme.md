# Minify! (The FuelPHP Package) #

## Description ##
This repository contains a version of Minify updated to work as a FuelPHP package. For more information about Minify, [check out the official repository](https://github.com/jyggen/Minify).

## Installation ##
1. Clone (`git clone git://github.com/jyggen/Minify-FuelPHP`) or [Download](https://github.com/jyggen/Minify-FuelPHP/zipball/master) the package.
2. Minify should be located in fuel/packages/.
3. Copy fuel/packages/minify/config/minify.php to fuel/app/config/minify.php and make your desired changes (if any).
4. Add 'minify' to 'always_load/packages' in the config (or use `Fuel::add_package('minify')` in your code).
5. Success!

## Usage ##

	Minify::add('assets/css/normalize.css');
	Minify::add('assets/css/base.css');
	Minify::add('https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.js');
	Minify::add('assets/js/jquery.autocomplete.js');
	Minify::add('assets/js/general.js');
	Minify::run();
	
	Minify::printLinks();
	
	// You should in most cases use getLinks and assign it to your view though.
	// $view->set('css_files', Minify::getLinks('css'), false);
	// $view->set('js_files', Minify::getLinks('js'), false);

The above code will result in output similar to this:

	<link rel="stylesheet" media="screen" href="/assets/compressed.css?beecb4f2">
	<script src="/assets/compressed.js?ada7b8bb"></script>