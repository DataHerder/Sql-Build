<?php
/**
 * Sql Wrapper File containing the wrapper for the Sql Builder Class
 * 
 * 
 * Copyright (C) 2012  Paul Carlton
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @category    Structured Query Language
 * @package     Sql
 * @license     GNU license
 * @version     0.1
 * @link        https://github.com/DataHerder/Sql-Build
 */


namespace SqlBuilder;

// require the bootstrap for autoloading
require "SqlBootstrap/SqlBootstrapLibrary.php";

// define bootstrap namespace shortcut
use SqlBuilder\SqlBootstrap\SqlBootstrapLibrary as SqlBootstrapLibrary;
// run bootstrap autoload on startup
SqlBootstrapLibrary::initAutoload();

// define the namespaces
use SqlBuilder\SqlDatabase\SqlDatabase as SqlDatabase;
use SqlBuilder\SqlClasses\SqlBuilderSelect as SqlBuilderSelect;
use SqlBuilder\SqlClasses\SqlBuilderUpdate as SqlBuilderUpdate;
use SqlBuilder\SqlClasses\SqlBuilderInsert as SqlBuilderInsert;
use SqlBuilder\SqlClasses\SqlBuilderDelete as SqlBuilderDelete;
use SqlBuilder\SqlClasses\SqlBuilderRaw as SqlBuilderRaw;
use SqlBuilder\SqlClasses\SqlBuilderAlter as SqlBuilderAlter;
use SqlBuilder\SqlClasses\SqlBuilderExpression as SqlBuilderExpression;

/**
 * Class Sql
 * 
 * This class wraps the separate classes together
 * for quick instantiation and ease of use rather than
 * having to create different instants
 * 
 * $sql = new Sql;
 * 
 * //now I'm a select
 * $sql->select();
 * 
 * //now I'm an update
 * $sql->update();
 * 
 * //now I'm an insert
 * $sql->insert();
 * 
 * etc...
 * 
 * @category    Structured Query Language
 * @package     Sql
 * @license     GNU license
 * @version     0.1
 * @link        my.public.repo
 * @since       File available since
 */
final class Sql
{

	private $SqlClass = null;
	private $bootstrap;
	private $log = false;
	private $logfile = 'logfile.sql';
	private $verbose = false;
	private static $syntax = 'mysql';
	private static $DbApi = null;


	/**
	 * By calling a type in __construct allows you to take advantage
	 * of the __invoke function for quick instantiation on inserts and updates
	 *
	 * $sql = new Sql('insert', new Bootstrap);
	 * $rows = $sql('table',array(array('field1,'field2'),array('field1','field2')))->execute();
	 *
	 * @param mixed $type_or_params
	 * @param mixed $bootstrap
	 * @internal param null $type
	 * @internal param null $db_ref
	 * @internal param $ (string) $type
	 * @return \SqlBuilder\Sql (object) $this
	 */
	function __construct($type_or_params = array(), &$bootstrap = null)
	{
		if (is_string($type_or_params)) {
			$type = $type_or_params;
			$type = null;
			$db_ref = null;
		} elseif (is_array($type_or_params)) {
			if (isSet($type_or_params['type'])) {
				$type = $type_or_params['type'];
			} else {
				$type = null;
			}
			if (isSet($type_or_params['db_ref'])) {
				$db_ref = null;
			}
		} elseif (is_object($type_or_params) && $type_or_params instanceof \SqlBuilder\SqlBootstrap\SqlBootstrapAbstract) {
			$bootstrap = $type_or_params;
			$type = 'select';
			$db_ref = null;
		}
		// we are not constructing the Abstract
		// we really only need to extend the abstract for the static Expression method
		// boot strap should only be called ONCE so it's instantiated BEFORE
		// the abstract __construct();  reason being is that for every new 
		// sql object created a bootstrap will be created, we don't need that
		// we only need an element FROM the bootstrap, so call it first
		if (is_string($type)) { //self instantiated
			if (is_object($bootstrap) && $bootstrap instanceof \SqlBuilder\SqlBootstrap\SqlBootstrapAbstract) {
				$this->bootstrap = $bootstrap;
			} else {
				$this->bootstrap = new \SqlBootstrap;
			}
			self::$DbApi = new SqlDatabase;
			$dsn = $this->bootstrap->load('dsn');
			if ( is_array($dsn) || is_string($dsn) ) {
				try {
					self::$DbApi->setup($dsn);
					$syntax_type = self::$DbApi->getConnectionType();
					self::$syntax = $syntax_type;
				} catch (\Exception $e) {
					print $e->getMessage();
					print pg_last_error();
					die;
				}
			}
			if ( $type == null ) {
				return null; //do nothing
			}
			else {
				//this all
				$this->loadSqlClass($type);
				return $this;
			}
		}
		elseif ($type === false ) {  // self instantiated... DO NOT DUPLICATE DATABASE CONNECTION
			// we want a reference, we do not want to instantiate anything new
			// when we embed statements (primarily SELECT statements)
			self::$DbApi = $db_ref;
			if (is_object(self::$DbApi)) {
				$syntax_type = self::$DbApi->getConnectionType();
				self::$syntax = $syntax_type;
			}
		}
	}


	/**
	 * Creates an expression object on the fly for sql statements
	 *
	 * @static
	 * @access public
	 * @param $type
	 * @param $data
	 * @param null $alias
	 * @return SqlClasses\SqlBuilderExpression
	 */
	public static function expr($type, $data, $alias = null)
	{
		$Expr = new SqlBuilderExpression($type, $data, $alias);
		return $Expr;
	}

	/**
	 * Shorthand shell for expr($type, $data, $alias = null)
	 *
	 * @see Sql::expr
	 * @static
	 * @access public
	 * @param $type
	 * @param $data
	 * @param null $alias
	 * @return SqlClasses\SqlBuilderExpression
	 */
	public static function e($type, $data, $alias = null) {
		return self::expr($type, $data, $alias);
	}


	/**
	 * The most basic call of the class $Sql('table','field','where');
	 *
	 * @param null $table
	 * @param null $fields
	 * @param null $where
	 * @return \SqlBuilder\Sql (object) $this
	 */
	public function __invoke( $table = null, $fields = null, $where = null )
	{
		// it is assumed you want to select if no instantiated in __construct()
		// if you want to use the __invoke on update or insert
		// you must specify it like $Sql = new Sql('insert');
		if ( $this->SqlClass == null ) {
			$this->SqlClass = new SqlBuilderSelect(self::$syntax, $this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
		if (is_string($table) && is_null($fields) && is_null($where)) {
			if (preg_match("/^(select|update|insert)\s/i", trim($table))) {
				// it's a raw statement
				$this->loadSqlClass('raw');
			}
		}
		$this->SqlClass->__invoke($table, $fields, $where);
		return $this;
	}


	/**
	 * This function
	 *
	 * @param $method
	 * @throws SqlException
	 */
	private function loadSqlClass( $method )
	{
		if ( $method == 'select' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderSelect(self::$syntax, $this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
		elseif ( $method == 'update' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderUpdate(self::$syntax, $this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
		elseif ( $method == 'insert' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderInsert(self::$syntax, $this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
		elseif ( $method == 'delete' || $method == 'truncate' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderDelete(self::$syntax, $this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
		elseif ($method == 'alter' || $method == 'build') {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderAlter(self::$syntax, $this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
		elseif ($method == 'raw') {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderRaw($this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
	}


	/**
	 * This creates a new instance of the sql class;
	 * Purpose is for embedded select statements
	 * and expressions
	 *
	 * @return Sql
	 */
	public static function gi() {
		$db_ref =& self::$DbApi;
		$B = new Sql(array('type'=>false, array('db_ref'=>$db_ref)));
		return $B;
	}


	/**
	 * This essentially wraps the classes together with the magic method __call
	 *
	 * @param string $method
	 * @param array $params
	 * @throws SqlException
	 * @return mixed|\SqlBuilder\Sql (object|string|array) $this->SqlClass
	 */
	public function __call( $method, $params=array() )
	{
		if (is_object(self::$DbApi)) {
			$syntax_type = self::$DbApi->getConnectionType();
		} else {
			$syntax_type = 'mysql';
		}
		if ( $method == 'gi') {
			//get instance
			$B = new Sql;
			return $B;
		}
		if ( !$syntax_type ) {
			$syntax_type = 'mysql';
		}
		if ( $method == 'SqlClass' ) {
			return $this;
		}

		$this->loadSqlClass($method);

		//special get method whereby
		if ($method == 'setSyntax' && is_object($this->SqlClass)) {
			$this->SqlClass->_setSyntax($params[0]);
			return $this;
		}
		elseif ($method == 'setSyntax') {
			throw new SqlException('Method setSyntax can only be called after statement object has been instantiated within Sql');
		}
		if ( $method == 'get' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderSelect($syntax_type, $this->bootstrap);
			//$this->_setSyntax($syntax_type);
			for ( $i=0; $i<3; $i++ ) {
				if (!isSet($params[$i])) {
					$params[$i] = null;
				}
			}
			$this->SqlClass->__invoke($params[0], $params[1], $params[2]);
			if (! self::$DbApi->checkConnection() ){
				throw new SqlException('No database set for get');
			}
			else {
				$rows = self::$DbApi->query($this->SqlClass->__toString());
				return $rows;
			}
		}

		if ( preg_match("/join$/i", $method) ) {
			$type = $this->sniffMyself();
			if ($type != 'select' && $type != 'update') {
				throw new SqlException('Joins can only be called on selects and updates.');
			}
		}
		if ( method_exists($this->SqlClass, $method) ) {
			$data = call_user_func_array(array($this->SqlClass, $method), $params);
			if (is_object($data)) {
				return $this;
			}
			else return $data;
		}
		// this is an explicit database call so route it to the database wrapper
		elseif ( method_exists(self::$DbApi, $method) || method_exists(self::$DbApi->current_method, $method)) {
			if ($method == 'execute' || $method == 'query') {
				array_push($params,$this->SqlClass->__toString());
			}
			$returned_data = call_user_func_array(array(self::$DbApi, $method), $params);
			if ($method == 'switchServer') {
				$syntax_type = self::$DbApi->getConnectionType();
				self::$syntax = $syntax_type;
				if (is_object($this->SqlClass)) {
					$this->SqlClass->_setSyntax($syntax_type);
				}
			}
			//important strong match, api returns null if not a value or response from database server
			if ( $returned_data !== null ) {
				return $returned_data;
			} else {
				return $this;
			}
		}
		else {
			throw new SqlException('
				This method does not exist for SqlBuilder. It\'s likely you are making a database call
				when you haven\'t established a database connection yet.  Please make sure you have a
				valid database connection before calling "execute" or "query"'
			);
		}
		return $this;
	}


	/**
	 * this function destroys SqlClass before instantiating a new one
	 * 
	 * @access private
	 * @param null
	 * @return null
	 */
	private function __destroyObject()
	{
		// detroy $this->SqlClass before creating a new one
		if ( is_object($this->SqlClass) ) {
			unset($this->SqlClass);
		}
	}


	/**
	 * Turn on logging
	 *
	 * @param string $log_file
	 * @param bool $verbose
	 * @return Sql
	 */
	public function log($log_file = '', $verbose = false)
	{
		if ($log_file !== false && is_string($log_file) && $log_file != '') {
			$this->logfile = $log_file;
		}
		$this->log = true;
		$this->verbose = $verbose;
		return $this;
	}


	/**
	 * Turn off logging
	 *
	 * @return Sql
	 */
	public function unlog()
	{
		$this->log = false;
		$this->verbose = false;
		return $this;
	}


	/**
	 * Log the SQL in a file
	 *
	 * @throws SqlClasses\Exceptions\SqlException
	 */
	private function logSql()
	{
		//this will log an sql file
		$fh = fopen($this->logfile, 'a'); // or die("Can't open file");
		if (!$fh) {
			throw new SqlException('Unable to load log file! Check your permissions on the directory or file you want to store your SQL queries in.');
		}
		if ($this->verbose) {
			$sql_string = $this->explode()->__toString();
		} else {
			$sql_string = $this->__toString();
		}
		fwrite($fh, $sql_string."\n");
		fclose($fh);
	}


	/**
	 * the point of this function is to constrain what certain
	 * public functions are allowable depending on the Class type
	 * for instance joins.  You can join on updates and selects
	 * but not inserts
	 * 
	 * @access private
	 * @return string (select|update|insert|Expresssion|Delete)
	 */
	private function sniffMyself()
	{
		// sniff myself - smells goooooood
		if ( $this->SqlClass instanceof SqlBuilderSelect ) {
			// this is a select object
			return "select";
		}
		elseif ( $this->SqlClass instanceof SqlBuilderUpdate ) {
			// this is an update object
			return 'update';
		}
		elseif ( $this->SqlClass instanceof SqlBuilderInsert ) {
			// this is an insert object
			return 'insert';
		}
		elseif ( $this->SqlClass instanceof SqlBuilderExpression ) {
			return 'expression';
		}
		elseif ( $this->SqlClass instanceof SqlBuilderDelete ) {
			return 'delete';
		}
		elseif ( $this->SqlClass instanceof SqlBuilderAlter ) {
			return 'alter';
		}
		elseif ( $this->SqlClass instanceof SqlBuilderRaw ) {
			return 'raw';
		}
	}


	/**
	 * when the object is destoyed, the SqlClass
	 * is also destroyed
	 * 
	 * @access public
	 * @param null
	 * @return null
	 */
	public function __destruct()
	{
		// after script execution.
		if ( is_object($this->SqlClass) ) {
			// it is true, unset calls the destruct method
			// and in fact destroys the object
			unset($this->SqlClass);
		}
	}


	/**
	 * This of course prints out the sql string from the class
	 *
	 * It acts as a shell to the __toString() magic function found
	 * in each SqlBuilder instance
	 *
	 * @return string
	 */
	public function __toString()
	{
		$sql = $this->SqlClass."";

		/**
		 * deprecate - should be in execute and query
		 * if ($this->log) {
		 * 	$this->logSql($sql);
		 * }
		 */
		return $sql;
	}
}


/**
 * We have this class included with the file for brevity
 *
 * All other exceptions are in their respective locations in the "Exceptions" folder
 * and properly namespaced
 */
class SqlException extends \Exception
{
	public function __construct($message, $code = 0, \Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
