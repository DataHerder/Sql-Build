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
 * The class for connecting to postgresql
 *
 * @package SqlPostgressql
 */
class SqlPostgresql extends SqlConnectionAbstract
{

	/**
	 * The current database resource
	 *
	 * @var
	 */
	private $current_resource;

	/**
	 * The last error given fom the database
	 *
	 * @var
	 */
	private $last_error;


	/**
	 * Generic constructor
	 */
	public function __construct(){}


	/**
	 * The function that creates the connection to the database
	 *
	 * @param $dsn
	 * @throws SqlPostgresDriverException
	 * @return mixed|resource
	 */
	public function connect( $dsn )
	{
		// dsn is checked here, if the pertinent information has not been met
		$args = $this->validateDsn($this->parseDsn($dsn));
		// we need the port for pg
		if (!isSet($args['port'])) {
			throw new SqlPostgresDriverException('Port is expected for postgres connection');
		}
		if (!is_string($dsn)) {
			$dsn = $this->pg_dsn($args);
		}
		$plink = pg_connect($dsn);
		return $plink; // return the resource to be allocated to the Database wrapper
	}


	/**
	 * Create the dsn from the arguments provided
	 *
	 * @param $args
	 * @return string
	 */
	private function pg_dsn($args)
	{
		$dsn = "host={$args['server']} port={$args['port']} dbname={$args['dbname']} user={$args['username']} password={$args['password']}";
		if (isSet($args['options'])){
			$dsn.= " options=".$args['options'];
		}
		return $dsn;
	}


	/**
	 * Execute the current query on the resource
	 *
	 * Primarily for update, insert, delete statements
	 *
	 * @param $sql
	 * @param $resource
	 * @throws SqlPostgresDriverException
	 * @return mixed|resource|void
	 */
	public function execute( $sql, $resource )
	{
		if (is_string($sql)) {
			$stmp = @pg_query($resource, $sql);
		}
		else {
			throw new SqlPostgresDriverException('Unknown Sql Type sent to database');
		}

		//if (!$stmp && is_callable($this->die_func)) {
		//	$this->last_error = pg_last_error();
		//	$this->error_func();
		//}
		if (!$stmp) {
			return $this->errorOut();
		}
		return $stmp;
	}


	/**
	 * Query the database
	 *
	 * @param $sql
	 * @param $resource
	 * @return array|mixed|void
	 */
	public function query( $sql, $resource )
	{
		$stmp = @pg_query($resource, $sql);
		if (!$stmp) {
			return $this->errorOut();
		}
		$rows_ = array();
		while ( $rows = @pg_fetch_assoc($stmp) ) {
			$rows_[] = $rows;
		}
		return $rows_;
	}


	/**
	 * Shell function for the serverStatus
	 *
	 * Unusable
	 *
	 * @param $resource
	 * @return mixed|void
	 */
	public function serverStatus($resource) {}


	/**
	 * Show tables method
	 *
	 * Unusable
	 *
	 * @param $resource
	 * @return bool|mixed
	 */
	public function showTables($resource)
	{
		return false;
	}

	/**
	 * Show the columns in the current
	 *
	 * @param $table
	 * @param $resource
	 * @return array|mixed
	 */
	public function showColumns( $table, $resource )
	{
		$set = pg_query('SELECT * FROM "'.$table.'" LIMIT 1');
		return array_keys(pg_fetch_assoc($set));
	}

	/**
	 * Currently unusable function
	 *
	 * @param $db
	 * @param $resource
	 * @return mixed|void
	 */
	public function database( $db, $resource ) {}

	/**
	 * Error out
	 *
	 * @throws SqlPostgresDriverException
	 * @return mixed|void
	 */
	protected function errorOut()
	{
		//$this->last_error = pg_last_error();
		if (is_callable($this->die_func)) {
			$this->die_func(pg_last_error());
		}
		throw new SqlPostgresDriverException('There was a problem with the request;' . pg_last_error());
	}

	/**
	 * Escape the string for postgres
	 *
	 * @param $str
	 * @return mixed|string
	 */
	public function escape( $str )
	{
		return pg_escape_string($str);
	}

	/**
	 * Format the column for postgres
	 *
	 * Prettifying the SQL
	 *
	 * @param $column
	 * @return mixed|string
	 */
	public function formatColumn( $column )
	{
		$column = $this->stripColumns($column);
		$column = preg_replace('/"/', '', $column);
		return '"' . $column . '"';
	}


	/**
	 * Format the table for postgres
	 *
	 * Prettifying the SQL
	 *
	 * @param $table
	 * @return mixed|string
	 */
	public function formatTable( $table ){
		$table = $this->stripColumns($table);
		if (strpos($table,'.')!==false) {
			$tmp = explode('.', $table);
			return '"'.$tmp[0].'"."'.$tmp[1].'"';
		}
		else {
			return '"'.$table.'"';
		}
	}

}


class SqlPostgresDriverException extends \Exception{}