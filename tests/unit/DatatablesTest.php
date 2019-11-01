<?php

namespace Ozdemir\Datatables\Test;

use Ozdemir\Datatables\DB\SQLite;
use Ozdemir\Datatables\Datatables;
use PHPUnit\Framework\TestCase;
use Ozdemir\Datatables\Http\Request;

class DatatablesTest extends TestCase
{

    protected $db;
    protected $request;

    private function customfunction($data)
    {
        return substr($data, 0, 3).'...';
    }

    public function setUp(): void
    {
        $sqlconfig = __DIR__.'/../fixtures/test.db';
        $this->request = Request::create(array(), ['draw' => 1]);

        $this->db = new Datatables(new SQLite($sqlconfig), $this->request);
    }

    public function tearDown(): void
    {
        unset($this->db);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Datatables::class, $this->db);
    }

    public function testReturnsRecordCounts()
    {
        $this->db->query('select id as fid, name, surname, age from mytable where id > 3');
        $datatables = $this->db->generate()->toArray();

        $this->assertSame(8, $datatables['recordsTotal']);
        $this->assertSame(8, $datatables['recordsFiltered']);
    }

    public function testReturnsDataFromABasicSql()
    {
        $this->db->query('select id as fid, name, surname, age from mytable');

        $data = $this->db->generate()->toArray()['data'][0];

        $this->assertSame("1", $data[0]);
        $this->assertSame("John", $data[1]);
        $this->assertStringContainsString('Doe', $data[2]);
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

        $this->assertSame(['fid', 'title', 'info', 'r_year', 'rental_rate', 'mins'], $this->db->getColumns());
    }

    public function testHidesUnnecessaryColumnsFromOutput()
    {
        $this->db->query('select id as fid, name, surname, age from mytable');
        $this->db->hide('fid');
        $data = $this->db->generate()->toArray()['data']['2'];

        $this->assertCount(3, $data);
        $this->assertSame(['name', 'surname', 'age'], $this->db->getColumns());
    }

    public function testReturnsModifiedDataViaClosureFunction()
    {
        $this->db->query('select id as fid, name, surname, age from mytable');

        $this->db->edit('name', function ($data) {
            return strtolower($data['name']);
        });

        $this->db->edit('surname', function ($data) {
            return $this->customfunction($data['surname']);
        });

        $data = $this->db->generate()->toArray()['data']['2'];

        $this->assertSame('george', $data[1]);
        $this->assertSame('Mar...', $data[2]);
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

        $this->assertSame(['column_name', 'privs'], $dt->getColumns());
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
        $columns = $dt->getColumns();

        $this->assertSame($columns[0], 'column_name');
    }

    public function testFiltersDataViaGlobalSearch()
    {
        $this->request->query->set('search', ['value' => 'doe']);

        $this->request->query->set('columns', [
            ['data' => '0', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '1', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select name, surname from mytable');
        $datatables = $this->db->generate()->toArray();

        $this->assertSame(11, $datatables['recordsTotal']);
        $this->assertSame(2, $datatables['recordsFiltered']);

    }

    public function testSortsDataViaSorting()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '1', 'dir' => 'desc']]); //surname-desc

        $this->request->query->set('columns', [
            ['data' => '0', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '1', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '2', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select name, surname, age from mytable');
        $datatables = $this->db->generate()->toArray();

        $this->assertSame(['Todd', 'Wycoff', '36'], $datatables['data'][0]);
    }

    public function testSortsExcludingHiddenColumns()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '1', 'dir' => 'asc']]); // age - asc

        $this->request->query->set('columns', [
            ['data' => '0', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '1', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->hide('fid');
        $this->db->hide('surname');
        $datatables = $this->db->generate()->toArray(); // only name and age visible

        $this->assertSame(['Colin', '19'], $datatables['data'][0]);
    }

    public function testSortsExcludingHiddenColumnsObjectData()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '1', 'dir' => 'asc']]);

        $this->request->query->set('columns', [
            [
                'data' => 'name',
                'name' => '',
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => ['value' => ''],
            ],
            ['data' => 'age', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->hide('fid');
        $this->db->hide('surname');
        $datatables = $this->db->generate()->toArray(); // only name and age visible

        $this->assertSame(['name' => 'Colin', 'age' => '19'], $datatables['data'][0]);
    }


    public function testReorderingColumnsDoesNotAffectOrdering()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '0', 'dir' => 'asc']]);

        $this->request->query->set('columns', [
            ['data' => 'age', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            [
                'data' => 'surname',
                'name' => '',
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => ['value' => ''],
            ],
            [
                'data' => 'name',
                'name' => '',
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => ['value' => ''],
            ],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->hide('fid');
        $datatables = $this->db->generate()->toArray();

        $this->assertSame(['name' => 'Colin', 'surname' => 'McCoy', 'age' => '19'], $datatables['data'][0]);
    }

    public function testReorderingColumnsDoesNotAffectGlobalSearching()
    {
        $this->request->query->set('search', ['value' => 'Stephanie']);
        $this->request->query->set('order', [['column' => '0', 'dir' => 'asc']]);

        $this->request->query->set('columns', [
            ['data' => '2', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '0', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '1', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->hide('fid');
        $datatables = $this->db->generate()->toArray();

        $this->assertSame(['Stephanie', 'Skinner', '45'], $datatables['data'][0]);
    }

    public function testReorderingColumnsDoesNotAffectIndividualSearching()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '0', 'dir' => 'asc']]);

        $this->request->query->set('columns', [
            [
                'data' => 'surname',
                'name' => '',
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => ['value' => 'McCoy'],
            ],
            [
                'data' => 'age',
                'name' => '',
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => ['value' => '19'],
            ],
            [
                'data' => 'name',
                'name' => '',
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => ['value' => 'Colin'],
            ],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->hide('fid');
        $datatables = $this->db->generate()->toArray();

        $this->assertSame(['name' => 'Colin', 'surname' => 'McCoy', 'age' => '19'], $datatables['data'][0]);
    }

    public function testCustomFilteringBetween()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '0', 'dir' => 'asc']]);

        $this->request->query->set('columns', [
            ['data' => '0', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '1', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '2', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->filter('fid', function () {
            return $this->between(4, 6);
        });

        $datatables = $this->db->generate()->toArray();

        $this->assertSame(11, $datatables['recordsTotal']);
        $this->assertSame(3, $datatables['recordsFiltered']);
    }

    public function testCustomFilteringWhereIn()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '0', 'dir' => 'asc']]);

        $this->request->query->set('columns', [
            ['data' => '0', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '1', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '2', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->filter('fid', function () {
            return $this->whereIn([5]);
        });

        $datatables = $this->db->generate()->toArray();

        $this->assertSame(11, $datatables['recordsTotal']);
        $this->assertSame(1, $datatables['recordsFiltered']);
        $this->assertSame(['5', 'Ruby', 'Pickett', '28'], $datatables['data'][0]);
    }

    public function testReturnDefaultSearchWhenNull()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => '0', 'dir' => 'asc']]);

        $this->request->query->set('columns', [
            ['data' => '0', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '5']],
            ['data' => '1', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
            ['data' => '2', 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']],
        ]);

        $this->db->query('Select id as fid, name, surname, age from mytable');
        $this->db->filter('fid', function () {
            //return $this->defaultFilter(); // when it is not defined, returns defaultFilter
        });

        $datatables = $this->db->generate()->toArray();

        $this->assertSame(11, $datatables['recordsTotal']);
        $this->assertSame(1, $datatables['recordsFiltered']);
        $this->assertSame(['5', 'Ruby', 'Pickett', '28'], $datatables['data'][0]);
    }
}
