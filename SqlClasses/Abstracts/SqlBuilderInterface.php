<?php 
namespace SqlBuilder\SqlClasses\Abstracts;


interface SqlBuilderInterface
{
	public function tableFormat($table);
	public function formatColumns( $column );
	public function formatValues( $value );
	public function isAssoc(array $array);
	public static function expr( $type, $val = null);
}
