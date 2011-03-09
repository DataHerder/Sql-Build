<?php
require_once('SqlDatabaseException.php');
require_once('SqlConnectionAbstract.php');
require_once('drivers/SqlMysql.php');
require_once('drivers/SqlPostgresql.php');

class SqlDatabase
{
	protected $database_connections = array();
	// current resource and current method are both references
	// from the database_connectsions variable
	public $current_method = null;
	protected $current_resource = null;
	protected $die_func = null;
	protected $last_error;

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

	public function getError()
	{
		$arr = new stdClass;
		$arr->error_type = $this->getConnectionType();
		$arr->last_error = $this->last_error;
		$arr->error_message = $this->error_message;
		return $arr;
	}

	public function getConnectionType()
	{
		if ($this->current_method instanceof SqlMysql) {
			return 'mysql';
		}
		elseif ($this->current_method instanceof SqlPostgresql) {
			return 'postgres';
		}
	}

	// a function that parses the dsn, allows either
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


	public function checkConnection()
	{
		return is_resource($this->current_resource);
	}


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
	}


	public function databaseConnections()
	{
		// return current database connections
	}


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


	public function switchDatabase( $database_name, $type = null )
	{
		if ( $database_name == '' ) {
			throw new SqlDatabaseException('Exception raised on switching to an undefined database');
		}
		else {
			$this->current_method->switchDatabase();
		}
	}



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



	public function switchConnection( $type = '', $host = '' )
	{
	}

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