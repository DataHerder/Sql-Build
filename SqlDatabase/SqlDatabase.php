<?php
/**
 * Sql Database file
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


namespace SqlBuilder\SqlDatabase;

use \SqlBuilder\SqlDatabase\Drivers\SqlMysql as SqlMysql;
use \SqlBuilder\SqlDatabase\Drivers\SqlPostgresql as SqlPostgresql;


/**
 * The main sql class that manages the sql database connections
 *
 * @package SqlDatabase
 */
class SqlDatabase
{

	/**
	 * The database connection array
	 *
	 * @var array
	 */
	protected $database_connections = array();

	/**
	 * Reference to the current method
	 *
	 * Current resource and current method are both references
	 * from the database_connections variable
	 *
	 * @var null|string
	 */
	public $current_method = null;

	/**
	 * Reference to the current resource
	 *
	 * Current resource and current method are both references
	 * from the database_connections variable
	 *
	 * @var resource|null
	 */
	protected $current_resource = null;

	/**
	 * External die function
	 *
	 * @var Function|null
	 */
	protected $die_func = null;

	/**
	 * The last error returned
	 *
	 * @var null | string
	 */
	protected $last_error;


	/**
	 * Last error message
	 *
	 * @var string
	 */
	protected $error_message = '';


	/**
	 * constructor
	 */
	public function __construct()
	{
		$this->database_methods['mysql'] = new SqlMysql;
		$this->database_methods['postgres'] = new SqlPostgresql;
		// set current_method to mysql because it's the default
		// careful, if you escape and set a different syntax without switching
		// database connections, the default will remain the default and will
		// be checked via current_method->escape(), iow: if you don't switch to postgres
		// from mysql and set a different syntax, it will still escape with mysql
		$this->current_method = $this->database_methods['mysql'];
		return $this;
	}


	/**
	 * Calls the correct method on the current resource
	 *
	 * @param $method
	 * @param array $params
	 * @return bool|mixed
	 * @throws SqlDatabaseException
	 */
	public function __call( $method, $params = array() )
	{
		// we want to mainly route all calls to the current method being used
		if (method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $params);
		}
		elseif (method_exists($this->current_method, $method)) {
			array_push($params, $this->current_resource);
			try {
				return call_user_func_array(array($this->current_method, $method), $params);
			} catch (SqlDatabaseError $e) {
				//there was an error with the database
				$parse_message = $e->getMessage();
				list($message, $last_error) = explode(';', $parse_message);
				$this->last_error = $last_error;
				$this->error_message = $message;
				return false;
			}
		}
		else {
			throw new SqlDatabaseException('Method "' . $method . '" does not exist for SqlDatabase');
		}
	}


	/**
	 * Get the last error in the form of a class
	 *
	 * @return SqlDatabaseError
	 */
	public function getError()
	{
		$arr = new SqlDatabaseError;
		$arr->error_type = $this->getConnectionType();
		$arr->last_error = $this->last_error;
		$arr->error_message = $this->error_message;
		return $arr;
	}

	/**
	 * Return the current connection
	 *
	 * @return string
	 */
	public function getConnectionType()
	{
		if ($this->current_method instanceof SqlMysql) {
			return 'mysql';
		}
		elseif ($this->current_method instanceof SqlPostgresql) {
			return 'postgres';
		}
	}


	/**
	 * Parses the dsn, allows either a string or an array
	 *
	 * @param $dsn
	 * @return string
	 * @throws SqlDatabaseException
	 */
	private function parseHost( $dsn )
	{
		if ($dsn == '') {
			return 'No Host Provided';
		}
		if ( is_string($dsn) ) {
			$data = explode(' ', $dsn);
			$dsn_args = array();
			for( $i=0;$i<count($data);$i++ ) {
				$tmp = explode('=', $data[$i]);
				$dsn_args[$tmp[0]]=$tmp[1];
			}
			if ( !isSet($dsn_args['host']) && !isSet($dsn_args['server'])) {
				throw new SqlDatabaseException('Host is required in dsn when connecting to postgres');
			}
			if (isSet($dsn_args['server'])) {
				$host = $dsn_args['server'];
			} else { $host = $dsn_args['host']; }
			return $host;
		}
		elseif (is_array($dsn) && !isSet($dsn['host']) && !isSet($dsn['server'])) {
			throw new SqlDatabaseException('Host is required in dsn when connecting to postgres');
		}
		elseif (is_array($dsn)) {
			if (isSet($dsn['server'])) {
				$host = $dsn['server'];
			} else { $host = $dsn['host']; }
			return $host;
		}
	}


	/**
	 * Make sure we are connected
	 *
	 * @return bool
	 */
	public function checkConnection()
	{
		return is_resource($this->current_resource);
	}


	/**
	 * Setup the connection
	 *
	 * @param string $type
	 * @param string $dsn
	 * @return bool|null
	 * @throws SqlDatabaseException
	 */
	public function setup( $type = '', $dsn = '' )
	{
		if ( (!is_array($type)) &&  (($type == null || $type == '' || !is_string($type)) || ($dsn == null || $dsn == '' || empty($dsn))) ) {
			throw new SqlDatabaseException('Type and / or Dsn is empty on setup');
		}

		if (is_array($type)) {
			if (empty($type)) {
				return false;
			}
			if ($type[0] == 'many') {
				// we probably have just two for right now, do we need more?  probably not
				$c = $type[1];
				foreach ( $c as $type => $dsn ) {
					if (is_array($dsn)) {
						for( $i=0; $i<count($dsn); $i++ ){
							if( !$this->setConnection($type, $dsn[$i]) ){
								throw new SqlDatabaseException('Unable to connect to database. For '.$type.' on dsn: '.$this->parseHost($dsn[$i]));
							}
						}
					}
					elseif (is_string($dsn)){
						if( !$this->setConnection($type, $dsn) ){
							throw new SqlDatabaseException('Unable to connect to database for '. $type.' on dsn: '.$this->parseHost($dsn[$i]));
						}
					}
				}
				return null;
			}
		}
		else {
			// call parseHost first, it ensures there is a host in the dsn
			if (! $this->setConnection($type, $dsn) ) {
				throw new SqlDatabaseException('Unable to connect to database for ' . $type . ' on dsn: '. $this->parseHost($dsn));
			}
			return null;
		}
	}


	/**
	 * Sets up the connection to the database and
	 * sets the necessary reference variables on the currently
	 * made connection
	 *
	 * @param $type
	 * @param $dsn
	 * @return bool
	 * @throws SqlDatabaseException
	 */
	protected function setConnection($type, $dsn)
	{
		$host = $this->parseHost($dsn);
		if (!is_string($type)) {
			return false;
		}
		$resource = $this->database_methods[$type]->connect($dsn);
		if ( !is_resource($resource) ) {
			throw new SqlDatabaseException('Resource not returned on setup.  Check that your database is running, or your dsn');
		}
		$this->database_connections[$type][$host]['resource'] = $resource;
		$this->database_connections[$type][$host]['dsn'] = $dsn;
		$this->current_method =& $this->database_methods[$type];
		$this->current_resource =& $this->database_connections[$type][$host]['resource'];
		return true;
	}


	/**
	 * A shell to return current database connections
	 *
	 * @deprecated
	 */
	public function databaseConnections()
	{
		// return current database connections
	}


	/**
	 * Add a connection to the array of connections
	 *
	 * @param $type
	 * @param $dsn
	 * @return null
	 */
	public function addConnection( $type, $dsn )
	{
		$host = $this->parseHost($dsn);
		$this->database_connections[$type][$host]['dsn'] = $dsn;
		$this->database_connections[$type][$host]['resource'] = $this->database_methods[$type]->connect($dsn);
		unset($this->current_resource);
		unset($this->current_method);
		$this->current_resource =& $this->database_connections[$type][$num]['resource'];
		$this->current_method =& $this->database_methods[$type];
		return null;
	}


	/**
	 * Switch the database currently in use
	 *
	 * @param $database_name
	 * @param null $type
	 * @throws SqlDatabaseException
	 */
	public function switchDatabase( $database_name, $type = null )
	{
		if ( $database_name == '' ) {
			throw new SqlDatabaseException('Exception raised on switching to an undefined database');
		}
		else {
			$this->current_method->switchDatabase();
		}
	}


	/**
	 * Switch the database server currently in use
	 *
	 * @param string $type
	 * @param string $host
	 * @return null
	 * @throws SqlDatabaseException
	 */
	public function switchServer($type = '', $host = '')
	{
		if (!isSet($this->database_connections[$type][$host])) {
			throw new SqlDatabaseException('Exception raised while switching to a connection undefined.  Careful. Attempted type='.$type.' and server='.$host);
		}
		unset($this->current_resource);
		unset($this->current_method);
		$this->current_resource =& $this->database_connections[$type][$host]['resource'];
		$this->current_method =& $this->database_methods[$type];
		//var_dump($this->current_resource);
		return null;
	}


	/**
	 * Not in use
	 *
	 * @deprecated
	 * @param string $type
	 * @param string $host
	 */
	public function switchConnection( $type = '', $host = '' )
	{
	}

	/**
	 * Ensure to destroy the connection to the database
	 * when class put into garbage collection
	 *
	 */
	public function __destruct()
	{
		unset($this->current_resource);
		foreach( $this->database_connections as $type => $database ) {
			foreach ( $database as $host => $connect_info ) {
				if ( $type == 'mysql' ) {
					@mysql_close($connect_info['resource']);
				}
				elseif ( $type == 'postgres' ) {
					@pg_close($connect_info['resource']);
				}
			}
		}
	}
}
