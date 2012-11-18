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
 * The MySQL class that connects to a MySQL database
 *
 * @package SqlMysql
 */
class SqlMysql extends SqlConnectionAbstract
{

	/**
	 * Current resource
	 *
	 * @var null
	 */
	protected $current_link = null;

	/**
	 * Connect to the database
	 *
	 * @param $dsn
	 * @return mixed|resource
	 */
	public function connect( $dsn ) {
		$args = $this->validateDsn($this->parseDsn($dsn));

		//this is ALWAYS a new link
		$resource = mysql_connect($args['server'], $args['username'], $args['password'], true);
		mysql_select_db($args['dbname'], $resource);
		return $resource;
	}

	/**
	 * Query the database
	 *
	 * @param $sql
	 * @param $resource
	 * @return array|mixed|void
	 */
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

	/**
	 * Exceute the query to the resource
	 *
	 * Primarily for update, inserts, delete statements
	 *
	 * @param $sql
	 * @param $resource
	 * @return mixed|resource|void
	 */
	public function execute($sql, $resource) {
		$stmp = mysql_query($sql, $resource);
		if (!$stmp) {
			return $this->errorOut();
		}
		return $stmp;
	}


	/**
	 * Shell for the show tables function
	 *
	 * Currently not usable
	 *
	 * @param $resource
	 * @return mixed|void
	 */
	public function showTables($resource) {
		
	}

	/**
	 * Create the resource from the database
	 *
	 * @param $db
	 * @param $resource
	 * @return bool|mixed
	 * @throws SqlDatabaseMysqlDriver
	 */
	public function database($db, $resource) {
		$resource = mysql_select_db($db, $resource);
		if (!$resource) {
			throw new SqlMysqlDriverException('There was a problem switching the database '. mysql_error());
		}
		return $resource;
	}

	/**
	 * Show columns function
	 *
	 * Currently unusable
	 *
	 * @param $table
	 * @param $resource
	 * @return mixed|void
	 */
	public function showColumns($table, $resource) {
		
	}

	/**
	 * Erroring out on the database;
	 *
	 * @return mixed|void
	 * @throws SqlDatabaseMysqlDriver
	 */
	protected function errorOut()
	{
		if (is_callable($this->die_func)) {
			$this->die_func(mysql_error());
		}
		throw new SqlMysqlDriverException('There was a problem with the request;' . mysql_error());
	}

	/**
	 * Format the column as necessary for MySQL
	 *
	 * @param $column
	 * @return mixed|string
	 */
	public function formatColumn( $column )
	{
		$column = $this->stripColumns($column);
		return '`'.$column.'`';
	}

	/**
	 * Format the table to MySQL standard
	 *
	 * @param $table
	 * @return mixed|string
	 */
	public function formatTable($table)
	{
		$table = $this->stripColumns($table);
		return '`'.$table.'`';
	}

	/**
	 * Shell function for the serverStatus
	 *
	 * @param $resource
	 * @return mixed|void
	 */
	public function serverStatus($resource) {}


	/**
	 * Escape the string for mysql
	 *
	 * @param $str
	 * @return mixed|string
	 */
	public function escape( $str )
	{
		return mysql_real_escape_string($str);
	}
}


class SqlMysqlDriverException extends \Exception {}