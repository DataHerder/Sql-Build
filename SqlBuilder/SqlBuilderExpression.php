<?php
/**
 * Sql Wrapper File containing the wrapper for the Sql Builder Class
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
 * @package     Sql
 * @license     GNU license
 * @version     0.1
 * @link        my.public.repo
 * @since       File available since
 */


/**
* SqlBuilderExpression returns the Object itself
* with __toString() magic method that SqlBuilderSelect
* sniffs for.
* 
* @category Structured Query Language
* @package Sql
* @subpackage SqlBuilder
* @author Paul Carlton
* @link my.public.repo
 */
final class SqlBuilderExpression
{

	protected $type = null;
	protected $val = null;
	protected $alias = '';
	protected $as_alias = null;



	/**
	 * Construct type and val;
	 * 
	 * @access public
	 * @param string $type
	 * @param mixed $val
	 * @return null
	 */
	public function __construct( $type, $val = null, $as_alias = null ) {
		$this->type = strtoupper($type);
		$this->val = $val;
		$this->as_alias = $as_alias;
	}



	/**
	 * acquires the alias in the sql
	 * the SqlBuilderSelect class determines if there is an alias,
	 * there isn't much of a reason to set it yourself currently
	 * 
	 * @access public
	 * @param string $alias
	 * @return object $this
	 */
	public function alias( $alias ) {
		$this->alias = $alias;
		return $this;
	}



	/**
	 * formatVal is an internal function to short hand the __toString method
	 * 
	 * @access private
	 * @param string $val
	 * @return string
	 */
	private function formatVal( $val )
	{
		if ( strpos($val,"'")!== false || strpos($val,'`')=== false || is_numeric($val) ) {
			//it's a literal string
			return $val;
		}
		elseif ( strpos($val,'`')=== false ) {
			return '`'.$val.'`';
		}
		elseif ( strpos($val,'`')!== false ) {
			//it's a column
			$this->val = preg_replace("/`/",'',$this->val);
			if ( $this->alias != '' ) {
				return $this->alias . '.`' . $val . '`';
			}
			else {
				return '`' . $val . '`';
			}
		}
	}



	/**
	 * __toString magic method returns the mysql expression
	 * 
	 * @access public
	 * @param null
	 * @return string
	 */
	public function __toString() {
		if ( $this->as_alias != null ) {
			$as_alias = ' AS ' . $this->as_alias;
		}
		else { $as_alias = ''; }
		if ( is_string($this->val) ) {
			$val = $this->formatVal($this->val);
			return $this->type . "(".$this->val.")" . $as_alias;
		}
		elseif ( is_array($this->val) ) {
			$val = array();
			for( $i=0;$i<count($this->val); $i++ ) {
				$val[] = $this->formatVal($this->val);
			}
			return $this->type . "(".join(', ', $val).")" . $as_alias;
		}
		else {
			return $this->type . '()';
		}
	}
}

