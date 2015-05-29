<?php


namespace SqlBuilder\Statements;


class SqlBuilderUpdate extends SqlBuilderAbstract {


	private $_table_ = null;
	private $_values_= null;
	private $_where_ = null;

	public function reset()
	{
		$this->_table_ = null;
		$this->_values_ = null;
	}

	public function __construct($dbApi){
		$this->_dbApi_ = $dbApi;
	}

	public function table($table = null)
	{
		if (is_null($table) && !is_string($table)) {
			throw new SqlBuilderInsertException('Table must be a null value.');
		}

		$this->_table_ = $table;
		return $this;
	}


	public function values($values = null)
	{
		if (is_null($values) && !is_array($values)) {
			throw new SqlBuilderUpdateException('Values must be an associative array ie: "field"=>"value"');
		}

		// if an associative array is given, assign to an array
		// because by default this function allows multiple inserts
		if ((bool)count(array_filter(array_keys($values), 'is_string'))) {
			$values = array($values);
		}
		$this->_values_ = $values;
		return $this;
	}

	/**
	 * Where statement
	 *
	 * @param int $where
	 * @param null $val
	 * @return $this
	 */
	public function where($where = 1, $val = null)
	{
		if (is_null($where)) {
			$where = 1;
		}

		if (preg_match("/\?/", $where)) {
			if (is_null($val)) {
				$where = preg_replace("/\?/", 'NULL', $where);
			} elseif (is_string($val)) {
				$where = preg_replace("/\?/", $this->_dbApi_->escape($val), $where, 1);
			} elseif (is_array($val)) {
				foreach ($val as $where_str) {
					$where = preg_replace("/\?/", $this->_dbApi_->escape($where_str), $where, 1);
				}
			}
		}

		$this->_where_[] = $where;
		return $this;
	}


	public function __toString()
	{
		$str = 'UPDATE '.$this->_table_."\n";
		$str.= "\tSET \n";

		$updates = array();
		foreach ($this->_values_[0] as $key => $value) {
			if (is_string($value)) {
				$value = "'".$this->_dbApi_->escape($value)."'";
			} elseif (is_null($value)) {
				$value = "NULL";
			} else {
				// force float val
				$value = (float)$value;
			}
			$updates[] = "`$key` = ".$value;
		}
		$str.= join(', ', $updates);

		// build the where statement
		if (empty($this->_where_)) {
			$where = array(1);
		} else {
			$where = $this->_where_;
		}
		$str.= "\nWHERE \n";
		$str.= '('.join(") AND \n\t(", $where).')'."\n";

		return $str;
	}

}



class SqlBuilderUpdateException extends \Exception {}