<?php

namespace Squeal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Database::class)]
class DatabaseTest extends TestCase
{
    public function testFromFile()
    {
        $db = Database::fromFile(__DIR__ . '/test.db');

        $this->assertInstanceOf(Database::class, $db);
        $this->assertFileExists(__DIR__ . '/test.db');

        unlink(__DIR__ . '/test.db');
    }

    public function testInMemory()
    {
        $db = Database::inMemory();

        $this->assertInstanceOf(Database::class, $db);
    }

    public function testExecWithPositionalParams()
    {
        $db = Database::inMemory();
        $result = $db->exec('select ? as number', [42]);

        $this->assertEquals(['number' => 42], $result->one());
    }

    public function testExecWithNamedParams()
    {
        $db = Database::inMemory();
        $result = $db->exec('select :arg as number', ['arg' => 42]);

        $this->assertEquals(['number' => 42], $result->one());
    }

    public function testExecWithConditionalNamedParams()
    {
        $db = Database::inMemory();
        $db->logQueries = true;

        $result = $db->exec('select :arg as number [[where 1 = :two]]', ['arg' => 42, 'two' => 2]);

        $this->assertEquals('select :arg as number where 1 = :two', $db->log[0]);
        $this->assertEmpty($result->all());
    }

    public function testLastInsertId()
    {
        $db = Database::inMemory();
        $db->exec('create table users (id integer primary key autoincrement, name text)');

        $result[] = $db->exec('insert into users (name) values (?)', ['John Doe']);
        $result[] = $db->exec('insert into users (name) values (?)', ['Jane Doe']);

        $this->assertEquals(1, $result[0]->lastInsertId);
        $this->assertEquals(2, $result[1]->lastInsertId);
    }

    public function testTable()
    {
        $db = Database::inMemory();
        $result = $db->table('users');

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testDirectory()
    {
        $db = Database::inMemory();
        $dir = $db->directory(__DIR__ . '/../fixtures/');

        $this->assertInstanceOf(Directory::class, $dir);
    }
}
