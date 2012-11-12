<?php

namespace SqlBuilder\SqlClasses;

final class SqlBuilderRaw extends Abstracts\SqlBuilderAbstract {

	/**
	 *
	 */
	public function __construct($syntax = 'mysql', $bootstrap = null)
	{
		parent::__construct($bootstrap);
		$this->syntax = $syntax;
	}

}

