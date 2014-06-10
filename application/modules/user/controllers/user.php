<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('authentication', NULL, 'ion_auth');
        $this->load->library('form_validation');
        $this->load->helper('url');


        $this->load->database();
        $this->lang->load('auth');
        $this->load->helper('language');    
     
    }
    
   
    
    function index()
    {
        $this->twiggy->template('index')->display();
    }
    function portal()
    {
        $this->twiggy->template('index')->display();
    }
    
}