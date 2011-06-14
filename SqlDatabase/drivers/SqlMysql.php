<?php

class SqlMysql extends SqlConnectionAbstract
{
	protected $current_link = null;
	public function connect( $dsn ) {
		$args = $this->validateDsn($this->parseDsn($dsn));

		//this is ALWAYS a new link
		$resource = mysql_connect($args['server'], $args['username'], $args['password'], true);
		mysql_select_db($args['dbname'], $resource);
		return $resource;
	}
	public function query($sql, $resource) {
		$stmp = mysql_query($sql, $resource);
		if (!$stmp) {
			return $this->errorOut();
		}
		if (isSet($this->query_bootstrap) && is_callable($this->query_bootstrap)) {
			return $this->query_bootstrap($stmp);
		}
		$rows = array();
		while ($row = mysql_fetch_assoc($stmp)) {
			$rows[] = $row;
		}
		return $rows;
	}
	public function execute($sql, $resource) {
		$stmp = mysql_query($sql, $resource);
		if (!$stmp) {
			return $this->errorOut();
		}
		return $stmp;
	}
	public function showTables($resource) {
		
	}
	public function database($db, $resource) {
		$resource = mysql_select_db($db, $resource);
		if (!$resource) {
			throw new SqlDatabaseMysqlDriver('There was a problem switching the database '. mysql_error());
		}
		return $resource;
	}
	public function showColumns($table, $resource) {
		
	}
	protected function errorOut()
	{
		if (is_callable($this->die_func)) {
			$this->die_func(mysql_error());
		}
		throw new SqlDatabaseMysqlDriver('There was a problem with the request;' . mysql_error());
	}

	public function formatColumn( $column )
	{
		$column = $this->stripColumns($column);
		return '`'.$column.'`';
	}
	public function formatTable($table)
	{
		$table = $this->stripColumns($table);
		return '`'.$table.'`';
	}

	public function serverStatus($resource) {}


	public function escape( $str )
	{
		return mysql_real_escape_string($str);
	}
}


class SqlDatabaseMysqlDriver extends Exception {}