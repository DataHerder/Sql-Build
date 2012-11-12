<?php

/**
 * This file only serves as an example!
 * DO NOT TOUCH THIS FILE IF YOU WANT TO KEEP AN UPDATED REPO
 *
 * If you are copying over and wish to keep the bootstrap in this file
 * then by all means, the choice is yours.  I suggest you create another
 * bootstrap AFTER requiring Sql.php, just like the one below
 */
namespace SqlBuilder\SqlBootstrap;

/**
 * Example class of a bootstrap with all the possible ways you can create database connections
 * on startup
 *
 * You do not have to use the bootstrap - look at the documentation for the database API
 */
class SqlBootstrap extends SqlBootstrapAbstract {
	protected function _init()
	{
		// your dsn if this is called during _init, your class will be instantiated, already
		// setup with your database, you won't need to call
		// $sql->setup($dsn);
		//$pg_dsn = 'host=localhost port=5433 dbname=template1 user=postgres password=[PASSWORD] options=\'--client_encoding=UTF8\'';
		//$my_dsn = 'host=localhost dbname=[DBNAME] user=[USER] password=[PASSWORD]';
		/*$pg_dsn1 = array(
			'host'=>'localhost',
			'port'=>'5433',
			'dbname'=>'infrastructure',
			'user'=>'postgres',
			'password'=>'',
			'options'=>"'--client_encoding=UTF8'"
		);*/
		// either one or array of connections
		//$dsn = array('mysql'=>array($my_dsn)); //, 'mysql'=>array($my_dsn));
		//$this->databaseSetup($dsn);
		//this preformats tables
		/*$this->preformatTable(function( $table ) {
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
		});*/
	}
}