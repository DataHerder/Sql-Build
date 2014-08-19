<?php

namespace SqlBuilder\Statements;

abstract class SqlBuilderAbstract {

	protected $config = array();
	public function injectConfig(array $config)
	{
		$this->config = array_merge($this->config, $config);
	}

}