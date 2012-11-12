<?php
namespace SqlBuilder\SqlBootstrap;

/**
 * Static library for the bootstrap
 */


class SqlBootstrapLibrary {
	private function __construct(){}

	public function initAutoload()
	{
		// register the autoload function
		spl_autoload_register(function($class_name){
			// get the location of the bootstrap file
			$dir = explode("/", __FILE__);
			// pop off the location of the bootstrap file
			array_pop($dir);array_pop($dir);
			$dir = join('/', $dir);
			$parts = explode("\\", $class_name);
			// shift off "SqlBuilder" - essentially Sql-Build;
			array_shift($parts);
			// join together
			$file_dir = $dir.'/'.join("/", $parts).".php";
			// if it's readable require the file
			if (is_readable($file_dir)) {
				require_once($file_dir);
			}
		});
	}
}