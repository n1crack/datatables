<?php

namespace spec\Ozdemir\Datatables;

use Ozdemir\Datatables\DB\MySQL;
use Ozdemir\Datatables\DB\SQLite;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

/**
 * Class DatatablesSpec
 * @package spec\Ozdemir\Datatables
 */
class DatatablesSpec extends ObjectBehavior
{

    function let()
    {
        $sqlconfig = realpath(dirname(__FILE__) . '/test.db');
        $db = new SQLite($sqlconfig);

        $request = new ServerRequest();
        $request = $request->withParsedBody([
            'draw' => '2',
            'columns' => [
                0 => [
                    'data' => 'Test Column',
                    'name' => '',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => '',
                        'regex' => false,
                    ],
                ],
            ],
            'order' => [
                0 => [
                    'column' => "0",
                    'dir' => 'asc',
                ],
            ],
            'start' => '0',
            'length' => '20',
            'search' => [
                'value' => '',
                'regex' => 'false',
            ],
        ]);

        $this->beConstructedWith($db, $request);
    }

    /**
     * @return array
     */
    public function getMatchers()
    {
        return [
            'haveColumns' => function ($subject, $key) {
                return (array_keys($subject) === $key);
            }
        ];
    }

    public function it_returns_record_counts()
    {
        $this->query("SELECT id AS fid, name, surname, age FROM mytable WHERE id > 3");
        $datatables = $this->generate(false);
        $datatables['recordsTotal']->shouldReturn(8);
        $datatables['recordsFiltered']->shouldReturn(8);
    }

    public function it_returns_data_from_a_basic_sql()
    {
        $this->query("SELECT id AS fid, name, surname, age FROM mytable");

        $data = $this->generate(false)['data'][0];

        $data['fid']->shouldReturn("1");
        $data['name']->shouldReturn("John");
        $data['surname']->shouldContain('Doe');
    }

    public function it_sets_column_names_from_aliases()
    {
        $this->query("SELECT
                  film_id AS fid,
                  title,
                  'description' AS info,
                  release_year 'r_year',
                  film.rental_rate,
                  film.length AS mins
            FROM film");
        $this->get('all_columns')->shouldReturn(['fid', 'title', 'info', 'r_year', 'rental_rate', 'mins']);
    }

    public function it_hides_unnecessary_columns_from_output()
    {
        $this->query("SELECT id AS fid, name, surname, age FROM mytable");
        $this->hide('fid');
        $data = $this->generate(false)['data']['2'];

        $data->shouldHaveCount(3); //  name, surname and age --
        $this->get('columns')->shouldReturn(['name', 'surname', 'age']);

    }

    public function it_returns_modified_data_via_closure_function()
    {
        $this->query("SELECT id AS fid, name, surname, age FROM mytable");

        $this->edit('name', function ($data) {
            return strtolower($data['name']);
        });

        $this->edit('surname', function ($data) {
            return $this->customfunction($data['surname']);
        });

        $data = $this->generate(false)['data']['2'];

        $data['name']->shouldReturn('george');
        $data['surname']->shouldReturn('Mar...');
    }

    function customfunction($data)
    {
        return substr($data, 0, 3) . '...';
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

        $dt->get('columns')->shouldReturn(['column_name', 'privs']);
    }

    public function it_returns_column_names_from_query_that_includes_a_subquery_in_where_statement()
    {
        $dt = $this->query("SELECT column_name
            FROM COLUMNS
            WHERE table_schema = 'mysql' AND table_name = 'user'
            AND (SELECT group_concat(cp.GRANTEE)
            FROM COLUMN_PRIVILEGES cp
            WHERE cp.TABLE_SCHEMA = COLUMNS.TABLE_SCHEMA
            AND cp.TABLE_NAME = COLUMNS.TABLE_NAME
            AND cp.COLUMN_NAME = COLUMNS.COLUMN_NAME) IS NOT NULL;");

        $dt->get('columns')->shouldReturn(['column_name']);
    }
}
