<?php

namespace SqlBuilder\Database\Drivers;

class Mysql extends Abstracts\Driver {

	protected $current_link = null;
	protected $die_func = null;
	protected $__type = 'mysqli';

	public function __construct()
	{
		// extend this to be programmable at the driver level
		$this->__type = 'mysqli';
	}
	/**
	 * @var null|\mysqli
	 */
	protected $current_resource = null;

	public function escape($string) {
		if ($this->__type == 'mysqli') {
			return $this->current_resource->real_escape_string($string);
		} elseif ($this->__type == 'mysql') {
			return mysql_real_escape_string($string);
		} elseif ($this->__type == 'postgres') {
			return pg_escape_string($string);
		} else {
			return null;
		}
	}

	public function connect($dsn)
	{
		$args = $this->validateDsn($this->parseDsn($dsn));
		$mysqli = new \mysqli($args['server'], $args['username'], $args['password'], $args['dbname']);
		if ($mysqli->connect_errno) {
			throw new MysqlDriverException('Unable to connect to mysql: '.
				$mysqli->connect_errno.' - '.$mysqli->connect_error
			);
		}
		$this->current_resource = $mysqli;
		return $mysqli;
	}

	public function query($sql, $resource, $options = array('keyindex' => false))
	{
		if (isSet($options['stmp'])) {
			// if there is already a stamp being passed assign and do not query twice
			$stmp = $options['stmp'];
		} else {
			if (is_null($resource) || $resource === false) {
				$resource = $this->current_resource;
			}
			$stmp = $resource->query($sql);
			if (!$stmp) {
				$this->errorOut();
			}
		}

		if (!isSet($options['keyindex'])) {
			$options['keyindex'] = false;
		}

		$rows = array();
		$options['keyindex'] = (bool)$options['keyindex'];

		if ($options['keyindex']) {
			$columns = $stmp->fetch_assoc(); //mysql_fetch_array($stmp, MYSQL_ASSOC);
			$stmp->data_seek(0);
			$columns = array_keys($columns); // reassign to just the keys
			while ($row = $stmp->fetch_assoc()) {
				// take the first column as the unique identifier
				$rows[$row[$columns[0]]] = $row;
			}
		} else {
			$stmp->data_seek(0);
			while ($row = $stmp->fetch_assoc()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}


	/**
	 * Connect to the database
	 *
	 * @deprecated
	 * @param $dsn
	 * @return resource
	 */
	public function _connect($dsn)
	{
		$args = $this->validateDsn($this->parseDsn($dsn));
		// resources
		$resource = mysql_connect($args['server'], $args['username'], $args['password'], true);
		$this->current_resource = $resource;
		mysql_select_db($args['dbname'], $resource);
		return $resource;
	}


	/**
	 * Query the database
	 *
	 * @deprecated
	 * @param $sql
	 * @param $resource
	 * @param array $options
	 * @return array
	 */
	public function _query($sql, $resource, $options = array('keyindex' => false))
	{

		if (isSet($options['stmp'])) {
			$stmp = $options['stmp'];
		} else {
			if (is_null($resource) || $resource === false) {
				$resource = $this->current_resource;
			}
			$stmp = mysql_query($sql, $resource);
			if (!$stmp) {
				$this->_errorOut();
			}
		}

		if (!isSet($options['keyindex'])) {
			$options['keyindex'] = false;
		}

		$rows = array();
		$options['keyindex'] = (bool)$options['keyindex'];

		if ($options['keyindex']) {
			$columns = mysql_fetch_array($stmp, MYSQL_ASSOC);
			mysql_data_seek($stmp, 0);
			$columns = array_keys($columns); // reassign to just the keys
			while ($row = mysql_fetch_array($stmp, MYSQL_ASSOC)) {
				// take the first column as the unique identifier
				$rows[$row[$columns[0]]] = $row;
			}
		} else {
			while ($row = mysql_fetch_assoc($stmp)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}



	public function exec($sql, $resource, $options = array())
	{
		if (is_null($resource) || $resource === false) {
			$resource = $this->current_resource;
		}
		if (preg_match("@insert@i", $sql)) {
		}
		$stmp = $resource->query($sql);
		if (!is_object($stmp) && $stmp === false) {
			$this->errorOut();
		}
		if ($stmp === true) {
			return $resource->affected_rows;
		} else {
			return $this->query(false, false, $options + array('stmp' => $stmp));
		}
	}


	/**
	 * @deprecated
	 * @param $sql
	 * @param $resource
	 * @param array $options
	 * @return array|int|mixed|void
	 * @throws MysqlDriverException
	 */
	public function _exec($sql, $resource, $options = array())
	{
		if (is_null($resource) || $resource === false) {
			$resource = $this->current_resource;
		}

		$stmp = mysql_query($sql, $resource);
		if (!$stmp) {
			return $this->_errorOut();
		}
		if ($stmp === true) {
			return mysql_affected_rows($resource);
		} else {
			return $this->query(false, false, $options + array('stmp' => $stmp));
		}
	}


	protected function errorOut()
	{
		if (is_callable($this->die_func)) {
			$this->{'die_func'}($this->current_resource->errno, $this->current_resource->error);
		}
		throw new MysqlDriverException(
			"Database Error: ".$this->current_resource->errno." - ".$this->current_resource->error
		);
	}

	/**
	 * Erroring out on the database;
	 *
	 * @deprecated
	 * @throws MysqlDriverException
	 * @return mixed|void
	 */
	protected function _errorOut()
	{
		if (is_callable($this->die_func)) {
			$this->{'die_func'}(mysql_error());
		}
		throw new MysqlDriverException('There was a problem with the request;' . mysql_error());
	}
}




class MysqlDriverException extends \Exception {}
