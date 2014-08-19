# SQL Builder BETA
## An Object Based Database Query Library

### Documentation does not exist.  Documentation will be uploaded and hosted

### Version 0.9

### Significant Changes

* Increased Simplicity
* Quick Shorthands
* Embed mysql functions in select without calling SqlExpression

### Example Usage:
```php
// create the database connection
$Sql = new \SqlBuilder\Sql('mysql', 'host=localhost database=your_database user=root password=')
// set the charset to utf8
$Sql->setCharset('utf8');

try {
	// create the Sql query
	$Sql->select()
		->table('my_table')
		->fields('*')
		->where("name = '?' AND age = ?", array('paul', 33))
	;
	// check that it is correct
	print $Sql;
	// query
	$rows = $Sql->query();
	if (!empty($rows)) {
		foreach ($rows as $row) {
			// .. do something with the returned data
		}
	}
} catch (Exception $e) {
	print $e->getMessage();
}
```