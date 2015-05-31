<?php
/**
 * SqlBuilder
 * SQL API and Syntax Wrapper for MySQL and PostgreSQL
 *
 * Copyright (C) 2015  Paul Carlton
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
 * @author      Paul Carlton
 * @category    Database
 * @package     SqlBuilder
 * @license     GNU license
 * @version     1.0
 * @link        https://github.com/DataHerder/Sql-Build
 * @since       File available since 2011
 */


namespace SqlBuilder;

use SqlBuilder\Database\DatabaseController;

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
	 * Constructs using a number of different argument types
	 *
	 * __construct('mysqli','host=localhost dbname=[dbname] user=root password=[password]', 'utf8')
	 * __construct('mysqli', array((
	 *   'host' => 'localhost',
	 *   'database' => '[database]',
	 *   'user' => 'user',
	 *   'password' => '[password]',
	 * ))
	 *
	 * @throws Database\DatabaseControllerException
	 * @throws SqlException
	 */
	public function __construct()
	{
		$this->dbApi = new DatabaseController();
		$num = func_num_args();
		if ($num < 2) {
			throw new SqlException('SqlBuilder\Sql expects database type and dsn.');
		}
		if ($num == 2 || $num == 3) {
			$arg1 = func_get_arg(0);
			$arg2 = func_get_arg(1);
			if (is_string($arg1) && (is_string($arg2) || is_array($arg2))) {
				$this->dbApi->setup($arg1, $arg2);
				if (is_array($arg2) && isSet($arg2['charset'])) {
					$this->setCharset($arg2['charset']);
				}
			} else {
				throw new SqlException('Expected string or array value for dsn.  Please see documentation.');
			}
			if ($num == 3) {
				$this->setCharset(func_get_arg(2));
			}
		} elseif ($num > 5) {
			// setup the dsn for them
			$dsn = 'host='.func_get_arg(1).' dbname='.func_get_arg(4).' user='.func_get_arg(2).' password='.func_get_arg(3);
			$this->dbApi->setup(func_get_arg(0), $dsn);
			if ($num >= 6) {
				$this->setCharset(func_get_arg(5));
			}
		} else {
			throw new SqlException('Unknown error occurred.  Please check your connections.');
		}
	}

	/**
	 * Returns an instance of the class, syntactic sugar for IDE
	 *
	 * @param string $database_type
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param string $db
	 * @param string $charset
	 * @return mixed
	 */
	public static function dsn($database_type = 'mysqli', $host = '', $user = '', $password = '', $db = '', $charset = 'utf8')
	{
		$class_name = __CLASS__;
		return new $class_name(
			$database_type, $host, $user, $password, $db, $charset
		);
	}

	/**
	 * Example use:
	 *
	 * __construct('mysql','host=localhost dbname=[dbname] user=root password=[password]', 'utf8')
	 *
	 * @deprecated
	 * @param null $type
	 * @param null $dsn
	 * @param string $charset
	 */
	public function ___construct($type = null, $dsn = null, $charset = 'latin1')
	{
		$this->dbApi = new DatabaseController();
		if (!is_null($type) && !is_null($dsn)) {
			$this->dbApi->setup($type, $dsn);
			// default charset is latin1 for mysql
			$this->setCharset($charset);
		}
	}

	public function describe($table_name = '')
	{
		if (!is_string($table_name) || $table_name == '') {
			throw new SqlException('Request table schema for given table requires table name to be a string value');
		}
		// don't escape for now for brevity
		// --TODO ESCAPE TABLE NAME
		$data = $this->dbApi->exec("DESCRIBE `".$table_name."`");
		return $data;
	}

	public function changeDb($db = '')
	{
		if (!is_string($db) || $db == '') {
			throw new SqlException('Changing the database requires the database as a string value');
		}
		$this->dbApi->changeDb($db);
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
	 *
	 * @param $type
	 * @param $dsn
	 */
	public function setup($type, $dsn)
	{
		return;
	}


	public function getTables()
	{
		return $this->dbApi->getTables();
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
		$this->sqlObj = new Statements\SqlBuilderSelect($this->dbApi);

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
			$this->sqlObj = new Statements\SqlBuilderInsert($this->dbApi);
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
	public function update($table = null, $values = null, $where = null, $where_vars = null)
	{
		$this->_unsetObj();
		if (is_null($table) || is_null($values)) {
			return $this;
		} else {
			$this->sqlObj = new Statements\SqlBuilderUpdate($this->dbApi);
			$this->sqlObj->table($table)->values($values)->where($where, $where_vars);
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
	public function delete($table = null, $where = null, $where_vars = null)
	{
		$this->_unsetObj();
		if (is_null($table)) {
			return $this;
		}

		$this->sqlObj = new Statements\SqlBuilderDelete($this->dbApi);
		if (is_null($where)) {
			$this->sqlObj->table($table);
		} else {
			$this->sqlObj->table($table);
			$this->sqlObj->where($where, $where_vars);
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

	public function uuid()
	{
		$uuid = $this->raw("SELECT UUID() as uuid");
		return $uuid[0]['uuid'];
	}

	/**
	 * Query the object wrapper
	 *
	 * @param bool|array $options_or_keyindex
	 * @return bool|array
	 */
	public function query($options_or_keyindex = array())
	{
		if (is_null($this->sqlObj)) {
			return false;
		}

		if (is_bool($options_or_keyindex)) {
			$options = array('keyindex' => $options_or_keyindex);
		} else {
			$options = $options_or_keyindex;
		}

		$sql = $this->sqlObj->__toString();
		$return_val = $this->dbApi->query($sql, null, $options);
		return $return_val;
	}


	/**
	 * Execute a non select query
	 *
	 * @return bool|array
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
			$str = $this->sqlObj->__toString();
			return $str;
		} else {
			return 'Nothing set.';
		}
	}
}



class SqlException extends \Exception {}