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
require_once('SqlBuilder/SqlBuilderAbstract.php');
require_once('SqlBuilder/SqlBuilderSelect.php');
require_once('SqlBuilder/SqlBuilderUpdate.php');
require_once('SqlBuilder/SqlBuilderInsert.php');
require_once('SqlBuilder/SqlBuilderDelete.php');
require_once('SqlBuilder/SqlBuilderExpression.php');
//builder exceptions
require_once('SqlBuilder/SqlBuilderExceptions.php');


//api
require_once('SqlDatabase/SqlDatabase.php');

//bootstrap
require_once('SqlBootstrap/SqlBuilderBootstrapAbstract.php');
require_once('SqlBootstrap/SqlBootstrap.php');


/**
 * Class Sql
 * 
 * This class wraps the the separate classes together
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
final class Sql extends SqlBuilderAbstract
{

	protected $SqlClass = null;
	protected $bootstrap;
	//protected $joins = array();
	public $DbApi = null;



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
	function __construct( $type = null )
	{
		// we are not constructing the Abstract
		// we really only need to extend the abstract for the static Expression method
		// boot strap should only be called ONCE so it's instantiated BEFORE
		// the abstract __construct();  reason being is that for every new 
		// sql object created a bootstrap will be created, we don't need that
		// we only need an element FROM the bootstrap, so call it first
		$this->bootstrap = new SqlBootstrap;
		parent::__construct($this->bootstrap);
		if ( !is_object($this->DbApi) ) {
			$this->DbApi = new SqlDatabase;
			parent::$db =& $this->DbApi;
			$dsn = $this->bootstrap->load('dsn');
			if ( is_array($dsn) || is_string($dsn) ) {
				$this->DbApi->setup($dsn);
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
			$this->SqlClass = new SqlBuilderSelect($this->bootstrap);
		}
		return $this->SqlClass->__invoke($table, $fields, $where);
	}


	protected function loadSqlClass( $method )
	{
		if ( $method == 'select' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderSelect($this->bootstrap);
			$this->SqlClass->db =& $this->DbApi;
		}
		elseif ( $method == 'update' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderUpdate($this->bootstrap);
			$this->SqlClass->db =& $this->DbApi;
		}
		elseif ( $method == 'insert' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderInsert($this->bootstrap);
			$this->SqlClass->db =& $this->DbApi;
		}
		elseif ( $method == 'delete' || $method == 'truncate' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderDelete($this->bootstrap);
			$this->SqlClass->db =& $this->DbApi;
		}
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

		$syntax_type = $this->DbApi->getConnectionType();
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
		if (is_object($this->SqlClass)){
			$this->SqlClass->_setSyntax($syntax_type);
		}
		if ( $method == 'get' ) {
			$this->__destroyObject();
			$this->SqlClass = new SqlBuilderSelect($this->bootstrap);
			$this->SqlClass->_setSyntax($syntax_type);
			for ( $i=0; $i<3; $i++ ) {
				if (!isSet($params[$i])) {
					$params[$i] = null;
				}
			}
			$this->SqlClass->__invoke($params[0], $params[1], $params[2]);
			if (! $this->DbApi->checkConnection() ){
				throw new SqlException('No database set for get');
			}
			else {
				$rows = $this->DbApi->query($this->SqlClass->__toString());
				return $rows;
			}
		}

		if ( preg_match("/^\w+join$/", $method) ) {
			array_unshift($params, $method);
			$this->SqlClass = call_user_func_array(array($this->SqlClass, 'allJoins'),$params);
			return $this;
		}
		elseif ( method_exists($this->SqlClass, $method) ) {
			$this->SqlClass = call_user_func_array(array($this->SqlClass, $method), $params);
			return $this;
		}
		// this is an explicit database call so route it to the database wrapper
		elseif ( method_exists($this->DbApi, $method) || method_exists($this->DbApi->current_method, $method)) {
			if ($method == 'execute' || $method == 'query') {
				array_push($params,$this->SqlClass->__toString());
			}
			$returned_data = call_user_func_array(array($this->DbApi, $method), $params);
			if ($method == 'switchServer' && is_object($this->SqlClass)) {
				$syntax_type = $this->DbApi->getConnectionType();
				$this->SqlClass->_setSyntax($syntax_type);
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
		return $this->SqlClass."";
	}
}


