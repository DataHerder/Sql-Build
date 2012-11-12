<?php 
/**
 * Sql Abstraction File containing the abstract and interface for the Sql Builder Classes
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
namespace SqlBuilder\SqlClasses\Abstracts;

use \SqlBuilder\SqlClasses\Abstracts\SqlBuilderWhereAbstract as SqlBuilderWhereAbstract;
use \SqlBuilder\SqlClasses\Abstracts\SqlBuilderJoinsAbstract as SqlBuilderJoinsAbstract;
use \SqlBuilder\SqlClasses\SqlBuilderExpression as SqlBuilderExpression;

/**
 * SqlBuilderAbstract has the core functions that the
 * sql update, insert, and select work from
 *
 * @package SqlBuilderAbstract
 */
abstract class SqlBuilderAbstract extends SqlBuilderJoinsAbstract implements SqlBuilderInterface
{

	protected $database_type = 'mysql';
	protected $syntax = 'mysql';
	// DbApi reference for statements
	// protected static $db;
	// public $db;
	protected $func_format = null;


	/**
	 * Constructer class, determines if we need to create a mysql connection
	 * This does not create a mysql connection on default, parameters need to be
	 * passed
	 *
	 * @access public
	 * @param null $bootstrap
	 * @return \SqlBuilder\SqlClasses\Abstracts\SqlBuilderAbstract $this
	 */
	public function __construct( $bootstrap = null )
	{
		if ( $bootstrap != null ) {
			$this->func_format = $bootstrap->getPreformatTableFunc();
		}
		$this->range = range('a', 'z');
		return $this;
	}


	/**
	 * An important element here where this static function
	 * allows expressions and the clause DISTINCT.
	 * This returns an object SqlExp that can be evaluated.
	 * All one needs to do is pass:
	 *
	 * Sql::expr('distinct','a.data');
	 *
	 * Or use the variable you created: $Sql::expr() for shorter typing
	 *
	 * @access public
	 * @param string $type
	 * @param mixed $val
	 * @param null $as_alias
	 * @return SqlBuilderExpression $Expression
	 */
	public static function expr($type, $val = null, $as_alias = null)
	{
		$Expression = new SqlBuilderExpression($type, $val, $as_alias);
		return $Expression;
	}


	protected function _setSyntax($syntax)
	{
		$this->syntax = $syntax;
	}




	/**
	 * This function formats tables and ensures that if a period
	 * is used as the beginning of a table name for shortcuts
	 * it is correctly handled rather than splitting it up
	 * 
	 * @access public
	 * @param string $table
	 * @return string
	 */
	public function tableFormat( $table )
	{
		//first check if there is a period
		//if ( $table instanceof SqlBuilderSelect || $table instanceof SqlBuilderExpression ) {
		if ( $table instanceof Sql || $table instanceof SqlBuilderExpression ) {
			return '('.$table.')';
		}
		$table = preg_replace('/`|"/', '', $table);
		$period = false;
		if ( substr($table, 0, 1) == '.' ) {
			$period = true;
			$new_t = substr($table, 1);
		} else {
			$new_t = $table;
		}
		if ( strpos($new_t,'.')!== false ) {
			$tmp = explode('.', $new_t);
			$table = $this->_tableFormatHelper($tmp[0]). '.' . $this->_tableFormatHelper($tmp[1]);
			return $table;
		}
		else {
			return $this->_tableFormatHelper($table);
		}
	}


	/**
	 * formatTable shorthand
	 * 
	 * @access protected
	 * @param string $table
	 * @return string
	 */
	protected function _tableFormatHelper($table) {
		//$table = trim($this->_bootstrapTableFormat($table));
		//return $this->db->formatTable($table);
		return $table;
	}



	/**
	 * protected formatTable function that checks for user defined function
	 * which formats the table accordingly...
	 * 
	 * For example this would be the function you define in your index file that is read
	 * by the class.
	 * 
	 * $SqlBuilder_Table_Format = function($table){ 
	 *   if (substr($table,0,1) == '.') {
	 *     //add something to beginning of the file name
	 *   }
	 * }
	 * 
	 * @access protected
	 * @param string $table
	 * @return string $table
	 */
	protected function _bootstrapTableFormat( $table )
	{
		//allows shorthands to tables
		if ( is_callable($this->func_format) ) {
			$t = $this->func_format;
			return $t($table);
			//return $this->func_format($table);
		}
		else {
			return $table;
		}
	}




	/**
	 * formatValues shorthand value formatting
	 * 
	 * @access public
	 * @param string $value
	 * @return string $value
	 */
	public function formatValues($value = null)
	{
		if ($value == null) {
			return 'NULL';
		}
		elseif ( is_numeric($value)) {
			return $value;
		}
		elseif ( is_string($value) ){
			if (is_object($this->db)) {
				$value = "'" . $this->db->escape($value) . "'";
			} else {
				// dangerous! when connected to a db the values are escaped
				$value = "'" . $value . "'";
			}
		}
		elseif ( $value instanceof SqlBuilderExpression ) {
			$value = $value.'';
		}
		elseif ($value instanceof Sql) {
			$value = '(' . $value . ')';
		}
		else {
			$value = "''";
		}
		return $value;
	}


	/**
	 * formatColumns shorthand column formatting
	 * 
	 * @access public
	 * @param string $column
	 * @return string $column
	 */
	public function formatColumns( $column )
	{
		if ( $column == '*' ) {
			return $column;
		}
		elseif ($column instanceof Sql || $column instanceof SqlBuilderExpression ) {
			return '(' . $column . ')';
		}
		elseif ( strpos($column, '.') !== false ) {
			$column = preg_replace('/`|"/','',$column);
			if (is_object($this->db)) {
				$column = $this->db->formatColumn($column);
			}
		}
		else {
			if (is_object($this->db)) {
				$column = $this->db->formatColumn($column);
			}
		}
		return $column;
	}







	protected function getFieldType()
	{
		if (is_object($this->db)) {
			$type = $this->db->getConnectionType();
		} else {
			$type = 'mysql';
		}
		$field_type = '`';
		if ($type == 'mysql') {
			$field_type = '`';
		}
		elseif ($type == 'postgres') {
			$field_type = '"';
		}
		return $field_type;
	}



	/**
	 * isAssoc checks to see if an array is an associative array or not
	 * 
	 * @access public
	 * @param array $array
	 * @return bool
	 */
	public function isAssoc(array $array)
	{
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}




}
