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
	 * @var resource|null|\mysqli
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
		// we are operating on mysqli now so default to mysqli
		$this->database_methods['mysqli'] = new Drivers\Mysql;
		$this->current_method = $this->database_methods['mysqli'];
		//$this->database_methods['mysql'] = new Drivers\Mysql;
		//$this->database_methods['postgres'] = new SqlPostgresql;
		// set current_method to mysql because it's the default
		// careful, if you escape and set a different syntax without switching
		// database connections, the default will remain the default and will
		// be checked via current_method->escape(), iow: if you don't switch to postgres
		// from mysql and set a different syntax, it will still escape with mysql
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
	private function parseHost($dsn, $return_all = false)
	{
		if ($dsn == '') {
			return false;
		}
		$host = '';
		$dsn_args = array();
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
			$host = $dsn_args['host'];
		} elseif (is_array($dsn) && !isSet($dsn['host']) && !isSet($dsn['server'])) {
			throw new DatabaseControllerException('Host is required in dsn when connecting to the database');
		} elseif (is_array($dsn)) {
			if (isSet($dsn['server'])) {
				$host = $dsn['server'];
			} else { $host = $dsn['host']; }
			$dsn_args = $dsn;
		}
		if ($return_all) {
			return array('host' => $host, 'dsn' => $dsn, 'dsn_args' => $dsn_args);
		} else {
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
	 * Get the tables of the current connection
	 * @returns array
	 */
	public function getTables($by_schema = false, $filter = "")
	{
		if (is_resource($this->current_resource)) {
			if ($this->current_type == 'mysql') {

				$tables_ = mysql_list_tables($this->current_db, $this->current_resource);
				$tables = array();
				while ($table = mysql_fetch_row($tables_)) {
					$tables[] = $table[0];
				}
			}
		} elseif ($this->current_type == 'mysqli') {

			$rows = array();
			if ($by_schema === true) {
				$stmp = $this->current_resource->query(
					"SELECT * FROM INFORMATION_SCHEMA.TABLE WHERE TABLE_SCHEMA LIKE '".$this->current_resource->real_escape_string($filter)."'"
				);
				while ($row = $stmp->fetch_assoc()) {
					// give a lot of data back
					$rows[] = $row;
				}
			} else {
				$stmp = $this->current_resource->query("SHOW TABLES");
				while($row = $stmp->fetch_assoc()) {
					// format it nice coming from mysql
					$rows[] = array_values($row)[0];
				}
			}
			return $rows;
		}
		return array();
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
			$dsn_data = $this->parseHost($dsn, true);
			$host = $dsn_data['host'];
			$this->current_host = $host;
			$this->current_db = $dsn_data['dsn_args']['dbname'];
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
							throw new DatabaseControllerException('Unable to connect to database for '. $type.' on dsn: '.$this->parseHost($dsn[$c]));
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

	protected $current_type = null;
	protected $current_host = null;

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
		$dsn_data = $this->parseHost($dsn, true);
		$host = $dsn_data['host'];
		$this->current_db = $dsn_data['dsn_args']['dbname'];
		if (!is_string($type)) {
			return false;
		}
		$resource = $this->database_methods[$type]->connect($dsn);
		if ( !is_resource($resource) && $type == 'mysql') {
			throw new DatabaseControllerException(
				'Resource not returned on setup.  Check that your database is running, or your dsn: ' . mysql_error()
			);
		} elseif ($type == 'mysqli' && $resource->connect_errno) {
			throw new DatabaseControllerException(
				'Resource not returned on setup.  Check that your database is running, or your dsn: '
				.$resource->connect_errno.' - '
				.$resource->connect_error
			);
		}
		$this->current_type = $type;
		$this->current_host = $host;
		$this->database_connections[$type][$host]['resource'] = $resource;
		$this->database_connections[$type][$host]['dsn'] = $dsn;
		$this->current_method =& $this->database_methods[$type];
		$this->current_resource =& $this->database_connections[$type][$host]['resource'];
		return true;
	}

	protected $current_db = null;
	public function changeDB($db)
	{
		$resource = $this->database_connections[$this->current_type][$this->current_host]['resource'];
		if ($this->current_type == 'mysql') {
			if (!@mysql_select_db($db, $resource)) {
				throw new DatabaseControllerException('Error in changing DB: ' . mysql_error());
			}
		} elseif ($this->current_type == 'mysqli') {
			$resource->select_db($db);
		} elseif ($this->current_type == 'postgres') {
			throw new DatabaseControllerException('Not supported for postgres at this time');
		}
		$this->current_db = $db;
		return true;
	}

	/**
	 * Ensure to destroy the connection to the database
	 * when class put into garbage collection
	 *
	 */
	public function __destruct()
	{
		foreach( $this->database_connections as $type => $database ) {
			foreach ( $database as $host => $connect_info ) {
				if ( $type == 'mysql' ) {
					@mysql_close($connect_info['resource']);
				} elseif ($type == 'mysqli') {
					//$connect_info['resource']->close();
				} elseif ( $type == 'postgres' ) {
					@pg_close($connect_info['resource']);
				}
			}
		}
		//dbg_array($this->current_resource);
		//$this->current_resource->close();
		//unset($this->current_resource);
	}

}


class DatabaseControllerException extends \Exception {}