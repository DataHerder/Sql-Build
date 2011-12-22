<?php

/**
 * Sql Wrapper File containing the wrapper for the Sql Builder Class
 * 
 * 
 * Copyright (C) 2011  Paul Carlton
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
 * @link        my.public.repo
 * @since       File available since
 */


//sql builder requires
require_once('SqlBuilder/Abstracts/SqlBuilderAbstract.php');
require_once('SqlBuilder/SqlBuilderSelect.php');
require_once('SqlBuilder/SqlBuilderUpdate.php');
require_once('SqlBuilder/SqlBuilderInsert.php');
require_once('SqlBuilder/SqlBuilderDelete.php');
require_once('SqlBuilder/SqlBuilderAlter.php');
require_once('SqlBuilder/SqlBuilderExpression.php');
//builder exceptions
require_once('SqlBuilder/SqlBuilderExceptions.php');


//api
require_once('SqlDatabase/SqlDatabase.php');

//bootstrap
require_once('SqlBootstrap/SqlBuilderBootstrapAbstract.php');
/**
 * A bootstrap is uniquely created by you, below is where the
 * example bootstrap is located.. A bootstrap should created
 * BEFORE instantiating Sql ex:
 * 
 * 	class SqlBootstrap extends SqlBootstrapAbstract {
 * 		protected function _init() {...}
 * 	}
 * 	$sql = new Sql;
 * 
 * You can either change the below file to spec and uncomment
 * this line or you can create your own bootstrap somewhere else
 * and include it in your file after including this file (Sql.php).
 */
// require_once('SqlBootstrap/SqlBootstrap.php');
// require_once('SqlBootstrap/MyBootstrap/MyBootstrap.php');


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
final class Sql //extends SqlBuilderAbstract
{

	protected $SqlClass = null;
	protected $bootstrap;
	protected $log = false;
	protected $logfile = 'logfile.sql';
	protected $verbose = false;
	protected static $syntax = 'mysql';
	protected static $DbApi = null;



	/**
	 * By calling a type in __construct allows you to take advantage
	 * of the __invoke function for quick instantiation on inserts and updates
	 * 
	 * $sql = new Sql('insert');
	 * $rows = $sql('table',array(array('field1,'field2'),array('field1','field2')))->execute();
	 * 
	 * @param (string) $type
	 * @return (object) $this
	 */
	function __construct( $type = null, $db_ref = null )
	{
		// we are not constructing the Abstract
		// we really only need to extend the abstract for the static Expression method
		// boot strap should only be called ONCE so it's instantiated BEFORE
		// the abstract __construct();  reason being is that for every new 
		// sql object created a bootstrap will be created, we don't need that
		// we only need an element FROM the bootstrap, so call it first
		if ($type !== false ) { //self instantiated
			$this->bootstrap = new SqlBootstrap;
			self::$DbApi = new SqlDatabase;
			$dsn = $this->bootstrap->load('dsn');
			if ( is_array($dsn) || is_string($dsn) ) {
				try {
					self::$DbApi->setup($dsn);
					$syntax_type = self::$DbApi->getConnectionType();
					self::$syntax = $syntax_type;
				} catch (Exception $e) {
					print $e->getMessage();
					print pg_last_error();
					die;
				}
			}
			if ( $type == null ) {
				return; //do nothing
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


	public static function expr($type, $data, $alias = null)
	{
		$Expr = new SqlBuilderExpression($type, $data, $alias);
		return $Expr;
	}



	/**
	 * The most basic call of the class $Sql('table','field','where');
	 * 
	 * @param (string) $table
	 * @param (string) $fields
	 * @param (string) $where
	 * @return (object) $this
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
		$this->SqlClass->__invoke($table, $fields, $where);
		return $this;
	}


	protected function loadSqlClass( $method )
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
			$this->SqlClass = new SqlBuilderBuildAlter(self::$syntax, $this->bootstrap);
			$this->SqlClass->db =& self::$DbApi;
		}
	}



	public static function gi() {
		$db_ref =& self::$DbApi;
		$B = new Sql(false, $db_ref);
		return $B;
	}


	/**
	 * This essentially wraps the classes together with the magic method __call
	 * 
	 * @param (string) $method
	 * @param (array) $params
	 * @return (object|string|array) $this->SqlClass
	 */
	public function __call( $method, $params=array() )
	{
		$syntax_type = self::$DbApi->getConnectionType();
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
			throw new SqlException('Method does not exist for SqlBuilder');
		}
		return $this;
	}

	/**
	 * returns the executed raw class
	 * needs added support for sql, this is where
	 * the database API will be called and all future
	 * 
	 * access public
	 * param null
	 * return mixed
	 */
	/*public function execute()
	{
		// return $this;
	}*/


	/**
	 * this function destroys SqlClass before instantiating a new one
	 * 
	 * @access protected
	 * @param null
	 * @return null
	 */
	protected function __destroyObject()
	{
		// detroy $this->SqlClass before creating a new one
		if ( is_object($this->SqlClass) ) {
			unset($this->SqlClass);
		}
	}



	public function log( $verbos = false )
	{
		$this->log = true;
		$this->verbose = $verbos;
		return $this;
	}

	public function unlog()
	{
		$this->log = false;
		$this->verbose = false;
		return $this;
	}



	private function logSql( $sql )
	{
		//this will log an sql file
		$fh = fopen($this->logfile, 'a'); // or die("Can't open file");
		#print 'h';
		if (!$fh) {
		#	//throw new SqlException('Unable to load log file!  Check your permissions or the directory you want to store your sqls in.');
			die('Unable to load log file!  Check your permissions or the directory you want to store your data.');
		}
		fwrite($fh, $sql."\n");
		fclose($fh);
	}


	/**
	 * the point of this function is to constrain what certain
	 * public functions are allowable depending on the Class type
	 * for instance joins.  You can join on updates and selects
	 * but not inserts
	 * 
	 * @access protected
	 * @return string (select|update|insert|Expresssion|Delete)
	 */
	protected function sniffMyself()
	{
		// sniff myself
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
		elseif ( $this->SqlClass instanceof SqlBuilderBuildAlter ) {
			return 'alter';
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



	public function __toString()
	{
		$sql = $this->SqlClass."";

		if ($this->log) {
			$this->logSql($sql);
		}
		return $sql;
	}
}


