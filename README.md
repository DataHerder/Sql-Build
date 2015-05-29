# SQL Builder BETA
## An OOP Approach to Queries
### Version 0.0.9

### Example Usage:
```php
// create the database connection
$Sql = new \SqlBuilder\Sql('mysqli', 'host=localhost database=your_database user=root password=')
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

# NOTE that the above select query can also be written like this as a shorthand:
$Sql->select('my_table', '*', "name = '?' AND age = ?", array('paul', '4000000000'));
```
