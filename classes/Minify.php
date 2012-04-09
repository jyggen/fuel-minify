<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Minify
 * @author     Jonas Stendahl
 * @license    MIT License
 * @copyright  2012 Jonas Stendahl
 * @link       http://www.jyggen.com
 *
 * CSS Compressor by Corey Hart
 * http://www.codenothing.com
 *
 * Closure Compiler by Google
 * http://closure-compiler.appspot.com
 */

namespace jyggen;

class MinifyException extends \FuelException {}

class Minify
{

	static protected $_opt           = array();
	static protected $_files         = array();
	static protected $_downloadQueue = array();
	static protected $_debugLog      = array();
	static protected $_cacheDir;
	static protected $_cssMode;
	static protected $_jsMode;
	static protected $_mincode;
	static protected $_outputDir;
	static protected $_publicDir;
	static protected $_benchmark;
	static protected $_memory;
	
	public static function _init()
	{

		\Config::load('minify', true);

	}

	/**
	 * Set an options value.
	 *
	 * @param	string	options key
	 * @param	string	options value
	 * @return	void
	 */
	static public function set($key, $value)
	{

		self::$_opt[$key] = $value;

	}
	
	/**
	 * Add file(s) to be minified.
	 *
	 * @param	mixed	URL or file path to file(s) to minified
	 * @return	void
	 */
	static public function add($files)
	{

		if (is_array($files) === true) {

			foreach ($files as $file) {

				self::add($file);

			}

		} else {

			self::$_files[]['path'] = $files;

		}

	}

	/**
	 * Run Minify!
	 *
	 * @return	void
	 */
	static public function run()
	{

		\Profiler::mark('Minify! started');

		self::loadDefaultOpts();
		self::validateOutputDir();
		self::validateCacheDir();
		self::validatePublicDir();
		self::validateFiles();

		if (empty(self::$_downloadQueue) === false) {

			self::downloadFiles();

		}

		self::detectMode();

		if (self::evaluate() === false) {

			self::compressFiles();
			self::saveFiles();
			self::saveCacheFile();

		}

		\Profiler::mark('Minify! finished');
		\Profiler::mark_memory(new self, 'Minify memory usage');

	}

	/**
	 * Generate HTML tag(s) to include the minified file(s).
	 *
	 * @return	string
	 */
	static public function getLinks($which='both')
	{

		$links = '';

		if (($which == 'both' || $which == 'js') && self::$_jsMode === true) {

			if (self::$_opt['publicDir'] !== null) {

				$file = self::$_opt['publicDir'].self::$_opt['minifyFile'].'.js';

			} else {

				$file = self::$_outputDir.self::$_opt['minifyFile'].'.js';

			}

			if(self::$_opt['useRewrite']) {

				$ident = date('Ymd', filemtime($file));

			} else {

				$ident = hash_file(self::$_opt['algorithm'], $file);

			}

			if (self::$_opt['absolutePaths'] === true && substr($file, 0, 1) !== '/') {

				$file = '/'.$file;

			}

			if(self::$_opt['useRewrite']) {
				
				$ext  = pathinfo($file, PATHINFO_EXTENSION);
				$file = substr($file, 0, -strlen($ext));
				$file = $file.$ident.'.'.$ext;
			
			} else {
			
				$file = $file.'?'.$ident;
			
			}

			$links .= sprintf(self::$_opt['htmlJS'], $file)."\n";

		}

		if (($which == 'both' || $which == 'css') && self::$_cssMode === true) {

			if (self::$_opt['publicDir'] !== null) {

				$file = self::$_opt['publicDir'].self::$_opt['minifyFile'].'.css';

			} else {

				$file = self::$_outputDir.self::$_opt['minifyFile'].'.css';

			}

			if(self::$_opt['useRewrite']) {

				$ident = date('Ymd', filemtime($file));

			} else {

				$ident = hash_file(self::$_opt['algorithm'], $file);

			}

			if (self::$_opt['absolutePaths'] === true && substr($file, 0, 1) !== '/') {

				$file = '/'.$file;

			}
			
			if(self::$_opt['useRewrite']) {
				
				$ext  = pathinfo($file, PATHINFO_EXTENSION);
				$file = substr($file, 0, -strlen($ext));
				$file = $file.$ident.'.'.$ext;
			
			} else {
			
				$file = $file.'?'.$ident;
			
			}

			$links .= sprintf(self::$_opt['htmlCSS'], $file)."\n";

		}

		return $links;

	}
	
	/**
	 * Output HTML tag(s) to include the minified file(s).
	 *
	 * @return	void
	 */
	static public function printLinks()
	{

		echo self::getLinks();

	}
	
	/**
	 * Validate that a directory exists and is writable. Will try to
	 * create it otherwise.
	 *
	 * @param	string	path to directory
	 * @return boolean
	 */
	static protected function validateDir($dir)
	{

		if (is_dir($dir) === false && mkdir($dir, 0777, true) === false) {

			$msg = '"%s" is not a valid directory.';
			$msg = sprintf($msg, $dir);

			\Log::error($msg, 'Minify::validateDir()');
			throw new MinifyException($msg);

		}

		if (is_writable($dir) === false) {

			$msg = '"%s" is not writable.';
			$msg = sprintf($msg, $dir);

			\Log::error($msg, 'Minify::validateDir()');
			throw new MinifyException($msg);

		}

		return true;

	}
	
	/**
	 * Validate that an options key is properly set.
	 *
	 * @param	string	options key to validate
	 * @return	boolean
	 */
	static protected function validateOpt($key)
	{

		if (isset(self::$_opt[$key]) === false
			|| empty(self::$_opt[$key]) === true
		) {

			$msg = 'Missing "%s" in configuration.';
			$msg = sprintf($msg, $key);

			\Log::error($msg, 'Minify::validateOpt()');
			throw new MinifyException($msg);

		} else {

			return true;

		}

	}
	
	/**
	 * Return the extension of a filename.
	 *
	 * @param	string	filename
	 * @return	string
	 */
	static protected function getExt($name)
	{

		$info = pathinfo($name);

		if(array_key_exists('extension', $info)) {

			return $info['extension'];

		} else {

			$msg = 'Couldn\'t get extension of file %s.';
			$msg = sprintf($msg, $name);

			\Log::error($msg, 'Minify::getExt()');
			return null;

		}

	}

	/**
	 * Check if the filename's extension is allowed.
	 *
	 * @param	string	filename
	 * @return	boolean
	 */
	static protected function isAllowedExt($name)
	{

		$ext = self::getExt($name);
		$ok  = (in_array($ext, self::$_opt['allowedExts']));

		return $ok;

	}

	/**
	 * Merge the default options with any user changes.
	 *
	 * @return	void
	 */
	static protected function loadDefaultOpts()
	{

		$defaultOpts = array(
						'algorithm'     => \Config::get('minify.algorithm', 'crc32b'),
						'cacheFile'     => \Config::get('minify.cacheFile', 'minify.sfv'),
						'cacheDir'      => \Config::get('minify.cacheDir', __DIR__.'/minify/cache/'),
						'outputDir'     => \Config::get('minify.outputDir', 'assets/'),
						'publicDir'     => \Config::get('minify.publicDir', null),
						'absolutePaths' => \Config::get('minify.absolutePaths', true),
						'allowedExts'   => \Config::get('minify.allowedExts', array('js', 'css')),
						'minifyFile'    => \Config::get('minify.minifyFile', 'compressed'),
						'useLocalJS'    => \Config::get('minify.useLocalJS', false),
						'htmlCSS'       => \Config::get('minify.htmlCSS', '<link rel="stylesheet" media="screen" href="%s">'),
						'htmlJS'        => \Config::get('minify.htmlJS', '<script src="%s"></script>'),
						'compressCode'  => \Config::get('minify.compressCode', true),
						'cssLevel'      => \Config::get('minify.cssLevel', 'sane'),
						'useRewrite'    => \Config::get('minify.useRewrite', false)
					   );

		self::$_opt = (self::$_opt + $defaultOpts);

	}

	/**
	 * Validate that the output directory exists and is writable.
	 *
	 * @return	boolean
	 */
	static protected function validateOutputDir()
	{

		self::validateOpt('outputDir');

		self::$_outputDir = self::$_opt['outputDir'];
		$isValid          = self::validateDir(self::$_outputDir);

		return $isValid;

	}

	/**
	 * Validate that the public directory exists and is writable. Also
	 * adds / to non-absolute paths if absolutePaths is set to true.
	 *
	 * @return	void
	 */
	static protected function validatePublicDir()
	{
		
		if (self::$_opt['publicDir'] === null) {
		
			self::$_publicDir = self::$_outputDir;
		
		} else {
			
			self::$_publicDir = self::$_opt['publicDir'];

		}

		$char = substr(self::$_publicDir, 0, 1);

		if (self::$_opt['absolutePaths'] === true && $char !== '/') {

			self::$_publicDir = '/'.self::$_publicDir;

		}


	}

	/**
	 * Validate that the cache directory exists and is writable.
	 *
	 * @return boolean
	 */
	static protected function validateCacheDir()
	{

		self::validateOpt('cacheDir');

		self::$_cacheDir = self::$_opt['cacheDir'];
		$isValid         = self::validateDir(self::$_cacheDir);

		return $isValid;

	}

	/**
	 * Validate that every file added to Minify is valid and any
	 * remote file to the download queue if the source isn't cached.
	 *
	 * @return void
	 */
	static protected function validateFiles()
	{

		foreach (self::$_files as $k => $file) {

			$key =& self::$_files[$k];

			if (self::isAllowedExt($file['path']) === false) {

				unset(self::$_files[$k]);

				$file = basename($file['path']);
				$msg  = 'Skipping %s due to invalid file.';
				$msg  = sprintf($msg, $file);

				\Log::debug($msg, 'Minify::validateFiles()');

			} else {

				$key['ext'] = self::getExt($file['path']);

				$regexp = '/((http|ftp|https):\/\/[\w\-_]+
						(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:
						\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?)/siU';

				$regexp = preg_replace('/\s+/', '', $regexp);

				if (preg_match($regexp, $file['path'], $match) !== 0) {

					$srcPath   = $file['path'];
					$cachePath = self::$_cacheDir.md5($file['path']);

					if (file_exists($cachePath) === true) {

						$key['data'] = file_get_contents($cachePath);
						$key['path'] = $cachePath;
						$key['hash'] = hash(self::$_opt['algorithm'], $key['data']);
						\Log::debug(basename($file['path']).' will use a cached copy', 'Minify::validateFiles()');

					} else {

						self::$_downloadQueue[$k] = $srcPath;
						\Log::debug(basename($file['path']).' will be downloaded', 'Minify::validateFiles()');

					}

				} else {

					if (file_exists($file['path']) === true) {

						$key['data'] = file_get_contents($file['path']);
						$key['hash'] = hash(self::$_opt['algorithm'], $key['data']);
						\Log::debug(basename($file['path']).' will use a local copy', 'Minify::validateFiles()');

					} else {

						unset($key);
						\Log::error(basename($file['path']).' is an invalid file', 'Minify::validateFiles()');

					}

				}//end if

			}//end if

		}//end foreach
		
	}

	static protected function downloadFiles()
	{

		foreach (self::$_downloadQueue as $key => $file) {

			unset(self::$_downloadQueue[$key]);
			$urls[$key] = $file;

		}

		$curl   = new \CURLRequest;
		$return = $curl->getThreaded(
			$urls,
			array(
			 CURLOPT_RETURNTRANSFER => true,
			 CURLOPT_FOLLOWLOCATION => true,
			),
			25
		);
		
		foreach ($return as $key => $data) {
			
			if ($data['info']['http_code'] !== 200) {

				unset(self::$_files[$key]);

				$file = basename($data['info']['url']);
				$code = $data['info']['http_code'];

				$msg = 'Skipping %s due to download error (%u).';
				$msg = sprint($msg, $file, $code);

				\Log::debug($msg, 'Minify::downloadFiles()');

			} else {
				
				$path =  self::$_cacheDir.md5($data['info']['url']);
				$k    =& self::$_files[$key];

				$k['data'] = $data['content'];
				$k['path'] = $path;
				$k['hash'] = hash(self::$_opt['algorithm'], $data['content']);

				file_put_contents($path, $data['content']);

			}//end if

		}//end foreach

	}

	static protected function detectMode()
	{

		self::$_jsMode  = false;
		self::$_cssMode = false;
		
		foreach (self::$_files as $file) {

			switch($file['ext']) {
				case 'js':
					self::$_jsMode = true;
					break;
				case 'css':
					self::$_cssMode = true;
					break;
			}

			if (self::$_jsMode !== false && self::$_cssMode !== false) {

				break;

			}

		}

	}

	static protected function validateCache()
	{
			
			$cache = file_get_contents(self::$_outputDir.self::$_opt['cacheFile']);

			if ($cache !== false) {

				$cache  = explode(PHP_EOL, $cache);
				$hashes = array();

				foreach ($cache as $line) {

					list($file, $hash) = explode(' ', $line);
					$hashes[$file]     = $hash;

				}

				foreach (self::$_files as $k => $file) {

					if (array_key_exists($file['path'], $hashes) === false) {

						\Log::debug(basename($file['path']).' - Fail!', 'Minify::validateCache()');
						return false;

					} else if ($file['hash'] !== $hashes[$file['path']]) {

						\Log::debug(basename($file['path']).' - Fail!', 'Minify::validateCache()');
						return false;

					} else {

						\Log::debug(basename($file['path']).' - OK!', 'Minify::validateCache()');
						unset($hashes[$file['path']]);

					}

				}//end foreach

				if (empty($hashes) === false) {

					return false;

				} else {
					
					return true;

				}

			} else {

				return false;

			}//end if

	}

	static protected function evaluate()
	{

		$file = self::$_outputDir.self::$_opt['cacheFile'];

		if (file_exists($file) === false) {

			\Log::debug('Cache file doesn\'t exist. Evaluation failed', 'Minify::evaluate()');
			return false;

		}

		if (self::$_jsMode === true) {

			$file = self::$_outputDir.self::$_opt['minifyFile'].'.js';

			if (file_exists($file) === false) {

				\Log::debug('Compressed file doesn\'t exist. Evaluation failed', 'Minify::evaluate()');
				return false;

			}

		}

		if (self::$_cssMode === true) {

			$file = self::$_outputDir.self::$_opt['minifyFile'].'.css';

			if (file_exists($file) === false) {

				\Log::debug('Compressed file doesn\'t exist. Evaluation failed', 'Minify::evaluate()');
				return false;

			}

		}

		$valid = self::validateCache();
		return $valid;

	}

	static protected function compressFiles()
	{

		ini_set('max_execution_time', 120);

		self::$_mincode['js']  = '';
		self::$_mincode['css'] = '';

		$curl = new \CURLRequest;
		$css  = new \CSSCompression();
		
		$css->option('readability', \CSSCompression::READ_NONE);
		$css->option('mode', self::$_opt['cssLevel']);

		foreach (self::$_files as $file) {

			$code  = $file['data'];
			$hash  = md5($code);
			$cache = self::$_cacheDir.$hash;

			if (file_exists($cache) === true) {

				self::$_mincode[$file['ext']] .= file_get_contents($cache);

			} else {

				if (self::$_opt['compressCode'] === false) {

					self::$_mincode[$file['ext']] .= $code;

				} else {

					if ($file['ext'] === 'js') {

						if (self::$_opt['useLocalJS'] === false) {

							if (((strlen($code) / 1000) / 1000) > 1) {

								$file = basename($file['path']);

								$msg  = '%s is bigger than 1000kB,';
								$msg .= ' split the code into multiple files or';
								$msg .= ' enable local compression for javascript.';
								$msg  = sprintf($msg, $file);

								\Log::error($msg, 'Minify::compressFiles()');
								throw new MinifyException($msg);

							}

							$post = array(
									 'js_code'           => $code,
									 'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
									 'output_format'     => 'json',
									);

							// Workaround to allow multiple output_info in query.
							$post  = http_build_query($post);
							$post .= '&output_info=errors&output_info=compiled_code';

							$return = $curl->get(
								'http://closure-compiler.appspot.com/compile',
								array(
								 CURLOPT_RETURNTRANSFER => true,
								 CURLOPT_POSTFIELDS     => $post,
								 CURLOPT_POST           => true,
								)
							);

							$data = json_decode($return['content'], true);

							if (isset($data['errors']) === true
								|| isset($data['serverErrors']) === true
							) {

								$error = $data['errors'][0]['error'];
								$file  = basename($file['path']);
								$line  = $data['errors'][0]['lineno'];

								$msg = 'Web Service returned %s in %s on line %u.';
								$msg = sprintf($msg, $error, $file, $line);

								\Log::error($msg, 'Minify::compressFiles()');
								throw new MinifyException($msg);

							} else if (isset($data['compiledCode']) === true) {
								
								$code = $data['compiledCode'];

								self::$_mincode[$file['ext']] .= $code;
								file_put_contents($cache, $code);

							} else {

								\Log::error('An unknown error has occured.', 'Minify::compressFiles()');
								throw new MinifyException('An unknown error has occured.');

							}//end if

						}//end if

					} else if ($file['ext'] === 'css') {
						

						$code = trim($css->compress($code));

						self::$_mincode[$file['ext']] .= $code;

						file_put_contents($cache, $code);

					}//end if

				}//end if

			}//end if

		}//end foreach

	}

	static protected function saveFiles()
	{

		if (self::$_jsMode === true) {

			$name = self::$_outputDir.self::$_opt['minifyFile'].'.js';

			file_put_contents($name, self::$_mincode['js']);
			chmod(self::$_outputDir.self::$_opt['minifyFile'].'.js', 0775);

		}

		if (self::$_cssMode === true) {

			$name = self::$_outputDir.self::$_opt['minifyFile'].'.css';

			file_put_contents($name, self::$_mincode['css']);
			chmod(self::$_outputDir.self::$_opt['minifyFile'].'.css', 0775);

		}

	}

	static protected function saveCacheFile()
	{

		$cache = '';

		foreach (self::$_files as $file) {

			$cache .= $file['path'].' '.$file['hash'].PHP_EOL;

		}

		file_put_contents(self::$_outputDir.self::$_opt['cacheFile'], trim($cache));

	}

}