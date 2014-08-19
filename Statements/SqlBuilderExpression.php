<?php

namespace SqlBuilder\Statements;


class SqlBuilderExpression {

	private $escaped_expression = '';

	private function __construct($escape_as_literal)
	{
		$this->escaped_expression = $escape_as_literal;
	}

	private static $escape_string = '';

	public static function escape($escape_as_literal = '')
	{
		if ($escape_as_literal == '') {
			throw new SqlBuilderExpressionException('Escaped literal expression is an empty string');
		}
		self::$escape_string = $escape_as_literal;
	}

	public static function e($escape_as_literal = '')
	{
		self::escape($escape_as_literal);
		return self::getSingleton();
	}

	public static function getSingleton()
	{
		$var = __CLASS__;
		$class = new $var(self::$escape_string);
		return $class;
	}

	public function __toString()
	{
		return $this->escaped_expression;
	}
}


class SqlBuilderExpressionException extends \Exception {}