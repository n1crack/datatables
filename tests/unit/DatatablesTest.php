<?php

namespace Ozdemir\Datatables\Test;

use Ozdemir\Datatables\DB\SQLite;
use Ozdemir\Datatables\Datatables;
use PHPUnit\Framework\TestCase;

class DatatablesTest extends TestCase
{

    protected $db;

    private function customfunction($data)
    {
        return substr($data, 0, 3) . '...';
    }

    public function setUp()
    {
        $sqlconfig = __DIR__ . '/../fixtures/test.db';

        $this->db = new Datatables(new SQLite($sqlconfig));
    }

    public function tearDown()
    {
        unset($this->db);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Datatables::class, $this->db);
    }

    public function testReturnsRecordCounts()
    {
        $this->db->query("select id as fid, name, surname, age from mytable where id > 3");
        $datatables = $this->db->generate(false);

        $this->assertSame(8, $datatables['recordsTotal']);
        $this->assertSame(8, $datatables['recordsFiltered']);
    }

    public function testReturnsDataFromABasicSql()
    {
        $this->db->query("select id as fid, name, surname, age from mytable");

        $data = $this->db->generate(false)['data'][0];

        $this->assertSame("1", $data['fid']);
        $this->assertSame("John", $data['name']);
        $this->assertContains('Doe', $data['surname']);
    }

    public function testSetsColumnNamesFromAliases()
    {
        $this->db->query("select
                  film_id as fid,
                  title,
                  'description' as info,
                  release_year 'r_year',
                  film.rental_rate,
                  film.length as mins
            from film");

        $this->assertSame(['fid', 'title', 'info', 'r_year', 'rental_rate', 'mins'], $this->db->get('all_columns'));
    }

    public function testHidesUnnecessaryColumnsFromOutput()
    {
        $this->db->query("select id as fid, name, surname, age from mytable");
        $this->db->hide('fid');
        $data = $this->db->generate(false)['data']['2'];

        $this->assertCount(3, $data);
        $this->assertSame(['name', 'surname', 'age'], $this->db->get('columns'));
    }

    public function testReturnsModifiedDataViaClosureFunction()
    {
        $this->db->query("select id as fid, name, surname, age from mytable");

        $this->db->edit('name', function ($data) {
            return strtolower($data['name']);
        });

        $this->db->edit('surname', function ($data) {
            return $this->customfunction($data['surname']);
        });

        $data = $this->db->generate(false)['data']['2'];

        $this->assertSame("george", $data['name']);
        $this->assertSame("Mar...", $data['surname']);
    }

    public function testReturnsColumnNamesFromQueryThatIncludesASubqueryInSelectStatement()
    {
        $dt = $this->db->query("SELECT column_name,
            (SELECT group_concat(cp.GRANTEE)
            FROM COLUMN_PRIVILEGES cp
            WHERE cp.TABLE_SCHEMA = COLUMNS.TABLE_SCHEMA
            AND cp.TABLE_NAME = COLUMNS.TABLE_NAME
            AND cp.COLUMN_NAME = COLUMNS.COLUMN_NAME)
            privs
            FROM COLUMNS
            WHERE table_schema = 'mysql' AND table_name = 'user';");

        $this->assertSame(['column_name', 'privs'], $dt->get('columns'));
    }

    public function testReturnsColumnNamesFromQueryThatIncludesASubqueryInWhereStatement()
    {
        $dt = $this->db->query("SELECT column_name
            FROM COLUMNS
            WHERE table_schema = 'mysql' AND table_name = 'user'
            and (SELECT group_concat(cp.GRANTEE)
            FROM COLUMN_PRIVILEGES cp
            WHERE cp.TABLE_SCHEMA = COLUMNS.TABLE_SCHEMA
            AND cp.TABLE_NAME = COLUMNS.TABLE_NAME
            AND cp.COLUMN_NAME = COLUMNS.COLUMN_NAME) is not null;");
        $columns = $dt->get('columns');

        $this->assertSame($columns[0], 'column_name');
    }
}
