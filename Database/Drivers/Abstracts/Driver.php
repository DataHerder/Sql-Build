<?php

namespace SqlBuilder\Database\Drivers\Abstracts;

abstract class Driver implements DriverInterface {

	/**
	 * Abstract connection
	 *
	 * @param $dsn
	 * @return mixed
	 */
	abstract public function connect($dsn);

	/**
	 * Abstract query function
	 *
	 * @param $query
	 * @param $resource
	 * @return mixed
	 */
	abstract public function query($query, $resource);


	/**
	 * Shared dsn parse function
	 *
	 * @param $dsn
	 * @return array
	 * @throws DriverAbstractException
	 */
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
			throw new DriverAbstractException('Unknown dsn type, expecting string or array');
		}
	}


	/**
	 * Ensure that the dsn has the necessary information to ensure
	 * a successful connection
	 *
	 * @param $args
	 * @return mixed
	 * @throws DriverAbstractException
	 */
	protected function validateDsn($args)
	{
		if (!isSet($args['database']) && !isSet($args['dbname'])){
			throw new DriverAbstractException('Database name required in dsn');
		}
		elseif (!isSet($args['host']) && !isSet($args['server'])){
			throw new DriverAbstractException('Host/Server required in dsn');
		}
		elseif (!isSet($args['user']) && !isSet($args['username'])){
			throw new DriverAbstractException('User/Username required in dsn');
		}
		elseif (!isSet($args['password'])){
			throw new DriverAbstractException('Password required in dsn');
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
}


class DriverAbstractException extends \Exception {}