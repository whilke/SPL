<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ladder extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('session');
    }
    
    function index()
    {
        $this->twiggy->template('index')->display();
    }

    function lobby()
    {
        $this->twiggy->template('lobby')->display();
    }
    
    function players()
    {
        $allUsers = $this->ion_auth->order_by('rating_mean')->users();
        $users = array();
        foreach($allUsers->result() AS $user)
        {
            if ($user->rating_mean != null)
                $users[] = $user;
        }
        
        $this->twiggy->set('users', $users);
        $this->twiggy->template('players')->display();
    }
    
    function game($id)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }        
        $user = $this->ion_auth->user()->row();
        $this->twiggy->set('userLadder', $user);

        $this->load->model('lobbys_model');
        $lobby = $this->lobbys_model->get($id);
        if ($lobby == null)
        {
            redirect('ladder/lobby', 'refresh');
        }
        
        //make sure this player gets added to the lobby.
        $this->lobbys_model->addPlayer($lobby, $user);
        
        $this->twiggy->set('lobby', $lobby);

        
        $this->twiggy->template('game')->display();
    }
    
    function create($id=0, $ajax=false)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        get_instance()->load->library('form_validation');

        if (is_array($id))
        {
            $ajax = $id['ajax'];
            $id = $id['id'];
        }
        
        
        $flashMsg = "";
        
        //validate form input
        $this->form_validation->set_rules('name', 'Name', 'required');
        
        if ($this->form_validation->run() == true) 
        {
            $this->load->model('lobbys_model');
            $name = $this->input->post('name');
            
            $props = array();
            $props['playerCount'] = 1;
            
            $user = $this->ion_auth->user()->row();
            $id = $lobbys = $this->lobbys_model->add($user->id, $name, $props );
            if ($id != false)
            {
                redirect('ladder/game/' . $id, 'refresh');            
            }
            else
            {
               $flashMsg ="Could not create lobby."; 
            }
        }
        
        $this->data = array();        
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);
        $this->twiggy->set('fromajax', $ajax);

               
        $this->data['name'] = array(
                'name'  => 'name',
                'id'    => 'name',
                'type'  => 'text',
                'maxlength'  => '20',
                'value' => $this->form_validation->set_value('name'),
            );        
 
               
        $this->twiggy->set('data', $this->data);
        $view = 'create';
        if ( ! $ajax)
        {
            $x = $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }                
    }
}
