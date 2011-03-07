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
	protected $where = array();
	protected $joinWhere = array();
	protected $increment = 0;
	protected $range = array();
	protected $user_range = array();
	protected $current_alias = '';
	protected $joinThese = false;
	protected $joins = array();
	protected $joinWhereType = '';

	protected $have = '';

	protected $orderBy = null;
	protected $limit = null;
	protected $groupBy = null;

	protected $test = '';
	protected $test_return = true;


	/**
	 * constructor
	 */
	public function __construct( $bootstrap = null ) {
		parent::__construct($bootstrap);
	}



	/**
	 * This essentially wraps the classes together with the magic method __call
	 * 
	 * @access public
	 * @param (string) $method
	 * @param (array) $params
	 * @return (object|string|array) $this->SqlClass
	 */
	public function __call( $method, $params=array() )
	{
		if ( preg_match("/^\w+join$/", $method) ) {
			array_unshift($params, $method);
			return call_user_func_array(array($this, 'allJoins'),$params);
		}
		elseif ( method_exists($this->SqlClass, $method) ) {
			return call_user_func_array(array($this, $method), $params);
		}
		else {
			throw new SqlSelectException('Method does not exist for SqlBuilder');
		}
	}



	/**
	 * __invoke  select is the only one that supports this method
	 */
	public function __invoke( $table = null, $fields = null, $where = null )
	{
		if ( ! is_string($table) ) {
			//do nothing
			return $this;
		}
		if ( !is_string($fields) && $fields != null ) {
			throw new SqlBuilderSelectException('Invoke requires the table columns of the from clause to be a string delimited by a comma');
		}
		if ( !is_string($where) && $where != null ) {
			throw new SqlBuilderSelectException('Invoke requires the where clause to be a string');
		}
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
		if ( is_string($where) ) {
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
		if ( $table == null ) {
			return $this;
		}
		$fields_ = array();
		if ( ! is_string($table) && !$table instanceof SqlBuilderSelect) {
			throw new SqlBuilderSelectException('SqlBuilderSelect select method only requires strings or null values for table.  If you want more complexity, use the "from" method');
		}
		if ( is_string($fields) ) {
			$fields = preg_replace('/`|"/','',$fields);
			if ( strpos($fields, ',') !== false ) {
				$tmp = explode(',', $fields);
				for( $i=0;$i<count($tmp);$i++ ) {
					$fields_[] = $tmp[$i];
				}
			}
			else{
				$fields_[] = $fields;
			}
		}
		elseif ( is_array( $fields ) ) {
			$fields_ = array();
			for ( $i=0;$i<count($fields);$i++ ) {
				$fields_[] = $fields[$i];
			}
		}
		else {
			$fields_[] = '*';
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
	 * @return object $this
	 */
	public function from( array $tables=array(), $fields = null )
	{
		if ( empty($tables) ) {
			throw new SqlBuilderSelectException('SqlBuilderSelect requires array for tables variable');
		}
		if ( is_array($tables) ) {
			if ( $this->isAssoc($tables) ) {
				foreach( $tables as $alias => $table ) {
					$this->aliases[] = $alias;
					$this->tables[$alias] = $table;
					if ( is_null($fields) ) {
						$this->fields[$alias] = '*';
					}
					array_push($this->user_range, $alias);
				}


				if ( is_null($fields) ) {
					return $this;
				}
				elseif ( is_string($fields) ) {
					if ( strpos($fields,',') !== false ) {
						$fields_ = split(',',$fields);
						for( $i=0;$i<count($fields_); $i++ ) {
							$this->fields[$this->user_range[0]] = $fields_[$i];
						}
					}
					elseif ( $fields != '*' ) { $f = $fields; }
					else { $f = '*'; }
					$this->fields[$this->user_range[0]] = $f;
				}
				elseif ( is_array($fields) ) {
					if ( $this->isAssoc($fields) ) {
						foreach( $fields as $alias => $field ) {
							if ( is_array($field) ) {
								for( $i=0;$i<count($field);$i++ ) {
									$this->fields[$alias][] = $field[$i];
								}
							}
							elseif ( is_string($field) ) {
								$this->fields[$alias][] = $field;
							}
						}
					}
					elseif ( is_array($fields) ) {
						for( $i=0; $i < count($fields); $i++ ) {
							$this->fields[$this->aliases[0]][] = $fields[$i];
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
	 * joinThese is for the where clause by creating
	 * adjacent where's to join together in one larger
	 * where clause... ex:
	 * 
	 * ( ( ( (`column1` = 'data1') AND (`column2` = 'data2' ) ) OR (`column3` = 'data3' ) ) AND
	 * ( ( (`column4` = 'data4') AND (`column5` = 'data5' ) ) OR (`column6` = 'data6' ) ) )
	 * 
	 * @access public
	 * @param string $with
	 * @return object $this
	 */
	public function joinThese( $with = 'and' )
	{
		if ( $with != 'and' && $with != 'or' ) {
			throw new SqlBuilderSelectException('joinThese requires a where with statement (and|or)');
		}
		$this->joinThese = true;
		$this->joinWhereType = $with;
		return $this;
	}



	/**
	 * joinTheseFinish will concatenate joinThese
	 * 
	 * @access public
	 * @param string $type
	 * @return object $this
	 */
	public function joinTheseFinish( $type = 'and' )
	{
		if ( $this->joinThese === false ) return $this;
		$this->joinThese = false;
		$str = $this->buildWhere( $this->joinWhere );
		if ( $type == 'or' ) {
			$str = '||('.$str.')';
		}
		array_push($this->where,$str);
		return $this;
	}



	/**
	 * joinWhere joins the current array of wheres
	 * 
	 * @access public
	 * @param null
	 * @return object $this
	 */
	public function joinWhere()
	{
		if ( $this->joinThese === true ) {
			$wheres = $this->joinWhere;
			$str = $this->buildWhere($wheres);
			$this->joinWhere = array();
			$this->joinWhere[] = $str;
			return $this;
		}
		$str = $this->buildWhere();
		$this->where = array();
		$this->where[] = $str;
		return $this;
	}



	/**
	 * orWhere, creates an orWhere clause instead of an and
	 * 
	 * @access public
	 * @param string $str
	 * @param mixed $q string array
	 * @return object $this
	 */
	public function orWhere( $str=null, $q=null ) {
		$this->where($str,$q);
		if ( $this->joinThese ) {
			$current_where = array_pop($this->joinWhere);
			$current_where = '||'.$current_where;
			array_push($this->joinWhere,$current_where);
			return $this;
		}
		$current_where = array_pop($this->where);
		$current_where = '||'.$current_where;
		array_push($this->where,$current_where);
		return $this;
	}



	/**
	 * where method, creates the where clause
	 * 
	 * @access public
	 * @param string $str
	 * @param mixed $q string array
	 * @return object $this
	 */
	public function where( $str=null, $q=null )
	{
		if ( is_null($str) ) {
			$str = '1 = 1';
		}
		if ( is_null($q) ) {
			//do nothing
		}
		elseif ( !is_array($q) ) {
			if ( is_string($q)){
				//xss eventually goes here
				$q = "'" . $this->db->escape($q) . "'";
			}
			$str = preg_replace('/\?/',$q,$str,1);
		}
		elseif ( is_array($q) ) {
			for ( $i=0; $i < count($q); $i++ ) {
				if ( !is_numeric($q[$i]) ) {
					$q[$i] = "'" . $this->db->escape($q[$i]) . "'";
				}
				$str = preg_replace('/\?/',$q[$i],$str,1);
			}
		}
		else {
			throw new SqlBuilderSelectException('Invalid inputs for where statement, requires null or string values');
		}
		if ( $this->joinThese === true ) {
			array_push($this->joinWhere, $str);
		}
		else {
			array_push($this->where,$str);
		}
		return $this;
	}



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
	 * @param numeric $num1
	 * @param numeric $num2
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
	 * @param string $field
	 * @return object $this
	 */
	public function test()
	{
		//first test the sql before executing
		$this->test = true;
		return $this;
	}


	/**
	 * --deprecated due to Sql Wrapper
	 * method rebuild flushes out all elements 
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
		if ( is_assoc($this->tables) ) {
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
		$join_string = '';
		if ( !empty($this->joins) ) {
			for ( $i =0; $i < count($this->joins); $i++ ) {
				$join = $this->joins[$i];
				if ( $join[0] == 'join' ) {
					$join_string.= ' JOIN '.$join[1].' ON '.$join[2];
				}
				else {
					$join_string.= ' ' . strtoupper($join[0]). ' JOIN ' . $join[1] . ' ON ' . $join[2];
				}
			}
		}
		if ( $join_string != '' ) {
			$sql .= $join_string;
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
		//now explode by SELECT;
		$sql_ = explode('SELECT ',$sql_string);
		$sql_string = array_pop($sql_); //join("\n", $sql_);

		$sql_ = explode(' FROM ', $sql_string);
		if ( strpos($sql_[0],',') !== false ) {
			$selects = explode(', ', $sql_[0]);
			$sql_[0] = "\t" . join(",\n\t",$selects);
		}
		else {
			$sql_[0] = "\t" . $sql_[0];
		}
		$sql_string = 'SELECT ' . "\n" . $sql_[0] . "\n".'FROM ' . "\n";

		$sql_ = explode(' WHERE ', $sql_[1]);
		if ( strpos($sql_[0],',') !== false ) {
			$froms = explode(',', $sql_[0]);
			$sql_[0] = "\t" . join(",\n\t", $froms);
		}
		else {
			$sql_[0] = "\t" . $sql_[0];
		}
		$sql_string .= $sql_[0] . "\n".'WHERE ' . "\n" . $sql_[1];

		$sql_string = preg_replace("/(inner join|join|left join|right join)/i","\n$1",$sql_string);

		print '<pre>' . $sql_string . '</pre>';
		return $this;
	}




	/**
	 * buildWhere function builds the where for __toString() and joinThese function
	 * 
	 * @access protected
	 * @param string $replace
	 * @return string $where
	 */
	protected function buildWhere( $replace = null )
	{
		if ( empty($this->where) && $replace == null) {
			return null;
		}
		//this is for joining where statements together
		elseif (is_array($replace)) {
			$wheres = $replace;
		}
		else {
			$wheres = $this->where;
		}
		$where = '';
		for ($i=0; $i<count($wheres); $i++) {
			if ( substr($wheres[$i],0,2) == '||' ) {
				$wheres[$i] = substr($wheres[$i],2);
				$or = true;
			} else { $or = false; }
			if ( $where != '' && $or == true ) {
				$where .= ' OR ';
			} elseif ( $where != '' && $or == false ) {
				$where .= ' AND ';
			}
			$where .= '(' . $wheres[$i] . ')';
		}
		return $where;
	}



	/**
	 * will close the mysql connection on destroy if
	 * a mysql connection was made by the class
	 */
	function __destruct()
	{
		if ( is_resource($this->dlink) && $this->database_type === 'mysql' ) {
			mysql_close($this->dlink);
		}
	}
}

