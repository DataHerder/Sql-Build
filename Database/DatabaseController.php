<?php

namespace SqlBuilder\Database;

class DatabaseController {

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
		$this->database_methods['mysql'] = new Drivers\Mysql;
		//$this->database_methods['postgres'] = new SqlPostgresql;
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
	 * @throws DatabaseControllerException
	 * @return bool|mixed
	 */
	public function __call( $method, $params = array() )
	{
		// we want to mainly route all calls to the current method being used
		if (method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $params);

		} elseif (method_exists($this->current_method, $method)) {

			array_push($params, $this->current_resource);
			try {
				$return_data = call_user_func_array(array($this->current_method, $method), $params);
				return $return_data;
			} catch (DatabaseControllerException $e) {
				//there was an error with the database
				$parse_message = $e->getMessage();
				list($message, $last_error) = explode(';', $parse_message);
				$this->last_error = $last_error;
				$this->error_message = $message;
				return false;
			}

		} else {
			throw new DatabaseControllerException('Method "' . $method . '" does not exist for SqlDatabase');
		}
	}



	/**
	 * Get the last error in the form of a class
	 *
	 * @return DatabaseControllerException
	 */
	public function getError()
	{
		$arr = new DatabaseControllerException;
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
		if ($this->current_method instanceof Drivers\Mysql) {
			return 'mysql';
		}
		elseif ($this->current_method instanceof Drivers\Postgresql) {
			return 'postgres';
		}
	}


	/**
	 * Parses the dsn, allows either a string or an array
	 *
	 * @param $dsn
	 * @return string
	 * @throws DatabaseControllerException
	 */
	private function parseHost($dsn)
	{
		if ($dsn == '') {
			return 'No Host Provided';
		}
		if (is_string($dsn)) {
			$data = explode(' ', $dsn);
			$dsn_args = array();
			for( $i=0;$i<count($data);$i++ ) {
				$tmp = explode('=', $data[$i]);
				$dsn_args[$tmp[0]]=$tmp[1];
			}
			if ( !isSet($dsn_args['host']) && !isSet($dsn_args['server'])) {
				throw new DatabaseControllerException('Host is required in dsn when connecting to the database');
			}
			return $dsn_args['host'];
		} elseif (is_array($dsn) && !isSet($dsn['host']) && !isSet($dsn['server'])) {
			throw new DatabaseControllerException('Host is required in dsn when connecting to the database');
		} elseif (is_array($dsn)) {
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
	 * @throws DatabaseControllerException
	 * @return bool|null
	 */
	public function setup( $type = '', $dsn = '' )
	{
		//if ( (!is_array($type)) &&  (($type == null || $type == '' || !is_string($type)) || ($dsn == null || $dsn == '' || empty($dsn))) ) {
		if ((!is_array($type)  && $type == '' && $dsn == '')) {
			throw new DatabaseControllerException('Type and / or Dsn is empty on setup');
		}
		if (is_string($dsn)) {
			$host = $this->parseHost($dsn);
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
								throw new DatabaseControllerException('Unable to connect to database. For '.$type.' on dsn: '.$this->parseHost($dsn[$i]));
							}
						}
					}
					elseif (is_string($dsn)){
						if( !$this->setConnection($type, $dsn) ){
							throw new DatabaseControllerException('Unable to connect to database for '. $type.' on dsn: '.$this->parseHost($dsn[$i]));
						}
					}
				}
				return null;
			}
			return null;
		}
		else {
			// call parseHost first, it ensures there is a host in the dsn
			if (! $this->setConnection($type, $dsn) ) {
				throw new DatabaseControllerException('Unable to connect to database for ' . $type . ' on dsn: '. $this->parseHost($dsn));
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
	 * @throws DatabaseControllerException
	 */
	protected function setConnection($type, $dsn)
	{
		$host = $this->parseHost($dsn);
		if (!is_string($type)) {
			return false;
		}
		$resource = $this->database_methods[$type]->connect($dsn);
		if ( !is_resource($resource) ) {
			throw new DatabaseControllerException('Resource not returned on setup.  Check that your database is running, or your dsn');
		}
		$this->database_connections[$type][$host]['resource'] = $resource;
		$this->database_connections[$type][$host]['dsn'] = $dsn;
		$this->current_method =& $this->database_methods[$type];
		$this->current_resource =& $this->database_connections[$type][$host]['resource'];
		return true;
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


class DatabaseControllerException extends \Exception {}