<?php

namespace Squeal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Builder::class)]
class BuilderTest extends TestCase
{
    private Database $db;

    public function setUp(): void
    {
        $this->db = Database::inMemory();

        $this
            ->db
            ->exec('create table if not exists users (id text, email text, name text)');

        $this->db->logQueries = true;
    }

    public function testSelect()
    {
        $b = new Builder($this->db, 'users');

        $b
            ->where('email', eq: 'john.doe@example.com')
            ->select(['email', 'name']);

        $this->assertEquals('select email, name from users where email = ?', $this->db->log[0]);
    }

    public function testInsert()
    {
        $b = new Builder($this->db, 'users');

        $b->insert([
            'id' => 1,
            'email' => 'john.doe@example.com',
            'name' => 'John Doe'
        ]);

        $this->assertEquals('insert into users (id, email, name) values (?, ?, ?)', $this->db->log[0]);
    }

    public function testUpdate()
    {
        $b = new Builder($this->db, 'users');

        $b
            ->where('id', eq: 2)
            ->update([
                'email' => 'jane.doe@example.com',
                'name' => 'Jane Doe'
            ]);

        $this->assertEquals('update users set email = ?, name = ? where id = ?', $this->db->log[0]);
    }

    public function testDelete()
    {
        $b = new Builder($this->db, 'users');

        $b
            ->where('id', neq: 2)
            ->delete();

        $this->assertEquals('delete from users where id != ?', $this->db->log[0]);
    }
}
