<?php

/*
	@author boctulus
*/

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Debug;

class Files
{	
	static protected $print_logs = false;

	static function printLogs(bool $status = true){
		static::$print_logs = $status;
	}

	/*
		Resultado:

		<?php 

		$arr = array (
		'x' => 'Z',
		);
	*/
	static function varExport($path, $data, $variable = '$arr'){
		if ($variable === null){
			$bytes = file_put_contents($path, '<?php '. "\r\n\r\n" . 'return ' . var_export($data, true). ';');
		} else {
			$bytes = file_put_contents($path, '<?php '. "\r\n\r\n" . $variable . ' = ' . var_export($data, true). ';');
		}

		return ($bytes > 0);
	}

	static function JSONExport($path, $data){
		$bytes = file_put_contents($path, json_encode($data));
		return ($bytes > 0);
	}

	/*
		https://www.codewall.co.uk/write-php-array-to-csv-file/
		https://fuelingphp.com/how-to-convert-associative-array-to-csv-in-php/
	*/
	static function arrayToCSV(string $filename, Array $array){
		if (!Strings::endsWith('.csv', strtolower($filename))){
			$filename .= '.csv';
		}

		$f = fopen($filename, 'a'); // Configure fopen to create, open, and write data.
 
		fputcsv($f, array_keys($array[0])); // Add the keys as the column headers
		
		// Loop over the array and passing in the values only.
		foreach ($array as $row)
		{
			fputcsv($f, $row);
		}
		// Close the file
		fclose($f);
	}

	static function getCSV(string $path, $assoc = true){	
		$rows = [];

		ini_set('auto_detect_line_endings', 'true');

		$handle = fopen($path,'r');

		$cabecera = fgetcsv($handle);
		$ch       = count($cabecera);
		
		$i = 0;
		while ( ($data = fgetcsv($handle) ) !== FALSE ) {
			if ($assoc){
				for ($j=0;$j<$ch; $j++){					
					$head_key = $cabecera[$j];
					$val      = $data[$j] ?? '';

					$rows[$i][$head_key] = $val;
				}
			} else {
				$rows[] = $data;
			}	

			$i++;		
		}
		
		ini_set('auto_detect_line_endings', 'false');

		return [
			'rows' => $rows,
			'head' => $cabecera
		];
	}
	static function write(string $path, string $string, int $flags = 0) : bool {
		$ok = (bool) @file_put_contents($path, $string, $flags);
		return $ok;
	}

	/*
		Escribe archivo o falla.
	*/
	static function writeOrFail(string $path, string $string, int $flags = 0){
		if (empty($path)){
			throw new \InvalidArgumentException("path is empty");
		}

		if (is_dir($path)){
			throw new \InvalidArgumentException("$path is not a valid file. It's a directory!");
		}

		$dir = Strings::beforeLast($path, DIRECTORY_SEPARATOR);

		static::writableOrFail($dir, "$path is not writable");

		$ok = (bool) @file_put_contents($path, $string, $flags);

		if (!$ok){
			throw new \Exception("$path could not be written");
		}
	}

	static function mkDir($dir, int $permissions = 0777, bool $recursive = true){
		$ok = null;

		if (!is_dir($dir)) {
			$ok = @mkdir($dir, $permissions, $recursive);
		}

		return $ok;
	}
	
	static function mkDirOrFail($dir, int $permissions = 0777, $recursive = true, string $error = "Failed trying to create %s"){
		$ok = null;

		if (!is_dir($dir)) {
			$ok = @mkdir($dir, $permissions, $recursive);
			if ($ok !== true){
				throw new \Exception(sprintf($error, $dir));
			}
		}

		return $ok;
	}

	static function writableOrFail(string $path, string $error = "'%s' is not writable"){
		if (!is_writable($path)){
			throw new \Exception(sprintf($error, $path));
		}
	}

	/*
		Files::logger([
			'x' => 'y'
		]);
	*/
	static function logger($data){	
		if (is_array($data) || is_object($data))
			$data = json_encode($data);
		
		// En /home/www/woo4/wp-content/error.log
		error_log($data);
	}

	static function dump($object){
		$data = var_export($object,  true);
		
		error_log($data);
	}

	static function localLogger($data, $filename = 'log.txt'){	
		$dir  = $path = __DIR__ . '/../logs/';
		$path = __DIR__ . '/../logs/'. $filename; 

		Files::mkDirOrFail($dir); //
		
		if (is_array($data) || is_object($data))
			$data = json_encode($data);
		
		$data = date("Y-m-d H:i:s"). "\t" .$data;

		if (static::$print_logs){
			dd($data);
		}

		return file_put_contents($path, $data. "\n", FILE_APPEND);
	}

	static function localDump($object, $filename = 'dump.txt', $append = false){
		$path = __DIR__ . '/../logs/'. $filename; 

		if ($append){
			file_put_contents($path, var_export($object,  true) . "\n", FILE_APPEND);
		} else {
			file_put_contents($path, var_export($object,  true) . "\n");
		}		
	}

	// NEW!
	static function dd($value, $title = null, $filename = 'dump.txt', $append = false){
		if (empty($title)){
			static::localDump($value, $filename, $append);
			return;
		}

		static::localDump([
			$title => $value
		], $filename, $append);
	}

	static function get_rel_path(){
		$ini = strpos(__DIR__, '/wp-content/');
		$rel_path = substr(__DIR__, $ini);
		$rel_path = substr($rel_path, 0, strlen($rel_path)-4);
		
		return $rel_path;
	}			
	

}







