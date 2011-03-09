<?php 


abstract class SqlBuilderJoinsAbstract extends SqlBuilderWhereAbstract {


	protected $joins = array();



	/**
	 * allJoins is a protected function that acts as the wrapper for 
	 * tableJoin, that way all joins go through the same function
	 * to reduce code
	 * 
	 * @access protected
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function allJoins( $table=null, $on=null, $type )
	{
		return $this->tableJoin($table, $on, $type);
	}


	/**
	 * This function sniffs for type and throws exceptions if
	 * data is not presented correctly for the join
	 * 
	 * @access protected
	 * @param string $table
	 * @param string $on
	 * @param string $type
	 * @return object $this
	 */
	protected function tableJoin($table, $on, $type)
	{
		//$this->checkForJoins();
		if (!is_string($table) && !$this->isAssoc($table)) {
			throw new SqlAbstractException('String or associative array (alias) expected for table in '.$type.'Join');
		}
		if (! is_string($on) ) {
			throw new SqlAbstractException($type . ' JOIN clause ON expects string.');
		}
		$table = $table;
		$this->joins[] = array($type,$table,$on);
		return $this;
	}





	/**
	 * inner join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function innerJoin( $table=null, $on=null )
	{
		//dbg_array($this);
		return $this->allJoins($table, $on, 'inner');
	}




	/**
	 * outer join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function outerJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'outer');
	}
	




	/**
	 * left join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function leftJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'left');
	}
	



	/**
	 * right join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function rightJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'right');
	}
	



	/**
	 * join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function join( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, '');
	}




	/**
	 * full join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function fullJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'full');
	}




	/**
	 * natural join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function naturalJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'natural');
	}



	/**
	 * natural join to join wrapper
	 * 
	 * @access public
	 * @param string $table
	 * @param string on
	 * @return object $this
	 */
	public function crossJoin( $table=null, $on=null )
	{
		return $this->allJoins($table, $on, 'cross');
	}


	protected function buildJoins()
	{
		$join_string = '';
		if ( !empty($this->joins) ) {
			for ( $i=0; $i<count($this->joins); $i++ ) {
				$join = $this->joins[$i];
				if (is_array($join[1])) {
					$a = key($join[1]);
					$join[1] = $this->tableFormat($join[1][$a]).' AS '.$a;
				}
				$table_join = trim($join[1]);
				if (strpos($table_join, ' ')!==false){
					$tmp = explode(' ', $table_join);
					if(count($tmp)>2) {
						$table_join = $this->tableFormat($tmp[0]) . ' AS ' . $tmp[2];
					}
					elseif (count($tmp)==2) {
						$table_join = $this->tableFormat($tmp[0]) . ' AS ' . $tmp[1];
					}
					else {
						$table_join = $this->tableFormat($tmp[0]);
					}
				} else {
					$table_join = $this->tableFormat($table_join);
				}
				if ( $join[0] == 'join' ) {
					$join_string.= ' JOIN '.$table_join.' ON '.$join[2];
				}
				else {
					$join_string.= ' ' . strtoupper($join[0]). ' JOIN ' . $table_join . ' ON ' . $join[2];
				}
			}
		}
		return $join_string;
	}
}
