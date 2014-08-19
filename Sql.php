<?php


namespace SqlBuilder;

class Sql {


	/**
	 * @var null|Database\DatabaseController
	 */
	private $dbApi = null;
	/**
	 * @var Statements\SqlBuilderSelect | Statements\SqlBuilderUpdate | Statements\SqlBuilderInsert | Statements\SqlBuilderDelete | Statements\SqlBuilderTruncate
	 */
	private $sqlObj = null;
	private $config = array(
		'no_parenth' => false
	);


	/**
	 * @param null $type
	 * @param null $dsn
	 * @param string $charset
	 */
	public function __construct($type = null, $dsn = null, $charset = 'latin1')
	{
		$this->dbApi = new Database\DatabaseController();
		if (!is_null($type) && !is_null($dsn)) {
			$this->dbApi->setup($type, $dsn);
			// default charset is latin1 for mysql
			$this->setCharset($charset);
		}
	}


	/**
	 * The configuration method for assigning control variables
	 *
	 * @param $config_name
	 * @param $config_value
	 * @throws SqlException
	 */
	public function config($config_name, $config_value)
	{
		if (!in_array($config_name, $this->config)) {
			throw new SqlException('Config parameter uknown: '.$config_name);
		} else {
			$this->config[$config_name] = $config_value;
		}
	}

	/**
	 * @param $type
	 * @param $dsn
	 */
	public function setup($type, $dsn)
	{
		return;
	}


	/**
	 * @param string $sql
	 * @param array $options
	 * @return mixed
	 */
	public function raw($sql = '', $options = array())
	{
		$_sql = trim($sql);
		if (preg_match("/^select/i", $_sql)) {
			return $this->dbApi->query($sql, null, $options);
		} elseif (preg_match("/^insert/", $_sql)) {
			return $this->dbApi->insert($sql, null, $options);
		} elseif (preg_match("/^update/", $_sql)) {
			return $this->dbApi->update($sql, null, $options);
		} elseif (preg_match("/^delete/", $_sql)) {
			return $this->dbApi->delete($_sql, null, $options);
		} else {
			$return_data = $this->dbApi->exec($sql, null, $options);
			return $return_data;
		}
	}


	public function setCharset($charset = 'utf8')
	{
		return $this->dbApi->exec('SET CHARACTER SET '.$charset);
	}

	/**
	 * The invoke magic method is shorthand for raw queries
	 *
	 * @param null $raw_sql
	 * @return mixed
	 */
	public function __invoke($raw_sql = null)
	{
		if (is_null($raw_sql)) {
			return $this;
		}

		$this->raw($raw_sql);
		return $this;
	}


	/**
	 * @param string $table
	 * @param string $fields
	 * @param string $where
	 * @param null $where_vars
	 * @return $this
	 */
	public function select($table = '', $fields = '', $where = '', $where_vars = null)
	{
		// unset the object
		$this->_unsetObj();
		$this->sqlObj = new Statements\SqlBuilderSelect();

		// if the table is set, set to the object
		if ($table != '') {
			$this->sqlObj->table($table);
		}

		// if table is set and fields not set
		if ($table != '' && is_string($fields) && $fields == '') {
			$this->sqlObj->fields('*');
		}

		// if table is set and fieds is set
		if ($table != '' && $fields != '') {
			$this->sqlObj->fields($fields);
		}

		// if where is set
		if ($where != '') {
			// we have an embedded where statement, do not use parenthesis
			// let the programmer use the parenthesis
			$this->config['no_parenth'] = true;
			$this->sqlObj->where($where, $where_vars);
		}

		// inject the latest configuration
		$this->sqlObj->injectConfig($this->config);
		return $this;
	}

	/**
	 * COUNT(*) quick command
	 *
	 * @param string $table
	 * @param string $where
	 * @param array $where_arguments
	 * @return mixed
	 */
	public function count($table = '', $where = '', $where_arguments = array())
	{
		$this->select($table, 'COUNT(*) AS counter', $where, $where_arguments);
		$rows = $this->query();
		return $rows[0]['counter'];
	}


	public function join($table = '', $on = '', $type = 'inner')
	{
		$this->sqlObj->join($table, $on, $type);
		return $this;
	}

	public function innerJoin($table = '', $on = '')
	{
		$this->sqlObj->innerJoin($table, $on);
		return $this;
	}



	public function outerJoin($table = '', $on = '')
	{
		$this->sqlObj->outerJoin($table, $on);
		return $this;
	}



	public function rightJoin($table = '', $on = '')
	{
		$this->sqlObj->rightJoin($table, $on);
		return $this;
	}



	public function leftJoin($table = '', $on = '')
	{
		$this->sqlObj->leftJoin($table, $on);
		return $this;
	}



	public function naturalJoin($table = '', $on = '')
	{
		$this->sqlObj->naturalJoin($table, $on, 'natural');
		return $this;
	}

	/**
	 * @param null $table
	 * @param null $values
	 * @return $this
	 */
	public function insert($table = null, $values = null)
	{
		$this->_unsetObj();
		if (is_null($table) || is_null($values)) {
			return $this;
		} else {
			$this->sqlObj = new Statements\SqlBuilderInsert();
			$this->sqlObj->table($table)->values($values);
			return $this;
		}
	}


	/**
	 * @param null $table
	 * @param null $values
	 * @param null $where
	 * @return $this
	 */
	public function update($table = null, $values = null, $where = null)
	{
		$this->_unsetObj();
		if (is_null($table) || is_null($values)) {
			return $this;
		} else {
			$this->sqlObj = new Statements\SqlBuilderUpdate();
			$this->sqlObj->table($table)->values($values)->where($where);
			return $this;
		}
	}


	/**
	 *
	 *
	 * @param null $table
	 * @param null $where
	 * @return $this
	 */
	public function delete($table = null, $where = null)
	{
		$this->_unsetObj();
		if (is_null($table)) {
			return $this;
		}

		$this->sqlObj = new Statements\SqlBuilderDelete();
		if (is_null($where)) {
			$this->sqlObj->table($table);
		}
		return $this;
	}




	/////////////////////////////////////////////////////
	//
	// WRAPPER sqlObj Methods
	//
	/////////////////////////////////////////////////////

	/**
	 * Set the table
	 * Wrapper function to the sql object
	 *
	 * @param string $table
	 * @return $this
	 */
	public function table($table = '')
	{
		if ($table != '') {
			$this->sqlObj->table($table);
		}
		return $this;
	}


	/**
	 * Set the fields
	 * Wrapper function to the sql object
	 *
	 * @param string $fields
	 * @return $this
	 */
	public function fields($fields = '')
	{
		if ($fields != '') {
			$this->sqlObj->fields($fields);
		}
		return $this;
	}

	public function values($values = null)
	{
		if (is_null($values)) {
			return $this;
		}
		$this->sqlObj->values($values);
		return $this;
	}
	/**
	 * Creates the where statement
	 *
	 * @param int $where
	 * @param null $val
	 * @return $this
	 */
	public function where($where = 1, $val = null)
	{
		$this->sqlObj->where($where, $val);
		return $this;
	}


	/**
	 *
	 */
	private function _unsetObj()
	{
		unset($this->sqlObj);
		$this->sqlObj = null;
	}


	/**
	 * Wrapper magic method for the sqlObj class
	 *
	 * @param $name
	 * @param $arguments
	 * @return $this
	 * @throws SqlException
	 */
	public function __call($name, $arguments)
	{
		if (method_exists($this->sqlObj,$name)) {
			call_user_func_array(array($this->sqlObj, $name), $arguments);
		} else {
			throw new SqlException('Method does not exist for object.');
		}
		return $this;
	}


	/**
	 * @return bool|array
	 */
	public function query()
	{
		if (is_null($this->sqlObj)) {
			return false;
		}

		$sql = $this->sqlObj->__toString();
		$return_val = $this->dbApi->query($sql);
		return $return_val;
	}


	/**
	 * Execute a non select query
	 *
	 * @return bool
	 */
	public function exec()
	{
		if (is_null($this->sqlObj)) {
			return false;
		}

		$sql = $this->sqlObj->__toString();
		$return_val = $this->dbApi->exec($sql);
		return $return_val;
	}


	/**
	 * @return string
	 */
	public function __toString()
	{
		if (is_object($this->sqlObj)) {
			return $this->sqlObj->__toString();
		} else {
			return 'Nothing set.';
		}
	}
}



class SqlException extends \Exception {}