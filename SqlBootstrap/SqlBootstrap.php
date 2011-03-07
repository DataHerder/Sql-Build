<?php


class SqlBootstrap extends SqlBuilderBootstrapAbstract {
	protected function _init()
	{
		// your dsn if this is called during _init, your class will be instantiated, already
		// setup with your database, you won't need to call
		// $sql->setup($dsn);
		//$pg_dsn = 'host=localhost port=5433 dbname=template1 user=postgres password=[PASSWORD] options=\'--client_encoding=UTF8\'';
		$my_dsn = 'host=localhost dbname=DBNAME user=root password=';
		$pg_dsn = array(
			'host'=>'localhost',
			'port'=>'5433',
			'dbname'=>'template1',
			'user'=>'postgres',
			'password'=>'[PASSWORD]',
			'options'=>"'--client_encoding=UTF8'"
		);
		// either one or array of connections
		$dsn = array('postgres'=>array($pg_dsn), 'mysql'=>array($my_dsn));
		$this->databaseSetup($dsn);
		//this preformats tables
		$this->preformatTable(function( $table ) {
			$v = substr($table,0,1);
			if ( $v == '/' ) {
				return '_framework_' . substr($table,1);
			}
			elseif ( $v == '.' ) {
				return '_business_' . substr($table,1);
			}
			elseif ( $v == '-' ) {
				return '_somethingelse_' . substr($table,1);
			}
			else {
				return $table;
			}
		});
	}
}