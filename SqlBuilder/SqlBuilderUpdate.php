<?php
/**
 * Sql Update File containing the class for update statements
 * 
 * 
 * Copyright (C) 2011  Paul Carlton
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @category    Structured Query Language
 * @package     SqlBuilder
 * @license     GNU license
 * @version     0.1
 * @link        my.public.repo
 * @since       File available since
 */




/**
 * SqlBuilderUpdate, constructs builder class
 * currently supports joins and complex where clauses
 * 
 * @category Structured Query Language
 * @package SqlBuilder
 * @subpackage Update
 * @author Paul Carlton
 * @link my.public.repo
 */
final class SqlBuilderUpdate extends SqlBuilderAbstract
{

	protected $sql;
	protected $table = '';
	protected $columns = array();
	protected $values = array();
	protected $where = '';
	protected $quoteInto = array();



	public function __construct($bootstrap = null)
	{
		parent::__construct($bootstrap);
	}


	public function __invoke( $table=null, array $args=array(), $where = null )
	{
		return $this->update($table, $args, $where);
	}




	/**
	 * update method
	 * 
	 * @access public
	 * @param string $table
	 * @param array $args
	 * @param array $where
	 * @return object $this
	 */
	public function update( $table=null, array $args=array(), $where=null )
	{
		if ( !is_string($table) ) {
			throw new SqlBuilderUpdateException('Update expects string for table');
		}
		if ( !is_array($args) && $this->isAssoc($args)) {
			throw new SqlBuilderUpdateException('Update requires fields to be an associative array');
		}
		$this->table = $table;

		$updates = array();
		$counter = 0;

		$this->columns = array_keys($args);
		$this->values = array_values($args);

		$where_clause = '';
		if ( is_array($where) ) {
			//it should have only two parameters
			if ( count($where)>2 ) {
				throw new SqlBuilderUpdateException('Parameter count for where exceeds two parameters, where = array("where statement ?", "quoteInto parameter").  Quote into parameter can also be an array');
			}
		}
		list( $this->where, $this->quoteInto ) = $where;

		return $this;
	}





	public function __toString()
	{
		//we need to check database api
		$field_type = $this->getFieldType();
		$str = 'UPDATE '.$this->tableFormat($this->table);
		$update = array();
		for ($i=0;$i<count($this->columns);$i++) {
			$update[] = $this->formatColumns($this->columns[$i]).' = '.$this->formatValues($this->values[$i]);
		}
		$str.= ' SET '.join(', ', $update);
		$where = $this->where;
		if (!empty($this->quoteInto)) {
			if (is_array($this->quoteInto)) {
				foreach ($this->quoteInto as $key => $value) {
					//be careful, accent grave at the beginning will recognize a database column rather than value
					if (strpos(trim($value), $field_type) || strpos(trim($value), '`')) {
						//this is a database column
						$value = $this->columnFormat($value);
					}
					else {
						$value = $this->formatValues($value);
					}
					$where = preg_replace('/\?/',$value,$where,1);
				}
			}
			else {
				$where_clause = preg_replace("/\?/", $this->quoteInto, $where);
				$where = $where_clause;
			}
		}
		if ($where != '') {
			$str.=' WHERE '.$where;
		}
		return $str;
	}

}

