<?php

class SqlPostgresql extends SqlConnectionAbstract
{

	protected $current_resource;
	protected $last_error;

	public function __construct()
	{
		// parent::__construct();
	}


	public function connect( $dsn )
	{
		// dsn is checked here, if the pertinent information has not been met
		$args = $this->validateDsn($this->parseDsn($dsn));
		// we need the port for pg
		if (!isSet($args['port'])) {
			throw new SqlDatabaseException('Port is expected for postgres connection');
		}
		if (!is_string($dsn)) {
			$dsn = $this->pg_dsn($args);
		}
		$plink = @pg_connect($dsn);
		return $plink; // return the resource to be allocated to the Database wrapper
	}


	private function pg_dsn($args)
	{
		$dsn = "host={$args['server']} port={$args['port']} dbname={$args['dbname']} user={$args['username']} password={$args['password']}";
		if (isSet($args['options'])){
			$dsn.= " options=".$args['options'];
		}
		return $dsn;
	}


	public function execute( $sql, $resource )
	{
		if (is_string($sql)) {
			$stmp = @pg_query($resource, $sql);
		}
		else {
			throw new SqlDatabaseException('Unknown Sql Type sent to database');
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


	public function serverStatus($resource) {}


	public function showTables($resource)
	{
		return false;
	}
	public function showColumns( $table, $resource )
	{
		$set = pg_query('SELECT * FROM "'.$table.'" LIMIT 1');
		return array_keys(pg_fetch_assoc($set));
	}
	public function database( $db, $resource ) {}

	protected function errorOut()
	{
		//$this->last_error = pg_last_error();
		if (is_callable($this->die_func)) {
			$this->die_func(pg_last_error());
		}
		throw new SqlDatabaseError('There was a problem with the request;' . pg_last_error());
	}

	public function escape( $str )
	{
		return pg_escape_string($str);
	}

	public function formatColumn( $column )
	{
		$column = $this->stripColumns($column);
		$column = preg_replace('/"/', '', $column);
		return '"' . $column . '"';
	}
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