<?php
/**
 * Sql Interface File
 *
 *
 * Copyright (C) 2012  Paul Carlton
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
 * @link        https://github.com/DataHerder/Sql-Build
 */

namespace SqlBuilder\SqlClasses\Abstracts;

/**
 * Simple interface requiring Sql classes to have these functions
 */
interface SqlBuilderInterface
{
	public function tableFormat($table);
	public function formatColumns($column);
	public function formatValues($value = null);
	public function isAssoc(array $array);
	public static function expr($type, $val = null);
}
