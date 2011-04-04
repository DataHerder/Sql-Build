<?php


final class SqlBuilderBuildAlter extends SqlBuilderAbstract
{
	protected $table;
	protected $alter_type;
	protected $dbtype;
	protected $default_charset = 'UTF8';
	protected $mysql_engine = 'MyISAM';
	protected $schema;
	protected $columns = array();
	protected $defaults = array(
		'null' => false,
		'default' =>'EMPTY_STRING',
	);


	/**
 	* constructor
 	*/
	public function __construct( $dbtype = 'mysql', $bootstrap = null ) {
		parent::__construct($bootstrap);
		$this->dbtype = $dbtype;
	}


	public function build($table = null, $schema = null)
	{
		if ($table == null) {
			return false;
		}
		$this->schema = $schema;
		$this->table = $table;
		$this->alter_type = 'create';
		return $this;
	}






	public function addCol($column = null, $type = null, $args = array())
	{
		if ($column == null && $type == null) {
			//return false;
			throw new SqlBuilderBuildAlterException('Column and type is required to add a column.');
		}
		$this->_defaults($args);
		$this->columns[$column] = array('type'=>$type,'args'=>$args);
		return $this;
	}



	public function alterCol($column = null, $toWhat = null, $args = array())
	{
		if ($column==null || $toWhat == null) {
			throw new SqlBuilderBuildAlterException('Column and what it should be changed to is required for alterColumn()');
		}
		$this->_defaults($args);
		$this->alterColumn = array($column, $toWhat, $args);
		return $this;
	}


	public function checkTable($table_name = '', array $cols_to_check=array()) {
		if (empty($cols_to_check)) {
			return false;
		}
		$cols = $this->db->showColumns($table_name);
		$unmatched_cols = array();
		foreach($cols_to_check as $col) {
			if (!isSet($cols[$col])) {
				$unmatched_cols[] = $col;
			}
		}
		return $unmatched_cols;
	}


	public function getDataTypes(array $args)
	{
		$vals = array();
		foreach ($args as $key => $value) {
			$type = '';
			$len = 0;
			if (preg_match("/^\d+$/",trim($value))) {
				$value = (float)$value;
			}
			//first check date
			if ($value == '') {
				$type = 'varchar';
				$len = '255';
			}
			elseif (!is_float($value) && date('Y-m-d',strtotime($value))!==date('Y-m-d',strtotime('asldkfjas'))) {
				//probably a date
				$type = 'datetime';
				$value = date('Y-m-d H:i:s',strtotime($value));
			}
			elseif (is_string($value)) {
				$len = strlen($value);
				if ($len < 10) {
					$type = 'varchar';
					$len+= 20;
				}
				elseif ($len > 10) {
					$len*= 10;
					if ($len > 255) {
						$type = 'text';
						$len = 0;
					}
					else {
						$type = 'varchar';
					}
				}
			} elseif (is_float($value)) {
				if ($value > 1000000000) {
					$type = 'bigint';
				} else {
					$type = 'int';
				}
			}
			$vals[] = array($value,$type,$len);
		}
		return $vals;
	}



	protected function _defaults(array &$args)
	{
		foreach($this->defaults as $key=>$value) {
			if (!isSet($args[$key])) {
				$args[$key]=$value;
			}
		}
	}







	public function alter($table = null, $schema = null)
	{
		if (is_null($table)) {
			return $this;
		}
		$this->schema = $schema;
		$this->table = $table;
		$this->alter_type = 'alter';
		return $this;
	}






	public function engine($mysql_engine = 'MyISAM')
	{
		$this->mysql_engine = $mysql_engine;
	}






	public function collate($collate = 'UTF8')
	{
		$this->default_charset = $collate;
	}






	public function __toString()
	{
		if ($this->alter_type == 'alter') {
			return $this->createAlter();
		}
		elseif ($this->alter_type == 'create') {
			return $this->createTable();
		}
		else {
			return 'No alter type';
		}
	}


	protected function _col(array $args)
	{
		$str = ' ';
		if ($args['null'] === false) {
			$str.= 'NOT NULL ';
		}
		if ($args['default']=='EMPTY_STRING') {
			$str.=" DEFAULT '' ";
		}
		else {
			if (is_string($args['default'])) {
				$s = "'".$this->db->escape($args['default'])."'";
			} else {
				$s = $args['default'];
			}
			$str.="DEFAULT $s";
		}
		return $str;
	}




	protected function createAlter()
	{
		
	}





	protected function createTable()
	{
		$dbtype = $this->dbtype;
		$sql = 'CREATE TABLE ';
		if ($this->schema != null) {
			$sql.=$this->tableFormat($schema).'.';
		}
		$sql.=$this->tableFormat($this->table);
		$sql.="\n";
		$sql.='('."\n";
		//dbg_array($this->columns);
		$cols = array();
		foreach ($this->columns as $column_name => $vals) {
			if ($vals['type'] == 'serial') {
				if($this->dbtype=='mysql'){
					$cols[]="\t".$this->formatColumns($column_name)." INT UNIQUE AUTO_INCREMENT";
				} else {
					$cols[]="\t".$this->formatColumns($column_name)." {$vals['type']}";
				}
			} else {
				$cols[]="\t".$this->formatColumns($column_name)." {$vals['type']} ".$this->_col($vals['args']);
			}
		}
		$sql.=join(",\n", $cols)."\n".');'."\n";
		return '<pre>'.$sql.'</pre>';
	}


}


class SqlBuilderBuildAlterException extends Exception{}