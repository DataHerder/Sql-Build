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

namespace SqlBuilder\SqlClasses;
use \SqlBuilder\SqlClasses\Abstracts\SqlBuilderAbstract as SqlBuilderAbstract;
use \SqlBuilder\SqlClasses\Exceptions\SqlBuilderInsertException as SqlBuilderInsertException;

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
	private $sql;


	private $columns = array();
	private $values = array();
	private $dupeOn = null;


	/**
	 * Construct the insert statement
	 *
	 * This is handled by Sql
	 *
	 * @param string $syntax
	 * @param null $bootstrap
	 */
	public function __construct($syntax='mysql', $bootstrap = null)
	{
		parent::__construct($bootstrap);
		$this->syntax = $syntax;
	}


	/**
	 * Invoke for the insert function
	 *
	 * @deprecated invokes on other objects than
	 *   selects is a bit frivolous and harder to read
	 * @param $table
	 * @param $values
	 * @return object
	 */
	public function __invoke( $table, $values ) {
		return $this->insert($table, $values);
	}


	/**
	 * insert function
	 *
	 * @access public
	 * @param string $table
	 * @param array $values
	 * @throws Exceptions\SqlBuilderInsertException
	 * @return object $this
	 */
	public function insert( $table = null, $values = null )
	{
		if (is_null($table)) {
			return $this;
		}
		//table MUST BE strings
		if ( ! is_string($table) ) {
			throw new SqlBuilderInsertException('Table needs to be a string');
		}
		if ( ! is_array($values) ) {
			throw new SqlBuilderInsertException('Insert columns and values must be an array');
		}

		// reset
		$this->columns = array();
		$this->values = array();
		$this->dupeOn = null;


		$this->table = preg_replace('/`|"/','',$table);

		//only one row
		if ( $this->isAssoc($values) ) {
			$this->columns = array_keys($values);
			$this->values = array_values($values);
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
					$this->values[] = array_values($values[$i]);
				}
			}
		}
		return $this;
	}


	/**
	 * Reading from a csvFile
	 *
	 * Currently unavailable to later release
	 *
	 * @param $file
	 * @return array
	 * @throws Exceptions\SqlBuilderInsertException
	 */
	private function getCsv( $file )
	{
		$handle = fopen($file,'r');
		if (!$handle) {
			throw new SqlBuilderInsertException('CSV file unreadable');
		}
		$row = 1;
		$cols = array();
		$vals = array();
		while (($data = fgetcsv($handle)) !== false) {
			if ($row == 1) {
				$cols = $data;
			}
			else {
				if (!isSet($i)) {
					$i=0;
				}
				for ($j=0;$j<count($data);$j++) {
					$vals[$i][$cols[$j]] = $data[$j];
				}
				$i++;
			}
			$row++;
		}
		fclose($handle);
		return array('columns'=>$cols,'values'=>$vals);
	}


	/**
	 * On duplicate key update signature
	 *
	 * @param string $set
	 * @return SqlBuilderInsert
	 * @throws Exceptions\SqlBuilderInsertException
	 */
	public function onDuplicateKey($set = '')
	{
		if ($set == '') {
			throw new SqlBuilderInsertException("ON DUPLICATE KEY needs an UPDATE statement");
		}
		$this->dupeOn = $set;
		return $this;
	}




	/**
	 * __toString()
	 */
	public function __toString()
	{
		$sql = "INSERT INTO ";
		$sql.= $this->tableFormat($this->table)." ";
		$columns = array();
		for($i=0;$i<count($this->columns);$i++) {
			$columns[] = $this->formatColumns($this->columns[$i]);
		}
		$values = array();
		$m = false;
		for($i=0;$i<count($this->values);$i++) {
			if (is_array($this->values[$i])) {
				$m = true;
				//multi row insert
				$values[$i] = '(';
				$v = array();
				for($c=0;$c<count($this->values[$i]);$c++){
					$v[] = $this->formatValues($this->values[$i][$c]);
				}
				$values[$i].= join(', ', $v) . ')';
			}
			else {
				$values[] = $this->formatValues($this->values[$i]);
			}
		}
		if (!$m) {
			$val = '('.join(', ', $values).')';
		} else { $val = join(', ', $values); }

		$sql.= '(' . join(', ', $columns) . ') VALUES ' . $val . "\n";
		if (is_string($this->dupeOn) && $this->dupeOn != '') {
			$sql.="	ON DUPLICATE KEY UPDATE ".$this->dupeOn;
		}
		return $sql;
	}

}
