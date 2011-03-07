<?php

abstract class SqlConnectionAbstract
{
	abstract public function connect( $dsn );
	abstract public function query( $sql, $resource );
	abstract public function execute( $sql, $resource );
	abstract public function database( $db, $resource );
	abstract public function showTables( $resource );
	abstract public function showColumns( $table, $resource );
	//this is important for dynamic escaping and column formatting
	abstract public function escape($string);
	abstract public function formatColumn($string);
	abstract protected function errorOut();
	abstract public function serverStatus($resource); // returns information about the database

	// a lambda function that is set in the bootstrap
	protected $die_func;

	protected function parseDsn($dsn)
	{
		if (is_string($dsn)) {
			$tmp = explode(' ', $dsn);
			$args = array();
			for ($i=0; $i<count($tmp); $i++) {
				$tmp2 = explode('=', $tmp[$i]);
				$args[$tmp2[0]] = $tmp2[1];
			}
			return $args;
		}
		elseif (is_array($dsn)) {
			return $dsn;
		}
		else {
			throw new SqlDatabaseException('Unknown dsn type, expecting string or array');
		}
	}
	protected function validateDsn($args)
	{
		if (!isSet($args['database']) && !isSet($args['dbname'])){
			throw new SqlDatabaseException('Database name required in dsn');
		}
		elseif (!isSet($args['host']) && !isSet($args['server'])){
			throw new SqlDatabaseException('Host/server required in dsn');
		}
		elseif (!isSet($args['user']) && !isSet($args['username'])){
			throw new SqlDatabaseException('user/username required in dsn');
		}
		elseif (!isSet($args['password'])){
			throw new SqlDatabaseException('Password required in dsn');
		}

		//ensure the same format for all child classes
		if (isSet($args['user'])) {
			$user = $args['user'];
			$args['username'] = $user;
			unset($args['user']);
		}
		if (isSet($args['host'])) {
			$server = $args['host'];
			$args['server'] = $server;
			unset($args['host']);
		}
		if (isSet($args['database'])) {
			$dbname = $args['database'];
			$args['dbname'] = $dbname;
			unset($args['database']);
		}
		return $args;
	}

	protected function stripColumns( $string )
	{
		//generic strip, before specific strip in class
		return preg_replace("/`/",'', $string);
	}
}


