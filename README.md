# SQL Builder BETA
## An OOP Approach to Queries
### Version 0.0.9

### Setting up Connection
```php
// order does not matter
$Sql = new \SqlBuilder\Sql('mysqli', 'host=your_host database=your_db user=your_user password=your_password', 'charset');
```
OR
```php
$Sql = new \SqlBuilder\Sql('mysqli', array(
	'host' => 'your_host',
	'database' => 'your_db',
	'user' => 'your_user',
	'password' => 'your_password',
	'charset' => 'utf8',
));
```
OR
```php
// order matters
$Sql = new \SqlBuilder\Sql('mysqli', 'your_host', 'your_user', 'your_password', 'your_db', 'charset');
```

OR
```php
// Constructing the class does not give you hints from IDE because __construct() method is looking
// for arguments dynamically, so this approach gives you hints in an IDE that supports comment docs
// returns an instance of itself
$Sql = \SqlBuilder\Sql::dsn('mysqli', 'your_host', 'your_user', 'your_password', 'your_db', 'charset');
```

Example:
```php
$Sql = new \SqlBuilder\Sql('mysqli', 'localhost', 'root', '[password]', 'test', 'utf8');
```

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
		->where("name = '?' AND age = ?", array('paul', 4000000000))
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
