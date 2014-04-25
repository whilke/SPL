<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('session');
    }
    
    function index()
    {
        $skipLanding = $this->session->userdata('skipLanding');
        
        if ($this->ion_auth->logged_in())
            $skipLanding = true;
        
        $this->twiggy->set('skipLanding', $skipLanding);
        $this->twiggy->template('index')->display();
    }
    
    function setLanding()
    {
        $this->session->set_userdata('skipLanding', true);
    }
    
    function contact()
    {
        get_instance()->load->library('form_validation');
        
        $this->form_validation->set_rules('from', 'From', 'required|valid_email');
        $this->form_validation->set_rules('subject', 'Subject', 'required|xss_clean');
        $this->form_validation->set_rules('content', 'Message', 'required|xss_clean');

        $msg = "";
        if ($this->form_validation->run() == true)
        {
            $from = $this->input->post('from');
            $subject    = $this->input->post('subject');
            $content = $this->input->post('content');

            $this->load->library('email');

            $this->email->clear();
            $this->email->from($from);
            $this->email->to('support@strifeproleague.com');
            $this->email->subject('CONTACT FORM: ' . $subject);
            $this->email->message($content);

            if ($this->email->send())
            {
                $msg = 'Thank you for the message, we will get back to you shortly';
            }
            else
            {
                $msg = 'Error sending message!';                
            }

            
        }
        
        $this->data = array();
        
                //display the create user form
        //set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : $msg);

        if ($msg != '')
            $this->data['enableBtn'] = false;
        else
            $this->data['enableBtn'] = true;
            

        $this->data['from'] = array(
            'name'  => 'from',
            'id'    => 'from',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('from'),
        );
        $this->data['subject'] = array(
            'name'  => 'subject',
            'id'    => 'subject',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('subject'),
        );
        $this->data['content'] = array(
            'name'  => 'content',
            'id'    => 'content',
            'value' => $this->form_validation->set_value('content'),
        );         


        $this->twiggy->set('data', $this->data);
         
        $this->twiggy->template('contact')->display();
        
    }
    
}
