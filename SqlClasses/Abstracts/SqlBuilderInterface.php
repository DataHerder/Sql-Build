<?php 
namespace SqlBuilder\SqlClasses\Abstracts;


interface SqlBuilderInterface
{
	public function tableFormat($table);
	public function formatColumns($column);
	public function formatValues($value = null);
	public function isAssoc(array $array);
	public static function expr($type, $val = null);
}
