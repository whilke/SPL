<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mail extends MY_Controller 
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
    
    private function index_render()
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        get_instance()->load->library('form_validation');
        
        $id = $this->ion_auth->user()->row()->id;
        
        $this->load->library('mahana_messaging');        
        $mahData = $this->mahana_messaging->get_all_threads($id, false, 'DESC');
        
        if ($mahData['err'] == 0)
        {
            $inbox = array();
            $outbox = array();
            foreach($mahData['retval'] as $message)
            {
                if ($message['status'] > 1) continue;
                $list = $this->mahana_messaging->get_participant_list($message['thread_id'], $id);
                $message['list'] = $list['retval'];
                if ($message['sender_id'] != $id)
                    $inbox[] = $message;
                else
                    $outbox[] = $message;
            }
            
            $messages = new stdClass();
            $messages->inbox = $inbox;
            $messages->outbox = $outbox;
            
            $this->twiggy->set('messages', $messages);
        }
        else
            $this->twiggy->set('messages', array());        
    }
    
    function index()
    {
        $this->index_render();
        $this->twiggy->set('showSent', false);            
        $this->twiggy->template('index')->display();
    }
    
    function sent()
    {
        $this->index_render();
        $this->twiggy->set('showSent', true);            
        $this->twiggy->template('index')->display();
   }
    
    function delete($fromSent, $threadid)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        $id = $this->ion_auth->user()->row()->id;


        $this->load->library('mahana_messaging');        
        $mahData = $this->mahana_messaging->get_full_thread($threadid, $id);
        if ($mahData['err'] == 0)
        {
            $mid = $mahData['retval'][0]['id'];
            $this->mahana_messaging->update_message_status($mid, $id, 2);
        }
        
        if ($fromSent)
        {
            redirect('mail/sent', 'refresh');            
        }
        else
        {
            redirect('mail', 'refresh');            
        }

    }
    
    function reply($id, $fromajax=false)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        get_instance()->load->library('form_validation');

        if (is_array($id))
        {
            $fromajax = $id['fromajax'];
            $id = $id['id'];
        }
        
        $from = $this->ion_auth->user()->row()->id;

        $flashMsg = "";
        
        //validate form input
        $this->form_validation->set_rules('subject', 'Subject', 'required');
        $this->form_validation->set_rules('msg', 'Message', 'required');
        
        $mahData = $this->mahana_messaging->get_full_thread($id, $from);
        $subject = "";
        $msg = "";
        if ($mahData['err'] == 0)
        {
            $subject = "RE: " . $mahData['retval'][0]['subject'];
            $msg = "\n\n>>" . $mahData['retval'][0]['body'];
        }
        
        
        if ($this->form_validation->run() == true) 
        {
            $subject = $this->input->post('subject');
            $msg = $this->input->post('msg');
 
            $this->load->library('mahana_messaging');
            $this->mahana_messaging->send_new_message($from, $mahData['retval'][0]['sender_id'],  $subject, $msg);
            redirect('mail', 'refresh');
        }
        

        
        $this->data = array();        
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);
        $this->twiggy->set('fromajax', $fromajax);
        $this->twiggy->set('id', $id);

               
        $this->data['subject'] = array(
                'name'  => 'subject',
                'id'    => 'subject',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('subject', $subject),
            );        
 
               
        $this->data['msg'] = array(
                'name'  => 'msg',
                'id'    => 'msg',
                'type'  => 'text',
                'autofocus' => true,
                'value' => $this->form_validation->set_value('msg', $msg),
            );        
 

        $this->twiggy->set('data', $this->data);
        $view = 'reply';
        if ( ! $fromajax)
        {
            $x = $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }        
              
    }
    
    function create($id, $fromajax=false)
    {
        
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        get_instance()->load->library('form_validation');

        if (is_array($id))
        {
            $fromajax = $id['fromajax'];
            $id = $id['id'];
        }
        
        $from = $this->ion_auth->user()->row()->id;
        $to = $this->ion_auth->user($id)->row();
        
        $flashMsg = "";
        
        //validate form input
        $this->form_validation->set_rules('subject', 'Subject', 'required');
        $this->form_validation->set_rules('msg', 'Message', 'required');
        
        if ($this->form_validation->run() == true) 
        {
            $subject = $this->input->post('subject');
            $msg = $this->input->post('msg');
 
            $this->load->library('mahana_messaging');
            $this->mahana_messaging->send_new_message($from, $id, $subject, $msg);
            redirect('mail/sent', 'refresh');
        }
        
        $this->data = array();        
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);
        $this->twiggy->set('fromajax', $fromajax);
        $this->twiggy->set('id', $id);
        $this->twiggy->set('to_user', $to->username);

               
        $this->data['subject'] = array(
                'name'  => 'subject',
                'id'    => 'subject',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('subject'),
            );        
 
               
        $this->data['msg'] = array(
                'name'  => 'msg',
                'id'    => 'msg',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('msg'),
            );        
 

        $this->twiggy->set('data', $this->data);
        $view = 'create';
        if ( ! $fromajax)
        {
            $x = $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }        
                       
    }
    
    function ajax_msgcount()
    {
        if (!$this->ion_auth->logged_in())
        {
            $d = array();
            $d['count'] = 0;

             $json = json_encode($d);

                  $this->output
                ->set_content_type('application/json')
                ->set_output($json);
                return;
        }
               
        $userId = $this->ion_auth->user()->row()->id;
        $msg = $this->mahana_messaging->get_msg_count($userId);
            $count = 0;
            if ($msg['err'] == 0)
                $count = $msg['retval'];
            
        $d = array();
        $d['count'] = $count;
        
         $json = json_encode($d);
            
            $this->output
            ->set_content_type('application/json')
            ->set_output($json);
        
    }
    
    function ajax_thread($id, $mark=0)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
               
        $userId = $this->ion_auth->user()->row()->id;
        
        $this->load->library('mahana_messaging');        
        $mahData = $this->mahana_messaging->get_full_thread($id, $userId);
        if ($mahData['err'] == 0)
        {
            $message = $mahData['retval'][0];
            
            if ($mark)
            {
                $mid = $message['id'];
                if ($message['status'] == 0)
                    $this->mahana_messaging->update_message_status($mid, $userId, 1);
            }
            
            $json = json_encode($message);
            
            $this->output
            ->set_content_type('application/json')
            ->set_output($json);
        }
        
    }
}