<?php 
/**
 * Sql Insert File containing the class for insert statements
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
 * @package     Sql
 * @license     GNU license
 * @version     0.1
 * @link        my.public.repo
 * @since       File available since 0.1
 */



/**
* SqlBuilderInsert creates and constructs an insert sql
* 
* @category Structured Query Language
* @package SqlBuilder
* @subpackage Insert
* @author Paul Carlton
* @link my.public.repo
 */
final class SqlBuilderInsert extends SqlBuilderAbstract
{
	protected $sql;


	protected $columns = array();
	protected $values_ = array();


	/**
	 * Constructer
	 */
	public function __construct($bootstrap = null)
	{
		parent::__construct($bootstrap);
	}


	public function __invoke( $table, $values ) {
		return $this->insert($table, $values);
	}



	/**
	 * insert function
	 * 
	 * @access public
	 * @param string $table
	 * @param array $values
	 * @return object $this
	 */
	public function insert( $table, $values )
	{
		//table MUST BE strings
		if ( ! is_string($table) ) {
			throw new SqlBuilderInsertException('Table needs to be a string');
		}
		if ( ! is_array($values) ) {
			throw new SqlBuilderInsertException('Insert columns and values must be an array');
		}
		$this->table = preg_replace('/`|"/','',$table);

		//only one row
		if ( $this->isAssoc($values) ) {
			$this->columns = array_keys($values);
			$this->values_ = array_values($values);
		}
		elseif ( is_array($values) ) {
			// this is a multi row insert
			// first get the columns
			if ( !$this->isAssoc($values[0]) ) {
				throw new SqlBuilderInsertException('For multi row insert, each row requires an associative array');
			}
			$this->columns = array_keys($values[0]);
			for ( $i=0; $i<count($values); $i++ ) {
				if ( !$this->isAssoc($values[$i]) ) {
					throw new SqlBuilderInsertException('For multi row insert, each row requires an associative array');
				}
				else {
					$this->values_[] = array_values($values[$i]);
				}
				//array_push($values__, join(', ',$values_));
			}
		}
		//$this->sql = $sql;
		return $this;
	}




	/**
	 * __toString()
	 */
	public function __toString()
	{
		$sql = "INSERT INTO ";
		$sql.= $this->tableFormat($this->table)." ";
		$values_ = array();
		$columns = array();
		for($i=0;$i<count($this->columns);$i++) {
			$columns[] = $this->formatColumns($this->columns[$i]);
		}
		$values = array();
		$m = false;
		for($i=0;$i<count($this->values_);$i++) {
			if (is_array($this->values_[$i])) {
				$m = true;
				//multi row insert
				$values[$i] = '(';
				$v = array();
				for($c=0;$c<count($this->values_[$i]);$c++){
					$v[] = $this->formatValues($this->values_[$i][$c]);
				}
				$values[$i].= join(', ', $v) . ')';
			}
			else {
				$values[] = $this->formatValues($this->values_[$i]);
			}
		}
		if (!$m) {
			$val = '('.join(', ', $values).')';
		} else { $val = join(', ', $values); }

		$sql.= '(' . join(', ', $columns) . ') VALUES ' . $val . "\n";
		return $sql;
	}

}
