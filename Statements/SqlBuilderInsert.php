<?php

namespace SqlBuilder\Statements;

class SqlBuilderInsert extends SqlBuilderAbstract {


	private $_table_ = null;
	private $_values_= null;


	public function reset()
	{
		$this->_table_ = null;
		$this->_values_ = null;
	}

	public function __construct(){}



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
			throw new SqlBuilderInsertException('Values must be an associative array ie: "field"=>"value"');
		}

		// if an associative array is given, assign to an array
		// because by default this function allows multiple inserts
		if ((bool)count(array_filter(array_keys($values), 'is_string'))) {
			$values = array($values);
		}
		$this->_values_ = $values;
		return $this;
	}



	public function __toString()
	{
		$str = 'INSERT INTO '.$this->_table_."\n";
		$current = current($this->_values_);
		$fields = array_keys($current);
		$str.= "\t(".join(', ', $fields).")\n";
		$str.= "\nVALUES\n";
		$vals_ = array();
		foreach ($this->_values_ as $v) {
			$vals = array_values($v);
			array_walk($vals, function(&$value){
				if (is_string($value)) {
					$value = "'".mysql_real_escape_string($value)."'";
				} elseif (is_null($value)) {
					$value = "NULL";
				} else {
					// force float val
					$value = (float)$value;
				}
			});
			$vals_[] = join(', ', $vals);
		}
		$str.="\t(".join("),\n\t(", $vals_).")";
		return $str;
	}


}


class SqlBuilderInsertException extends \Exception {}