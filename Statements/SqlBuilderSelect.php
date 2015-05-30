<?php

namespace SqlBuilder\Statements;


class SqlBuilderSelect extends SqlBuilderAbstract {

	private $range = array();
	private $_tables_ = array();
	private $_joins_ = array();
	private $_fields_ = array();
	private $_where_ = array();
	private $_having_ = '';
	private $_order_ = '';
	private $_group_ = '';
	private $_limit_ = array();

	/**
	 * Reset the object
	 *
	 */
	public function resetSelect()
	{
		$this->_tables_ = array();
		$this->_fields_ = array();
		$this->_where_ = array();
	}


	public function __construct($dbApi)
	{
		$this->range = range('a', 'z');
		$this->config = array(
			'no_parenth' => false,
		);
		$this->_dbApi_ = $dbApi;
	}

	/**
	 * Table data
	 *
	 * @param null $table
	 * @return $this
	 * @throws SqlBuilderSelectException
	 */
	public function table($table = null)
	{
		$table_args = array();
		// if a string is passed
		if (is_string($table)) {
			// if there are multiple tables in the string
			if (strpos($table, ',') !== false) {
				$table_args = explode(",", $table);
				// if there are aliases to these tables, split them and reformat them
				$new_args = array();
				foreach ($table_args as $i => $args) {
					// if there is an alias, assign it
					if (preg_match("/ as /i", $args)) {
						list($field_name, $table_alias) = preg_split("/\bas\b/i", $args);
						$new_args[trim($table_alias)] = trim($field_name);
					} else {
						// otherwise assign the range by $i
						$new_args[$this->range[$i]] = $args;
					}
				}
				// reassign table_args
				$table_args = $new_args;
			} else {
				// assign the first range as alias
				if (preg_match("/ as /i", $table)) {
					list($table_name, $table_alias) = preg_split("/\bas\b/i", $table);
					$this->range[0] = trim($table_alias);
				} else {
					$table_name = $table;
				}
				$table_args[$this->range[0]] = trim($table_name);
			}

		} elseif (is_array($table)) {

			$is_int = false;
			foreach ($table as $i => $table_name) {
				if (is_int($i) && !$is_int) $is_int = true;
				if ($is_int) {
					$table_args[$this->range[$i]] = $table_name;
				} else {
					$table_args[$i] = $table_name;
				}
			}
		} else {
			throw new SqlBuilderSelectException('Table passed must be string or array.  Invalid type passed.');
		}

		array_walk($table_args, function(&$value){
			if (is_array($value)) {
				$tmp = $value[key($value)];
				$value[trim(key($value))] = trim($tmp);
			} else {
				$value = trim($value);
			}
		});

		$this->_tables_ = $table_args;
		return $this;
	}



	public function tables($tables = null)
	{
		$this->table($tables);
		return $this;
	}


	/**
	 * Fields are always in this data structure
	 *
	 * a => array(field1, field2)
	 * b => array(field3, field4)
	 *
	 * @param null $fields
	 * @throws SqlBuilderSelectException
	 * @return $this
	 */
	public function fields($fields = null)
	{
		$field_args = array();
		if (is_string($fields)) {
			// if there are multiple strings with ,
			if (strpos($fields, ',') !== false || strpos($fields, '.') !== false) {
				// wipe out postgres or mysql quotes
				$fields = preg_replace('/(`|")/', '', $fields);
				$fields = explode(",", $fields);
				foreach ($fields as $field) {
					// if there is an alias
					$field = trim($field);
					if (preg_match("/\w+\.[\w\*]+/", $field)) {
						list($alias, $field) = explode(".", $field);
						$field_args[$alias][] = $field;
					} else {
						$field_args[$this->range[0]][] = $field;
					}
				}
			} else {
				$field_args[$this->range[0]][] = $fields;
			}
		} elseif (is_array($fields)) {
			$field_args = $fields; // already passed as the expected array
		} elseif ($fields instanceof SqlBuilderExpression) {
			$field_args = array($fields->__toString());
		} else {
			throw new SqlBuilderSelectException('Unexpected data passed through fields.');
		}

		$this->_fields_ = $field_args;
		return $this;
	}


	/**
	 * Where statement
	 *
	 * @param int $where
	 * @param null $val
	 * @param bool $return_val
	 * @return $this
	 */
	public function where($where = 1, $val = null, $return_val = false)
	{
		if (is_null($where)) {
			$where = 1;
		}

		if (preg_match("/\?/", $where)) {
			if (is_null($val)) {
				$where = preg_replace("/\?/", 'NULL', $where, 1);
			} elseif (is_string($val)) {
				$where = preg_replace("/\?/", $this->_dbApi_->escape($val), $where, 1);
			} elseif (is_int($val)) {
				$where = preg_replace("/\?/", $val, $where, 1);
			} elseif (is_array($val)) {
				foreach ($val as $where_str) {
					$where = $this->where($where, $where_str, true);
				}
			}
		}
		if ($return_val === true) {
			return $where;
		}
		$this->_where_[] = $where;
		return $this;
	}


	public function having($have_statement = null)
	{
		if (!is_string($have_statement)) {
			throw new SqlBuilderSelectException('Having statement expects string value.');
		}

		$this->_having_ = $have_statement;
		return $this;
	}



	public function orderBy($order_by_string = '')
	{
		$this->_order_ = $order_by_string;
		return $this;
	}



	public function groupBy($group_by_string = '')
	{
		$this->_group_ = $group_by_string;
		return $this;
	}



	public function limit($num1, $num2 = null)
	{
		$this->_limit_ = $num1;
		if (!is_null($num2)) {
			$this->_limit_.=','.$num2;
		}
		return $this;
	}


	public function join($table = '', $on = '', $type = 'inner')
	{
		// no validation at this point, just put the placement there
		// will update later
		// if there is an alias, assign it
		if (preg_match("/ as /i", $table)) {
			list($table_name, $table_alias) = preg_split("/ as /i", $table);
			$table = '`'.$table_name.'` AS '.$table_alias;
		}

		$tmp = explode("=", $on);
		$v = array();
		foreach ($tmp as $a) {
			$a = trim($a);
			if (strpos($a, '.') !== false) {
				$t = explode(".", $a);
				array_walk($t, function(&$value){
					$value = '`'.trim($value).'`';
				});
				$v[] = $t[0].'.'.$t[1];
			}
		}
		if (!empty($v)) {
			$on = $v[0].' = '.$v[1];
		}
		$str = strtoupper($type).' JOIN '.$table.' ON '.$on;
		$this->_joins_[] = $str;
		return $this;
	}



	public function innerJoin($table = '', $on = '')
	{
		$this->join($table, $on);
		return $this;
	}



	public function outerJoin($table = '', $on = '')
	{
		$this->join($table, $on, 'outer');
		return $this;
	}



	public function rightJoin($table = '', $on = '')
	{
		$this->join($table, $on, 'right');
		return $this;
	}



	public function leftJoin($table = '', $on = '')
	{
		$this->join($table, $on, 'left');
		return $this;
	}



	public function naturalJoin($table = '', $on = '')
	{
		$this->join($table, $on, 'natural');
		return $this;
	}



	/**
	 * @return string
	 */
	public function __toString()
	{

		if (empty($this->_fields_)) {
			$this->fields('*');
		}

		// build the select fields
		//-- TODO not strings in the fields variable with commas and how that parses
		$str = "SELECT \n";
		$fields_sql = array();
		foreach ($this->_fields_ as $alias => $fields) {
			// if aliases are being used, expression can be passed
			// or aliases turned off
			if (is_array($fields)) {
				foreach ($fields as $field) {
					if ($field != '*' && !preg_match("/\bas\b/i", $field) && !preg_match("/\w+\(/", $field)) {
						$field = '`'.$field.'`';
					}
					if (!preg_match("/\w+\(/", $field)) {
						$fields_sql[] = "\t`$alias`.$field";
					} else {
						$fields_sql[] = "\t$field";
					}
				}
			} else {
				$fields_sql[] = "\t$fields";
			}
		}
		$str.=join(",\n", $fields_sql);

		// build the from statement
		$str.= "\nFROM \n";
		$tables = array();
		foreach ($this->_tables_ as $alias => $table) {
			$tables[] ="\t`$table` AS $alias";
		}
		$str.= join(",\n", $tables)."\n";


		if (!empty($this->_joins_)) {
			foreach ($this->_joins_ as $join) {
				$str.="\n".$join."\n";
			}
		}

		// build the where statement
		if (empty($this->_where_)) {
			$where = array(1);
		} else {
			$where = $this->_where_;
		}
		$str.= "\nWHERE \n";
		if ($this->config['no_parenth'] === true) {
			$str.= join(" AND \n\t", $where)."\n";
		} else {
			$str.= '('.join(") AND \n\t(", $where).')'."\n";
		}

		if ($this->_group_ != '') {
			$str.="\nGROUP BY ".$this->_group_."\n";
		}

		if ($this->_having_ != '') {
			$str.="\nHAVING ".$this->_having_."\n";
		}

		if ($this->_order_ != '') {
			$str.="\nORDER BY ".$this->_order_."\n";
		}

		if (!empty($this->_limit_)) {
			$str.="\nLIMIT ".$this->_limit_."\n";
		}
		return $str;
	}


}



class SqlBuilderSelectException extends \Exception {}