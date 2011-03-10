<?php


class SqlBuilderWhereAbstract {
	
	protected $where = array();
	protected $joinThese = false;
	protected $joinWhere = array();
	protected $joinWhereType = '';


	/**
	 * where method, creates the where clause
	 * 
	 * @access public
	 * @param string $str
	 * @param mixed $q string array
	 * @return object $this
	 */
	public function where( $str=null, $q='-%skip%-' )
	{
		//print $str;
		$type = self::_sniffMyself();
		if (($type != 'select' && $type != 'delete' && $type != 'update') || (isSet($this->type) && $this->type == 'TRUNCATE')) {
			throw new SqlAbstractException('The WHERE clause is reserved only for SELECT, UPDATE, and DELETE statements');
		}
		if ( is_null($str) ) {
			$str = '1 = 1';
		}
		if ($q == '-%skip%-') {
			//do nothing
		}
		elseif ( !is_array($q) ) {
			if ( is_null($q) ) {
				//do nothing
				$q = 'NULL';
			}
			elseif ( is_string($q)){
				//xss eventually goes here
				//$q = "'" . $this->db->escape($q) . "'";
				$q = "'" . $q . "'";
			}
			$str = preg_replace('/\?/',$q,$str,1);
		}
		elseif ( is_array($q) ) {
			for ( $i=0; $i < count($q); $i++ ) {
				if ( !is_numeric($q[$i]) ) {
					//$q[$i] = "'" . $this->db->escape($q[$i]) . "'";
					$q[$i] = "'" . $q[$i] . "'"; //no escape for now
				}
				$str = preg_replace('/\?/',$q[$i],$str,1);
			}
		}
		else {
			throw new SqlAbstractException('Invalid inputs for where statement, requires null or string values');
		}
		if ( $this->joinThese === true ) {
			array_push($this->joinWhere, $str);
		}
		else {
			array_push($this->where,$str);
			//$this->where($str);
		}
		//var_dump($this->where);
		return $this;
	}




	/**
	 * orWhere, creates an orWhere clause instead of an and
	 * 
	 * @access public
	 * @param string $str
	 * @param mixed $q string array
	 * @return object $this
	 */
	public function orWhere( $str=null, $q='-%skip%-' ) {
		$this->where($str,$q);
		if ( $this->joinThese ) {
			$current_where = array_pop($this->joinWhere);
			$current_where = '||'.$current_where;
			array_push($this->joinWhere,$current_where);
			return $this;
		}
		$current_where = array_pop($this->where);
		$current_where = '||'.$current_where;
		array_push($this->where,$current_where);
		return $this;
	}





	/**
	 * joinThese is for the where clause by creating
	 * adjacent where's to join together in one larger
	 * where clause... ex:
	 * 
	 * ( ( ( (`column1` = 'data1') AND (`column2` = 'data2' ) ) OR (`column3` = 'data3' ) ) AND
	 * ( ( (`column4` = 'data4') AND (`column5` = 'data5' ) ) OR (`column6` = 'data6' ) ) )
	 * 
	 * @access public
	 * @param string $with
	 * @return object $this
	 */
	public function joinThese( $with = 'and' )
	{
		if ( $with != 'and' && $with != 'or' ) {
			throw new SqlBuilderSelectException('joinThese requires a where with statement (and|or)');
		}
		$this->joinThese = true;
		$this->joinWhereType = $with;
		return $this;
	}



	/**
	 * joinTheseFinish will concatenate joinThese
	 * 
	 * @access public
	 * @param string $type
	 * @return object $this
	 */
	public function joinTheseFinish( $type = 'and' )
	{
		if ( $this->joinThese === false ) return $this;
		$this->joinThese = false;
		$str = $this->buildWhere( $this->joinWhere );
		if ( $type == 'or' ) {
			$str = '||('.$str.')';
		}
		array_push($this->where,$str);
		return $this;
	}



	/**
	 * joinWhere joins the current array of wheres
	 * 
	 * @access public
	 * @param null
	 * @return object $this
	 */
	public function joinWhere()
	{
		if ( $this->joinThese === true ) {
			$wheres = $this->joinWhere;
			$str = $this->buildWhere($wheres);
			$this->joinWhere = array();
			$this->joinWhere[] = $str;
			return $this;
		}
		$str = $this->buildWhere();
		$this->where = array();
		$this->where[] = $str;
		return $this;
	}


	/**
	 * the point of this function is to constrain what certain
	 * public functions are allowable depending on the Class type
	 * for instance joins.  You can join on updates and selects
	 * but not inserts
	 * 
	 * @access protected
	 * @return string (select|update|insert|Expresssion|Delete)
	 */
	protected function _sniffMyself()
	{
		// sniff myself you know where
		// mmmm... smells good
		if ( $this instanceof SqlBuilderSelect ) {
			// this is a select object
			return "select";
		}
		elseif ( $this instanceof SqlBuilderUpdate ) {
			// this is an update object
			return 'update';
		}
		elseif ( $this instanceof SqlBuilderInsert ) {
			// this is an insert object
			return 'insert';
		}
		elseif ( $this instanceof SqlBuilderExpression ) {
			return 'expression';
		}
		elseif ( $this instanceof SqlBuilderDelete ) {
			return 'delete';
		}
	}





	/**
	 * buildWhere function builds the where for __toString() and joinThese function
	 * 
	 * @access protected
	 * @param string $replace
	 * @return string $where
	 */
	protected function buildWhere( $replace = null )
	{
		if ( empty($this->where) && $replace == null) {
			return null;
		}
		// this is for joining where statements together
		elseif (is_array($replace)) {
			$wheres = $replace;
		}
		else {
			$wheres = $this->where;
		}
		$where = '';
		for ($i=0; $i<count($wheres); $i++) {
			if ( substr($wheres[$i],0,2) == '||' ) {
				$wheres[$i] = substr($wheres[$i],2);
				$or = true;
			} else { $or = false; }
			if ( $where != '' && $or == true ) {
				$where .= ' OR ';
			} elseif ( $where != '' && $or == false ) {
				$where .= ' AND ';
			}
			$where .= '(' . $wheres[$i] . ')';
		}
		return $where;
	}

}