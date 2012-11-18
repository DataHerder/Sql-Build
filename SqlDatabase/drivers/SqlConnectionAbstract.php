<?php
/**
 * Sql Database Abstract file
 *
 * The abstract class that must be the base of every sql connection class
 *
 *
 * Copyright (C) 2011  Paul Carlton
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
 * @category    Structured Query Language
 * @package     SqlBuilder
 * @license     GNU license
 * @version     0.1
 * @link        my.public.repo
 * @since       File available since
 */


namespace SqlBuilder\SqlDatabase\Drivers;

/**
 * The abstract class that defines what is needed in every sql database connection class
 * This extends currently the postgres and mysql classes
 *
 *  - SqlLite will be added later
 *  - Oracle Support will be added
 *  - Microsoft Server will be added
 *
 * @package SqlConnectionAbstract
 */
abstract class SqlConnectionAbstract
{
	/**
	 * Every class must have a connect method
	 *
	 * @param $dsn
	 * @return mixed
	 */
	abstract public function connect( $dsn );

	/**
	 * Every class must have a query method
	 *
	 * @param $sql
	 * @param $resource
	 * @return mixed
	 */
	abstract public function query( $sql, $resource );

	/**
	 * Every class must have an execute method
	 *
	 * @param $sql
	 * @param $resource
	 * @return mixed
	 */
	abstract public function execute( $sql, $resource );

	/**
	 * Every class must have a database method
	 *
	 * @param $db
	 * @param $resource
	 * @return mixed
	 */
	abstract public function database( $db, $resource );

	/**
	 * Every class must have a show tables method
	 *
	 * @param $resource
	 * @return mixed
	 */
	abstract public function showTables( $resource );

	/**
	 * Every class must have a show columns method
	 *
	 * @param $table
	 * @param $resource
	 * @return mixed
	 */
	abstract public function showColumns( $table, $resource );

	/**
	 * Every class must have an escape method
	 *
	 * This is important for dynamic escaping and column formatting
	 *
	 * @param $string
	 * @return mixed
	 */
	abstract public function escape($string);

	/**
	 * Every class must have a formatTable method
	 *
	 * @param $string
	 * @return mixed
	 */
	abstract public function formatTable($string);

	/**
	 * Every class must have a formatColumn method
	 *
	 * @param $string
	 * @return mixed
	 */
	abstract public function formatColumn($string);

	/**
	 * Every class must have an errorOut method
	 *
	 * @return mixed
	 */
	abstract protected function errorOut();

	/**
	 * Every class must have a serverStatus method
	 *
	 * @param $resource
	 * @return mixed
	 */
	abstract public function serverStatus($resource); // returns information about the database

	//

	/**
	 * A user set die function held in a variable
	 *
	 * A lambda function that is set in the bootstrap
	 *
	 * @var function
	 */
	protected $die_func;

	/**
	 * Shared dsn parse function
	 *
	 * @param $dsn
	 * @return array
	 * @throws SqlDatabaseAbstractException
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
			throw new SqlDatabaseAbstractException('Unknown dsn type, expecting string or array');
		}
	}


	/**
	 * Ensure that the dsn has the necessary information to ensure
	 * a successful connection
	 *
	 * @param $args
	 * @return mixed
	 * @throws SqlDatabaseAbstractException
	 */
	protected function validateDsn($args)
	{
		if (!isSet($args['database']) && !isSet($args['dbname'])){
			throw new SqlDatabaseAbstractException('Database name required in dsn');
		}
		elseif (!isSet($args['host']) && !isSet($args['server'])){
			throw new SqlDatabaseAbstractException('Host/Server required in dsn');
		}
		elseif (!isSet($args['user']) && !isSet($args['username'])){
			throw new SqlDatabaseAbstractException('User/Username required in dsn');
		}
		elseif (!isSet($args['password'])){
			throw new SqlDatabaseAbstractException('Password required in dsn');
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

	/**
	 * Strip the columns
	 *
	 * @param $string
	 * @return mixed
	 */
	protected function stripColumns( $string )
	{
		//generic strip, before specific strip in class
		return preg_replace("/`/",'', $string);
	}

}


class SqlDatabaseAbstractException extends \Exception {}