<?php
namespace SqlBuilder\SqlBootstrap;

abstract class SqlBuilderBootstrapAbstract {

	protected $_preformatTableFunc = null;
	protected $_preformatTableVar = false;
	protected $_database_setup = array();
	protected $_query_method = null;



	public function __construct()
	{
		if (method_exists($this, '_init')) {
			$this->_init();
		}
	}

	protected function databaseSetup( $dsn = '', $extra = '' )
	{
		if (is_string($dsn)) {
			//it's one instantiation
			$this->_database_setup[] = $dsn;
			$this->_database_setup[] = $extra;
		}
		elseif (is_array($dsn)) {
			$this->_database_setup[] = 'many';
			$this->_database_setup[] = $dsn;
		}
	}


	public function load($name = ''){
		if (method_exists($this, $name)) {
			return call_user_func(array($this, $name));
		}
		else {
			if ($name == '_init') {
				$message = '_init method required onload with bootstrap.  Ensure protected function _init() is created in your bootstrap.  It can be empty.';
			}
			else { $message = 'Method "'. $name . '" not found in bootstrap'; }
			throw new SqlAbstractBootstrapException($message);
		}
	}
	protected function dsn()
	{
		return $this->_database_setup;
	}



	protected function preformatTable( $function )
	{
		$this->_preformatTableFunc = $function;
		return true;
	}


	protected function ci_plugin( CI_Model $ci_model )
	{
		
	}

	public function getPreformatTableFunc()
	{
		return $this->_preformatTableFunc;
	}
}

