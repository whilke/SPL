<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class draft_api extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->database();
        $this->lang->load('auth');
        $this->load->model('Draft_model', 'Drafts');

    }
    
    function getLobbys()
    { 
        $lobbys = $this->Drafts->getAll();
        
        $obj = new stdClass();
        $retLobbys = array();
        foreach($lobbys AS $lobby)
        {
            $timestamp = new DateTime($lobby->timestamp);
            $time = date("Y-m-d H:i:s");
            $now = new DateTime($time);
            
            $diff = $now->diff($timestamp);       
            
            $mins = $diff->days * 24 * 60;
            $mins += $diff->h * 60;
            $mins += $diff->i;            
            
            
            $stripLobby = new stdClass();
            
            if ( $mins > 30  )
            {
            }
            else
            {                
                $stripLobby = new stdClass();
                $stripLobby->id = $lobby->id;
                $stripLobby->name = $lobby->title;
                $retLobbys[] = $stripLobby;
            }
            
        }        
        $obj->lobbys = $retLobbys;
        

        $json = json_encode($obj);
            
        $this->output
        ->set_content_type('application/json')
        ->set_output($json);
        
    }    
    
    function sendChat()
    {
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if (!$this->ion_auth->logged_in())
        {
            return;
        }
        
        $draft = $this->Drafts->get($id);
        $user = $this->ion_auth->user()->row();   
        if (!$draft->isUserValid($user))
        {
            //admins are allowed.
            $isManager = $this->ion_auth->is_manager();
            if (!$isManager)
            {
                 
                return;                
            }
        }
        
        $update = json_decode($data);
        $this->Drafts->addChat($id, $user->id, $update->msg);        
    }
    
    function getDraftLobby($id, $chatTs=0)
    {
        $chatTs = rawurldecode($chatTs);
        
        $user = null;
        $sId= null;
        if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();
        }        
        else
        {
            $sId = $this->session->userdata('session_id');
        }

        $draft = $this->Drafts->get($id);
        $draftValid = false;
        
        if ($draft != null)
        {
            $timestamp = new DateTime($draft->timestamp);
            $time = date("Y-m-d H:i:s");
            $now = new DateTime($time);
            
            $diff = $now->diff($timestamp);       
            
            $mins = $diff->days * 24 * 60;
            $mins += $diff->h * 60;
            $mins += $diff->i;            
            
            if ($mins < 30)                
                $draftValid = true;
        }
        
        $retObj = new stdClass();
        $retObj->valid = false;
        if (!$draftValid)
        {
            $retObj->redirect = site_url('drafts');
        }
        else
        {
            if ($user != null)
            {
                $retObj->validUser = true;
            }
            else
            {
                $retObj->validUser = false;
                
            }
                
            
            if ($draft->isTimerActive() && $draft->isTimerExpired())
            {
                $draft->randomPick();
            }

            $retObj->valid = true;
            $retObj->gloryBan = $draft->getGloryBan();
            $retObj->valorBan = $draft->getValorBan();
            $retObj->gloryPicks = $draft->getGloryPicks();
            $retObj->valorPicks = $draft->getValorPicks();
            
            if ($draft->glory_seat != null)
            {
                $retObj->glorySeat = ['name'=>$draft->glory_seat->username,
                    'id'=>$draft->glory_seat->id];            
            }
            
            if ($draft->valor_seat != null)
            {
                $retObj->valorSeat = ['name'=>$draft->valor_seat->username,
                    'id'=>$draft->valor_seat->id];            
            }
            
            $retObj->timerActive = $draft->isTimerActive();
            $retObj->gloryRound = $draft->isGloryRound();
            $retObj->roundTime = $draft->getRoundTimeLeft();
            
            if ( $retObj->timerActive && $retObj->roundTime < 0)
            {
                if ( $retObj->gloryRound )
                {
                    $retObj->glory_extraTime = $draft->glory_extra_time + $retObj->roundTime; 
                    if ($retObj->glory_extraTime < 0) $retObj->glory_extraTime = 0;
                    $retObj->valor_extraTime = $draft->valor_extra_time; 
                }
                else
                {
                    $retObj->valor_extraTime = $draft->valor_extra_time + $retObj->roundTime; 
                    if ($retObj->valor_extraTime < 0) $retObj->valor_extraTime = 0;
                    $retObj->glory_extraTime = $draft->glory_extra_time; 
                }
                
                $retObj->roundTime = 0;
           }
           else
           {
                $retObj->valor_extraTime = $draft->valor_extra_time; 
                $retObj->glory_extraTime = $draft->glory_extra_time;                
           }
          
           $now = microtime(true);
           $retObj->nowChatTime = $now;
           if ($chatTs != 0)
                $now = $chatTs;
           
           $chat = $this->Drafts->getChat($id, $now);
           $retObj->chat = $chat;            
           
        }
             
        $json = json_encode($retObj);            
        $this->output
        ->set_content_type('application/json')
        ->set_output($json);        

    }
    
    function pickHero()
    {
        $r = new stdClass();
        $r->result = false;
        
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();        
            $draft = $this->Drafts->get($id);

            //validate that this user is allowed to make a pick.
            if ($draft->validatePickUser($user))
            {
                $obj = json_decode($data);        
                $hero_id = $obj->id;        
                if ($draft->pickHero($user, $hero_id))
                {
                    $r->result = true;
                    $r->hero_id = $hero_id;
                }
            }

        }    
                    
        $json = json_encode($r);            
        $this->output
        ->set_content_type('application/json')
        ->set_output($json);                
    }
    
    function joinLobby()
    {
        $r = new stdClass();
        $r->result = false;
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        $user = null;
        $sId= null;
        if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();
        }        
        else
        {
            $sId = $this->session->userdata('session_id');
        }
        
        //if ($this->ion_auth->logged_in())
        {
            $draft = $this->Drafts->get($id);
            if ($draft != null)
            {
                $username = "Guest";
                if ($user != null)
                    $username = $user->username;
                
                $this->Drafts->addChat($id, 2, $username . " joined the lobby.");
                
                $dUser =  $this->Drafts->isUserInDraft($draft->id, $user, $sId);
                if ($dUser == false)
                {
                    $this->Drafts->addUser($draft->id, $user, $sId);
                }
            }   
        }
    }
    
    function leaveLobby()
    {
        $r = new stdClass();
        $r->result = false;
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        $user = null;
        $sId= null;
        if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();
        }        
        else
        {
            $sId = $this->session->userdata('session_id');
        }
        
        {
            $username = "Guest";
            if ($user != null)
                $username = $user->username;

            $draft = $this->Drafts->get($id);
            if ($draft != null)
            {
                $this->Drafts->addChat($id, 2, $username . " left the lobby.");
            }
            
        }
    }    
    
    function sitDown()
    {
        $r = new stdClass();
        $r->result = false;
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();        
            $draft = $this->Drafts->get($id);

            //validate that this user is allowed to make a pick.
            if (true)
            {
                $obj = json_decode($data);
                $isGlory = $obj->isGlory;
                
                if ($draft->sitDown($isGlory, $user))
                {
                    $r->result = true;
                }
            }

        }    
                    
        $json = json_encode($r);            
        $this->output
        ->set_content_type('application/json')
        ->set_output($json);        
    }
}
    
   

/* End of file auth_ajax.php */
/* Location: ./application/modules/auth/controllers/auth_ajax.php */