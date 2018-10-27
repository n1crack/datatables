<?php

namespace spec\Ozdemir\Datatables;

use Ozdemir\Datatables\DB\SQLite;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\Request;

class DatatablesSpec extends ObjectBehavior
{
    private $request;


    public function let()
    {
        $sqlconfig = __DIR__.'/../fixtures/test.db';
        $db = new SQLite($sqlconfig);

        $this->request = Request::create(null, 'GET', ['draw' => 1]);

        $this->beConstructedWith($db, $this->request);
    }

    public function getMatchers()
    {
        return [
            'haveColumns' => function ($subject, $key) {
                return (array_keys($subject) === $key);
            },
        ];
    }

    public function it_returns_record_counts()
    {
        $this->query("Select id as fid, name, surname, age from mytable where id > 3");
        $datatables = $this->generate()->toArray();
        $datatables['recordsTotal']->shouldReturn(8);
        $datatables['recordsFiltered']->shouldReturn(8);
    }

    public function it_returns_data_from_a_basic_sql()
    {
        $this->query("Select id as fid, name, surname, age from mytable");

        $data = $this->generate()->toArray()['data'][0];

        $data['fid']->shouldReturn("1");
        $data['name']->shouldReturn("John");
        $data['surname']->shouldContain('Doe');
    }

    public function it_sets_column_names_from_aliases()
    {
        $this->query("Select
                  film_id as fid,
                  title,
                  'description' as info,
                  release_year 'r_year',
                  film.rental_rate,
                  film.length as mins
            from film");
        $this->getColumns()->shouldReturn(['fid', 'title', 'info', 'r_year', 'rental_rate', 'mins']);
    }

    public function it_hides_unnecessary_columns_from_output()
    {
        $this->query('Select id as fid, name, surname, age from mytable');
        $this->hide('fid');
        $data = $this->generate()->toArray()['data']['2'];

        $data->shouldHaveCount(3); //  name, surname and age --
        $this->getColumns()->shouldReturn(['name', 'surname', 'age']);
    }

    public function it_returns_modified_data_via_closure_function()
    {
        $this->query('Select id as fid, name, surname, age from mytable');

        $this->edit('name', function ($data) {
            return strtolower($data['name']);
        });

        $this->edit('surname', function ($data) {
            return $this->customfunction($data['surname']);
        });

        $data = $this->generate()->toArray()['data']['2'];

        $data['name']->shouldReturn('george');
        $data['surname']->shouldReturn('Mar...');
    }

    public function customfunction($data)
    {
        return substr($data, 0, 3).'...';
    }

    public function it_returns_column_names_from_query_that_includes_a_subquery_in_select_statement()
    {
        $dt = $this->query("SELECT column_name,
            (SELECT group_concat(cp.GRANTEE)
            FROM COLUMN_PRIVILEGES cp
            WHERE cp.TABLE_SCHEMA = COLUMNS.TABLE_SCHEMA
            AND cp.TABLE_NAME = COLUMNS.TABLE_NAME
            AND cp.COLUMN_NAME = COLUMNS.COLUMN_NAME)
            privs
            FROM COLUMNS
            WHERE table_schema = 'mysql' AND table_name = 'user';");

        $dt->getColumns()->shouldReturn(['column_name', 'privs']);
    }

    public function it_returns_column_names_from_query_that_includes_a_subquery_in_where_statement()
    {
        $dt = $this->query("SELECT column_name
            FROM COLUMNS
            WHERE table_schema = 'mysql' AND table_name = 'user'
            and (SELECT group_concat(cp.GRANTEE)
            FROM COLUMN_PRIVILEGES cp
            WHERE cp.TABLE_SCHEMA = COLUMNS.TABLE_SCHEMA
            AND cp.TABLE_NAME = COLUMNS.TABLE_NAME
            AND cp.COLUMN_NAME = COLUMNS.COLUMN_NAME) is not null;");

        $dt->getColumns()->shouldReturn(['column_name']);
    }

    public function it_filters_data_via_global_search()
    {
        $this->request->query->set('search', ['value' => 'doe']);

        $this->request->query->set('columns', [
            ['data' => 0, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '']],
            ['data' => 1, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '']],
        ]);

        $this->query('Select name, surname from mytable');
        $datatables = $this->generate()->toArray();

        $datatables['recordsTotal']->shouldReturn(11);
        $datatables['recordsFiltered']->shouldReturn(2);
    }

    public function it_sorts_data_via_sorting()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => 1, 'dir' => 'desc']]); //surname-desc

        $this->request->query->set('columns', [
            ['data' => 0, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '']],
            ['data' => 1, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '']],
            ['data' => 2, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '']],
        ]);

        $this->query('Select name, surname, age from mytable');
        $datatables = $this->generate()->toArray();

        $datatables['data'][0]->shouldReturn(['Todd', 'Wycoff', '36']);
    }

    public function it_sorts_excluding_hidden_columns()
    {
        $this->request->query->set('search', ['value' => '']);
        $this->request->query->set('order', [['column' => 1, 'dir' => 'asc']]); // age - asc

        $this->request->query->set('columns', [
            ['data' => 0, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '']],
            ['data' => 1, 'name' => '', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '']],
        ]);

        $this->query('Select id as fid, name, surname, age from mytable');
        $this->hide('fid');
        $this->hide('surname');
        $datatables = $this->generate()->toArray(); // only name and age visible

        $datatables['data'][0]->shouldReturn(['Colin', '19']);
    }

}
