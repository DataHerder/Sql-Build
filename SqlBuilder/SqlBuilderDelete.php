<?php
/**
 * Sql Delete File containing the class for delete/truncate statements
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
 * SqlBuilderDelete, constructs builder class
 * currently supports joins and complex where clauses
 * 
 * @category Structured Query Language
 * @package SqlBuilder
 * @subpackage Delete
 * @author Paul Carlton
 * @link my.public.repo
 */
final class SqlBuilderDelete extends SqlBuilderAbstract
{
	protected $sql;
	protected $table = '';
	protected $type = 'DELETE';

	public function __construct($bootstrap = null) {
		parent::__construct($bootstrap);
	}


	public function __invoke( $table=null, $where=null )
	{
		//the __invoke assumes you want a delete statement
		$this->delete($table, $where);
		return $this;
	}

	public function truncate( $table = null )
	{
		$this->type = 'TRUNCATE';
		if ( ! is_string($table) ) {
			throw new Exception('TRUNCATE expects string as table.');
		}
		$this->table = $table;
		return $this;
	}
	public function delete( $table = null, $where = null )
	{
		if ( ! is_string($table) ) {
			throw new Exception('DELETE expects string as table.');
		}
		elseif ( ! is_string($where) && $where != null ) {
			throw new Exception('DELETE expects WHERE statement to be a string or null value.');
		}
		$this->table = $table;
		//$this->where = $where;
		if (!is_null($where)) {
			$this->where($where);
		}
		return $this;
	}

	public function __toString()
	{
		if ($this->type == 'TRUNCATE') {
			return 'TRUNCATE '.$this->tableFormat($this->table);
		}
		else {
			$sql= "DELETE FROM ".$this->tableFormat($this->table);
			$where = $this->buildWhere();
			if ($this->where != null && $this->where != ''){
				$sql.= ' WHERE '. $where;
			}
			return $sql;
		}
	}

	public function __destruct()
	{
		$this->joins = array();
		$this->where = array();
	}
}