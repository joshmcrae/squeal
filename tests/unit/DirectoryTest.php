<?php

namespace Squeal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Directory::class)]
class DirectoryTest extends TestCase
{
    private Database $db;
    private Directory $dir;

    public function setUp(): void
    {
        $this->db = Database::inMemory();
        $this->db->logQueries = true;

        $this->dir = new Directory(
            $this->db,
            __DIR__ . '/../fixtures',
            '.sql'
        );
    }

    public function testList()
    {
        $this->assertEquals(
            [
                '01_create_users_table',
                '02_create_cache_table'
            ],
            $this->dir->list()
        );
    }

    public function testExec()
    {
        $this->dir->exec('02_create_cache_table');

        $this->assertStringStartsWith(
            'create table cache',
            $this->db->log[0]
        );
    }
}
