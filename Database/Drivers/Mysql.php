<?php

namespace SqlBuilder\Database\Drivers;

class Mysql extends Abstracts\Driver {

	protected $current_link = null;
	protected $die_func = null;
	protected $current_resource = null;

	/**
	 * Connect to the database
	 *
	 * @param $dsn
	 * @return resource
	 */
	public function connect($dsn)
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
	 * @param $sql
	 * @param $resource
	 * @param array $options
	 * @return array
	 */
	public function query($sql, $resource, $options = array('keyindex' => false))
	{

		if (isSet($options['stmp'])) {
			$stmp = $options['stmp'];
		} else {
			if (is_null($resource) || $resource === false) {
				$resource = $this->current_resource;
			}
			$stmp = mysql_query($sql, $resource);
			if (!$stmp) {
				return $this->errorOut();
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
		$stmp = mysql_query($sql, $resource);
		if (!$stmp) {
			return $this->errorOut();
		}
		if ($stmp === true) {
			return true;
		} else {
			return $this->query(false, false, $options + array('stmp' => $stmp));
		}
	}



	/**
	 * Erroring out on the database;
	 *
	 * @throws MysqlDriverException
	 * @return mixed|void
	 */
	protected function errorOut()
	{
		if (is_callable($this->die_func)) {
			$this->{'die_func'}(mysql_error());
		}
		throw new MysqlDriverException('There was a problem with the request;' . mysql_error());
	}
}




class MysqlDriverException extends \Exception {}
