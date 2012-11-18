<?php

namespace SqlBuilder\SqlClasses;

final class SqlBuilderRaw extends Abstracts\SqlBuilderAbstract {

	protected $sql = '';

	/**
	 *
	 */
	public function __construct($syntax = 'mysql', $bootstrap = null)
	{
		parent::__construct($bootstrap);
		$this->syntax = $syntax;
	}

	public function __invoke($sql)
	{
		$this->sql = $sql;
	}

	public function __toString()
	{
		return $this->sql;
	}


}

