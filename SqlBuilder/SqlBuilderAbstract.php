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


interface SqlBuilderInterface
{
	public function tableFormat($table);
	public function formatColumns( $column );
	public function formatValues( $value );
	public function isAssoc(array $array);
	public static function expr( $type, $val = null);
}


/**
 * SqlBuilderAbstract has the core functions that the 
 * sql update, insert, and select work from
 * 
 * @category Structured Query Language
 * @package Sql
 * @subpackage SqlBuilder
 * @author Paul Carlton
 * @link my.public.repo
 */
abstract class SqlBuilderAbstract implements SqlBuilderInterface
{

	protected $database_type = 'mysql';
	protected $syntax = 'mysql';
	protected static $joins = array();

	// DbApi reference for statements
	protected static $db;

	protected $func_format = null;

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
		$table = preg_replace('/`|"/', '', $table);
		$period = false;
		if ( substr($table, 0, 1) == '.' ) {
			$period = true;
			$new_t = substr($table, 1);
		} else {
			$new_t = $table;
		}
		if ( strpos($new_t,'.')!== false && !$table instanceof SqlBuilderSelect ) {
			$tmp = explode('.', $new_t);
			$table = $this->_tableFormatHelper($tmp[0]). '.' . $this->_tableFormatHelper($tmp[1]);
			return $table;
		}
		else {
			return $this->_tableFormatHelper($table);
		}
	}


	/**
	 * formatTable shorthand and SqlBuilderExpression sniffer
	 * 
	 * @access protected
	 * @param string $table
	 * @return string
	 */
	protected function _tableFormatHelper($table) {
		if ( $table instanceof SqlBuilderSelect || $table instanceof SqlBuilderExpression ) {
			return '('.$table.')';
		}
		elseif ($this->syntax == 'mysql'){
			return '`' . trim($this->_bootstrapTableFormat($table)) . '`';
		}
		elseif ($this->syntax == 'postgres') {
			return '"' . trim($this->_bootstrapTableFormat($table)) . '"';
		}
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
	public function formatValues( $value = null )
	{
		if ($value == null) {
			return 'NULL';
		}
		elseif ( is_numeric($value)) {
			return $value;
		}
		elseif ( is_string($value) ){
			$value = "'" . self::$db->escape($value) . "'";
		}
		elseif ( $value instanceof SqlBuilderExpression ) {
			$value = $value.'';
		}
		elseif ($value instanceof SqlBuilderSelect) {
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
		$column = preg_replace('/`|"/','',$column);
		if ( strpos($column, '.') !== false ) {
			$column = $this->checkTableFormat($column);
		}
		$column = self::$db->formatColumn($column);
		return $column;
	}




	/**
	 * Constructer class, determines if we need to create a mysql connection
	 * This does not create a mysql connection on default, parameters need to be
	 * passed
	 * 
	 * @access public
	 * @param mixed $dsn
	 * @return object $this
	 */
	public function __construct( $bootstrap = null )
	{
		if ( $bootstrap != null ) {
			$this->func_format = $bootstrap->getPreformatTableFunc();
		}
		$this->range = range('a', 'z');
		return $this;
	}


	protected function getFieldType()
	{
		//$type = parent::$db->getConnectionType();
		$type = self::$db->getConnectionType();
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




	/**
	 * allJoins is a protected function that acts as the wrapper for 
	 * tableJoin, that way all joins go through the same function
	 * to reduce code
	 * 
	 * @access protected
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	protected function allJoins( $table=null, $on=null, $type )
	{
		return $this->tableJoin($table, $on, $type);
	}


	/**
	 * This function sniffs for type and throws exceptions if
	 * data is not presented correctly for the join
	 * 
	 * @access protected
	 * @param string $table
	 * @param string $on
	 * @param string $type
	 * @return object $this
	 */
	protected function tableJoin($table, $on, $type)
	{
		$this->checkForJoins();
		if (!is_string($table) && !$this->isAssoc($table)) {
			throw new SqlAbstractException('String or associative array (alias) expected for table in '.$type.'Join');
		}
		if (! is_string($on) ) {
			throw new SqlAbstractException('INNER JOIN clause ON expects string.');
		}
		$table = $table;
		self::$joins[] = array($type,$table,$on);
		return $this;
	}





	/**
	 * checkForJoins makes sure joins are performed only on certain classes
	 * by throwing an exception if it does not perform
	 * 
	 * @return null
	 */
	protected function checkForJoins()
	{
		$a = $this->sniffMyself();
		if ( $a != 'select' && $a != 'update' ) {
			throw new SqlAbstractException('Can not perform joins anything else but updates and selects');
		}
	}




	/**
	 * inner join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function innerJoin( $table=null, $on=null )
	{
		//dbg_array($this);
		return $this->allJoins($table, $on, 'inner');
	}




	/**
	 * outer join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function outerJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'outer');
	}
	




	/**
	 * left join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function leftJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'left');
	}
	



	/**
	 * right join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function rightJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'right');
	}
	



	/**
	 * join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function join( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, '');
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
		// sniff myself you know where
		// mmmm... smells good
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
	}

}
