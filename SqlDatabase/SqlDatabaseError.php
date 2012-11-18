<?php
/**
 * Sql Database Error file
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


namespace SqlBuilder\SqlDatabase;

/**
 * Class that holds the errors from the database
 *
 * @package SqlDatabaseError
 */
class SqlDatabaseError {
	/**
	 * The errors returned
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Error type
	 *
	 * @var string
	 */
	public $error_type = '';

	/**
	 * The last error
	 *
	 * @var string
	 */
	public $last_error = '';

	/**
	 * Last error message
	 *
	 * @var string
	 */
	public $error_message = '';

	/**
	 * Generic constructor
	 */
	public function __construct() { }

	/**
	 * Set the current key value
	 *
	 * @param $key
	 * @param $value
	 */
	public function __set($key, $value) {
		eval('$this->'.$key.' = "'.preg_replace("/\"/",'\\"',$value).'";');
	}

	/**
	 * Get the current value
	 *
	 * @param $key
	 * @return string
	 */
	public function __get($key) {
		$var = '';
		eval('$var = $this->'.$key.';');
		return $var;
	}

	/**
	 * To string the error
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->error_message.': '.$this->last_error;
	}
}