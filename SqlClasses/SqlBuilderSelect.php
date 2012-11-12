<?php 
/**
 * Sql Select File containing the class for select statements
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
 * @package     SqlBuilder
 * @license     GNU license
 * @version     0.1
 * @link        my.public.repo
 * @since       File available since
 */

namespace SqlBuilder\SqlClasses;
use \SqlBuilder\SqlClasses\Abstracts\SqlBuilderAbstract as SqlBuilderAbstract;
use \SqlBuilder\SqlClasses\Exceptions\SqlBuilderSelectException as SqlBuilderSelectException;

/**
 * SqlBuilderSelect, constructs builder class
 * currently supports joins and complex where clauses
 * 
 * @category Structured Query Language
 * @package SqlBuilder
 * @subpackage Select
 * @author Paul Carlton
 * @link my.public.repo
 */
final class SqlBuilderSelect extends SqlBuilderAbstract
{

	protected $tables = array();
	protected $aliases = array();
	protected $fields = array();
	protected $increment = 0;
	public $range = array();
	protected $user_range = array();
	protected $current_alias = '';

	protected $have = '';

	protected $orderBy = null;
	protected $limit = null;
	protected $groupBy = null;

	protected $test = '';
	protected $test_return = true;


	/**
	 * constructor
	 */
	public function __construct( $syntax = 'mysql', $bootstrap = null ) {
		parent::__construct($bootstrap);
		$this->syntax = $syntax;
	}



	public function __invoke( $table = null, $fields = null, $where = null )
	{
		if ( ! is_string($table) ) {
			//do nothing
			//used for embedding sql instances
			$new_instance = new SqlBuilderSelect;
			return $new_instance;
			//return $this;
		}
		if ( !is_string($fields) && $fields != null ) {
			throw new SqlBuilderSelectException('Invoke requires the table columns of the from clause to be a string delimited by a comma');
		}
		if ( !is_string($where) && $where != null ) {
			throw new SqlBuilderSelectException('Invoke requires the where clause to be a string');
		}

		// meets criteria - reset the fields
		$this->rebuild();


		$table = preg_replace('/`|"/','',$table);
		if ( strpos($table,',') !== false ) {
			$tmp = explode(',', $table);
			for ( $i=0;$i<count($tmp);$i++ ) {
				$this->tables[] = $tmp[$i];
			}
		}
		else {
			$this->tables[] = $table;
		}
		if ( is_string($fields) ) {
			$fields = preg_replace('/`|"/','',$fields);
			if ( strpos($fields, ',') !== false ) {
				$tmp = explode(',', $fields);
				for( $i=0;$i<count($tmp);$i++ ) {
					$this->fields[] = $tmp[$i];
				}
			}
			else{
				$this->fields[] = $fields;
			}
		}
		else {
			$this->fields[] = '*';
		}
		if ( !is_null($where) ) {
			$this->where[] = $where;
		}
		return $this;
	}



	/**
	 * select, shorthands the from clause for easy access
	 * 
	 * @access public
	 * @param mixed $table can only except a string or null value
	 * @param mixed $fields array or string value
	 * @return object $this
	 */
	public function select( $table = null, $fields = null, $where = null )
	{
		if ( is_null($table) ) {
			return $this;
		}
		$fields_ = array();
		if (!is_string($table)) {
			throw new SqlBuilderSelectException('SqlBuilderSelect select method only requires strings or null values for table.  If you want more complexity, use the "from" method');
		}

		// flush out all old stuff
		//$this->rebuild();

		if (is_string($fields)) {
			if (strpos($fields, ',') !== false) {
				$tmp = explode(',', $fields);
				for ($i=0; $i<count($tmp); $i++) {
					$fields_[] = $tmp[$i];
				}
			}
			else{
				$fields_[] = $fields;
			}
		}
		elseif (is_array($fields)) {
			$fields_ = array();
			for ( $i=0;$i<count($fields);$i++ ) {
				$fields_[] = $fields[$i];
			}
		}
		else {
			$fields_[] = '*';
		}
		if (!is_null($where)) {
			$this->where($where);
		}
		$this->tables[$this->range[$this->increment]] = $table;
		$this->fields[$this->range[$this->increment]] = $fields_;
		$this->increment++;
		return $this;
	}


	/**
	 * From is for more complex querying
	 *
	 * @access public
	 * @param array $tables
	 * @param mixed $fields string or array
	 * @throws Exceptions\SqlBuilderSelectException
	 * @return $this (object)
	 */
	public function from( array $tables=array(), $fields = null )
	{
		if ( empty($tables) ) {
			throw new SqlBuilderSelectException('SqlBuilderSelect requires array for tables variable');
		}

		// flush out all old stuff
		//$this->rebuild();

		if ( is_array($tables) ) {
			if ( $this->isAssoc($tables) ) {
				foreach( $tables as $alias => $table ) {
					$this->aliases[] = $alias;
					$this->tables[$alias] = $table; //$this->stdClassIt($table);
					if ( is_null($fields) ) {
						$this->fields[$alias] = '*';
					}
					array_push($this->user_range, $alias);
				}


				if (is_null($fields)) {
					//dbg_array($this->tables);
					return $this;
				}
				elseif ( is_string($fields) ) {
					if ( strpos($fields,',') !== false ) {
						$fields_ = explode(',',$fields);
						for( $i=0;$i<count($fields_); $i++ ) {
							$this->fields[$this->user_range[0]][] = $fields_[$i];
						}
					}
					elseif ( $fields != '*' ) {
						$f = $fields;
						$this->fields[$this->user_range[0]][] = $f;
					} else {
						$f = '*';
						$this->fields[$this->user_range[0]][] = $f;
					}
				}
				elseif ( is_array($fields) ) {
					if ( $this->isAssoc($fields) ) {
						foreach( $fields as $alias => $field ) {
							if ( is_array($field) ) {
								for( $i=0;$i<count($field);$i++ ) {
									$this->fields[$alias][] = $field[$i];
								}
							}
							elseif (is_string($field) || is_object($field)) {
								$this->fields[$alias][] = $field;
							}
						}
					}
					elseif ( is_array($fields) ) {
						for( $i=0; $i < count($fields); $i++ ) {
							if (is_array($fields[$i])) { // && !$this->isAssoc($fields[$i]))) {
								for ($j=0; $j<count($fields[$i]);$j++) {
									$this->fields[$this->aliases[$i]][] = $fields[$i][$j];
								}
							}
							else {
								$temp_fields = array_map('trim', explode(",", $fields[$i]));
								for ($j = 0; $j < count($temp_fields); $j++) {
									$this->fields[$this->aliases[$i]][] = $temp_fields[$j];
								}
							}
						}
					}
				}
			}
			else {
				throw new SqlBuilderSelectException('"from" expects associative arrays passed for tables which includes their aliases');
			}
		}
		elseif ( is_string($tables) ) {
			return $this->select($tables,$fields);
		}
		return $this;
	}


	/**
	 * Having Expression
	 *
	 * @param $expr
	 * @param null $q
	 * @return SqlBuilderSelect
	 * @throws Exceptions\SqlBuilderSelectException
	 */
	public function having( $expr, $q=null )
	{

		if ( !is_string($expr) && !$expr instanceof SqlBuilderExpression) {
			throw new SqlBuilderSelectException('First parameter is expected to be a string');
		}
		if ( is_string($q) ) {
			//throw new Exception('having');
			$expr = preg_replace("/\?/", $q, $expr, 1);
		}
		elseif( is_array($q) ) {
			for( $i=0;$i<count($q);$i++ ){
				$expr = preg_replace("/\?/", $q, $str, 1);
			}
		}
		$this->have = $expr;
		return $this;
	}


	/**
	 * groupBy method
	 *
	 * @access public
	 * @param string $field
	 * @throws Exceptions\SqlBuilderSelectException
	 * @return object $this
	 */
	public function groupBy( $field )
	{
		if (is_array($field)) {
			for($i=0;$i<count($field);$i++){
				$this->groupBy[] = $field[$i]; //= 'GROUP BY ' . $this->formatFrom($field);
			}
		}
		elseif (is_string($field)) {
			if (strpos($field,',')!==false) {
				$tmp=explode(',', $field);
				for($i=0;$i<count($tmp);$i++){
					$this->groupBy[] = $tmp[$i];
				}
			}
			else {
				$this->groupBy[] = $field;
			}
		}
		else {
			throw new SqlBuilderSelectException('GROUP BY expects string or array');
		}
		return $this;
	}






	/**
	 * orderBy method
	 * 
	 * @access public
	 * @param string $field
	 * @param string $type
	 * @return object $this
	 */
	public function orderBy( $field, $type = 'ASC')
	{
		if (is_string($field)) {
			if (strpos($field, ',')!==false || strpos(trim($field), ' ')!==false) {
				$tmp = explode(',', $field);
				for($i=0;$i<count($tmp);$i++) {
					$a = trim($tmp[$i]);
					if (strpos($a, ' ')!==false) {
						$this->orderBy[] = explode(' ',$a);
					}
					else {
						$this->orderBy[] = array($a,$type);
					}
				}
				return $this;
			}
			else {
				$this->orderBy[] = array($field,$type); //= 'ORDER BY ' . $this->formatFrom($field) . ' ' . $type;
				return $this;
			}
		}
		elseif(is_array($field)) {
			//this will probably never be used
		}
	}




	/**
	 * limit method
	 * 
	 * @access public
	 * @param integer $num1
	 * @param integer $num2
	 * @return object $this
	 */
	public function limit( $num1,$num2=null )
	{
		$str = 'LIMIT ' . $num1;
		if ( $num2 != null ) {
			$str.= ', ' . $num2;
		}
		$this->limit = $str;
		return $this;
	}


	/**
	 * test method, when set SqlBuilder creates EXPLAIN sql and returns
	 * it's result
	 *
	 * @access public
	 * @return object $this
	 */
	public function test()
	{
		//first test the sql before executing
		$this->test = true;
		return $this;
	}


	/**
	 * Method rebuild flushes out all elements
	 * 
	 * @access public
	 * @param null
	 * @return null
	 */
	public function rebuild()
	{
		$this->tables = array();
		$this->fields = array();
		$this->where = array();
		$this->increment = 0;
		$this->range = array(); //dynamically created range if no range is created
		$this->user_range = array();
		$this->current_alias = '';

		$this->orderBy = null;
		$this->limit = null;
		$this->groupBy = null;
	}



	/**
	 * __toString()
	 * 
	 */
	public function __toString()
	{
		$sql = 'SELECT ';
		$aliases = false;
		if ( $this->isAssoc($this->tables) ) {
			$aliases = true;
		}

		if ( $aliases === true ) {
			$ff_ = array();
			foreach ($this->fields as $alias => $f_ ) {
				if ( is_array($f_) ) {
					for ( $i=0; $i < count($f_); $i++ ) {
						if ( ! is_object($f_[$i]) ) {
							$ff_[] = $alias . '.' . $this->formatColumns($f_[$i]);
						}
						else {
							$ff_[] = $f_[$i]->alias($alias);
						}
					}
				}
				else {
					if ( is_object($f_) ) {
						$ff_[] = $f_->alias($alias);
					}
					else {
						$ff_[] = $alias . '.' . $this->formatColumns($f_);
					}
				}
			}
			$sql .= join(', ', $ff_) . ' FROM ';
			$t_ = array();
			foreach ($this->tables as $alias => $table_name) {
				$t_[] = $this->tableFormat($table_name) . ' AS ' . $alias;
			}
			$sql .= join(', ', $t_);
		}
		else {
			$ff_ = array();
			foreach( $this->fields as $alias => $f_ ) {
				if ( is_array($f_) ) {
					for( $i=0; $i < count($f_); $i++ ) {
						$ff_[] = $this->tableFormat($f_[$i]);
					}
				}
				else {
					$ff_[] = $this->tableFormat($f_);
				}
			}
			$sql .= join(', ', $ff_) . ' FROM ';
			$t_ = array_values($this->tables);
			$sql .= $t_[0];
		}

		//build joins if they exists
		$join_string = $this->buildJoins();
		if ($join_string != '') {
			$sql.= $join_string;
		}


		$where = $this->buildWhere();
		if ( $where != null ) {
			$sql = $sql . ' WHERE ' . $where;
		}

		if ( $this->groupBy != null ) {
			$sql.= ' GROUP BY ';
			$group = array();
			for ( $i=0;$i<count($this->groupBy);$i++) {
				if (is_numeric($this->groupBy[$i])) {
					$group[] = $this->groupBy[$i];
				} else {
					$group[] = $this->formatColumns($this->groupBy[$i]);
				}
			}
			$sql.= join(', ',$group);
		}


		if ( $this->have != null ) {
			$sql.=' HAVING '. $this->have;
		}


		if ( $this->orderBy != null ) {
			$sql.= ' ORDER BY ';
			$orderBy=array();
			for($i=0; $i<count($this->orderBy); $i++){
				$orderBy[] = $this->formatColumns($this->orderBy[$i][0]) . ' ' . $this->orderBy[$i][1];
			}
			$sql.= join(', ', $orderBy);
		}
		if ( $this->limit != null ) {
			$sql.= ' '.$this->limit;
		}
		return $sql;
	}



	/**
	 * explode method prints a formatted sql to the screen
	 * 
	 * @access public
	 * @param null
	 * @return object $this
	 */
	public function explode() {
		//explode the sql;
		//invoke __toString()
		$sql_string = $this . '';
		//first check for from
		$tmp = explode('FROM', $sql_string);
		if (count($tmp)==1) {
			print $sql_string;
			return $this;
		}
		$str = array_shift($tmp);
		$str.= "\nFROM ".join("\n\tFROM ",$tmp);
		$str = preg_replace("/((natural|cross|inner|outer|left|right)\sjoin)/i","\n$1",$str);
		$tmp = explode('WHERE', $str);
		if (count($tmp)==1) {
			print '<pre>';print_r($str);print'</pre>';
			return $this;
		}
		$str = array_shift($tmp);
		$str.= "\nWHERE ".join("\nWHERE", $tmp);
		print '<pre>'.$str.'</pre>';
		return $this;
	}




	/**
	 * destroy, must be created to ensure that the variables housed
	 * in the abstract are also emptied to ensure clean sqls
	 * 
	 * @access public
	 * @param null
	 * @return null
	 */
	public function __destruct()
	{
		$this->joins = array();
	}


}

