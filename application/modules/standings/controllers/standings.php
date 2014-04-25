<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Standings extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        $this->load->model('Teams_model');
        
        $teamList = $this->Teams_model->get_list();
        $this->twiggy->set('teams', $teamList);
         
        $this->twiggy->template('index')->display();
    }
    
}
