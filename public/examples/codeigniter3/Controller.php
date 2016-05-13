<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\CodeigniterAdapter;

class Welcome extends CI_Controller {

    public function index()
    {
        $this->load->view('welcome_message');
    }

    public function ajax()
    {
        //
        //   Set config/config.app:
        //   $config['composer_autoload'] = 'vendor/autoload.php';
        //

        $datatables = new Datatables(new CodeigniterAdapter);
        $datatables->query('Select film_id, title, description from film');

        echo $datatables->generate();
    }

}
