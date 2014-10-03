<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Drafts extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('session');
        $this->load->model('Draft_model', 'Drafts');
    }
    
    function index()
    {
        $this->twiggy->template('index')->display();
    }
    
    function validate($id)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }        
        $user = $this->ion_auth->user()->row();

        $draft = $this->Drafts->get($id);
        if ($draft == null)
        {
            redirect('drafts', 'refresh');
            return;
        }
        $this->twiggy->set('draft', $draft);
        
        get_instance()->load->library('form_validation');
        $flashMsg = "";
        
        //validate form input
        if ($draft->password != '')
        {
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run() == true) 
            {
                $passwd = $this->input->post('password');

                if ($passwd == $draft->password)
                {
                    $this->Drafts->addUser($draft->id, $user->id);
                    redirect('drafts/lobby/' . $id, 'refresh');            
                    return;
                }
                else
                {
                   $flashMsg ="Invalid Password."; 
                }
            }
        }
        
        $this->data = array();        
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);

               
        $this->data['password'] = array(
                'name'  => 'password',
                'id'    => 'password',
                'type'  => 'password',
                'maxlength'  => '45',
                'value' => '',
            );        
 
               
        $this->twiggy->set('data', $this->data);
        
        
        $this->twiggy->template('validate')->display();
    }
    
    function lobby($id)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }        
        $user = $this->ion_auth->user()->row();

        $draft = $this->Drafts->get($id);
        if ($draft == null)
        {
            redirect('drafts', 'refresh');
            return;
        }
        
        if (!$draft->isUserValid($user) && $draft->password != "")
        {
            //admins are allowed.
            //$isManager = $this->ion_auth->is_manager();
            //if (!$isManager)
            {
                redirect('drafts/validate/' .  $id, 'refresh');    
                return;                
            }
        }
        
        $this->load->model('stats_model', 'stats');
        $heroList = $this->stats->getHeroList2();
        
        $this->twiggy->set('heroes', $heroList);
        $this->twiggy->set('draft', $draft);
        
        $this->twiggy->template('lobby')->display();
    }
}
