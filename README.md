# Squeal PHP

A database library for SQLite.

## Installation

```sh
composer require joshmcrae/squeal
```

## Usage

Create a database object:

```php
<?php

use Squeal\Database;

// File
$db = Database::fromFile('path/to/database.db');

// In-memory (useful for testing or long-running processes)
$db = Database::inMemory();

```

Execute a query:

```php
<?php

// Positional parameters
$db->exec('select * from users where id = ?', ['123']);

// Named parameters
$db->exec('select * from users where id = :id', ['id' => '123']);

```

Conditional SQL fragments based on presence of named parameters:


```php
<?php

$db->exec('
    select  *
    from    users 
    where   active = 1
            [[and type < :type]]
            [[and created_at < :hour_ago]]
', [
    'hour_ago' => time() - 3600
]);

// select * from users where active = 1 and created_at < :hour_ago

```

Query results:

```php
$results = $db->exec('select * from users');

// Fetch a single record
$results->one();

// Fetch remaining records
$results->all();

// Map remaining results through transformer function
$users = $results->map(fn (array $row) => new User($row['id'], $row['email'], $row['name']));
```

Query builder:

```php
$results = $db
    ->table('users')
    ->where('active', eq: 1)
    ->where('created_at', lt: time() - 3600)
    ->select(['id', 'email', 'name']);

// Insert record
$db
    ->table('users')
    ->insert([
        'id' => 2,
        'email' => 'john.doe@example.com',
        'name' => 'John Doe',
        'created_at' => time()
    ]);

// Update records
$db
    ->table('users')
    ->where('id', eq: 2)
    ->update([
        'email' => 'jane.doe@example.com',
        'name' => 'Jane Doe',
        'updated_at' => time()
    ]);

// Delete records
$db
    ->table('users')
    ->where('active', eq: 0)
    ->delete();
```
