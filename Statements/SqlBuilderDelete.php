<?php
namespace SqlBuilder\Statements;


class SqlBuilderDelete extends SqlBuilderAbstract {

	protected $config = array();

	private $_table_ = null;
	private $_values_= null;
	private $_where_ = null;


	/**
	 * @param $dbApi
	 */
	public function __construct($dbApi)
	{
		$this->_dbApi_ = $dbApi;
	}


	/**
	 * RESET()
	 */
	public function reset()
	{
		$this->_table_ = null;
		$this->_values_ = null;
	}

	public function table($table = null)
	{
		if (is_null($table) && !is_string($table)) {
			throw new SqlBuilderInsertException('Table must be a null value.');
		}

		$this->_table_ = $table;
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
				dbg_array($where);
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
		$str = 'DELETE FROM '.$this->_table_."\n";

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