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
$Sql = new \SqlBuilder\Sql('mysql', 'host=localhost database=your_database user=root password=')
$Sql->setCharset('utf8');
$Sql->select('my_table', '*', "name = '?' AND age = ?", array('paul', 33));
```