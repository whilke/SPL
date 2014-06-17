<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Team extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('authentication', NULL, 'ion_auth');
        $this->load->library('form_validation');
        $this->load->helper('url');

        $this->load->database();

        $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

        $this->lang->load('auth');
        $this->load->helper('language');

        $this->model('Teams_model');

    }
    
    public function index()
    {
        $this->twiggy->template('index')->display();
    }
    
    public function portal($teamid=0)
    {
        $team = null;
        $userTeamId = 0;
        if ($teamid == 0)
        {
            if (!$this->ion_auth->logged_in())
            {
                 redirect('/', 'refresh');
            }
            
            $user = $this->ion_auth->user()->row();
            $team = $this->Teams_model->getById($user->team_id);
            $teamid = $team->id;
            $userTeamId = $team->id;
        }
        else
        {           
            $team = $this->Teams_model->getById($teamid);
            if ($team == NULL)
            {
                redirect('/', 'refresh');
            }
            
            if ($this->ion_auth->logged_in() && !$this->ion_auth->is_manager())
            {
                $user = $this->ion_auth->user()->row();
                if ($user->team_id != 0)
                {
                    $uTeam = $this->Teams_model->getById($user->team_id);
                    $userTeamId = $uTeam->id;                    
                }
            }
        }
        if ($teamid == $userTeamId)
        {
            $this->twiggy->set('isTeamMember', true);
        }
        
        $isManager = $this->ion_auth->is_manager();
        $hideStats = true;
        if ($isManager ||  $teamid == $userTeamId)
            $hideStats = false;

        //check if this user is the team owner.
        $isTeamOwner = false;
        if (isset($user))
        {
            $isTeamOwner = $this->ion_auth->is_team_owner();
            if ($isTeamOwner)
            {
                if ($teamid != $userTeamId)
                    $isTeamOwner = false;
            }
        }
        
        if ($isManager)
            $isTeamOwner = true;
        
        $this->model('Seasons_model');
        
        $matches = $this->Seasons_model->getMatchesForPortal($teamid, !$isManager);
        $this->twiggy->set('matches', $matches);

                
        $this->twiggy->set('isTeamOwner', $isTeamOwner);
        $this->twiggy->set('canEdit', $isTeamOwner);
        
        $team->owner_id = $team->players[0]->id;
        $this->twiggy->set('team', $team);
        
        $seasons = $this->Seasons_model->GetAllSeasons();
        $activeSeason = $this->Seasons_model->GetCurrentSeason();
        
        $total_points = 0;
        $arr = Array();
        foreach($seasons AS $season)
        {
            $stats = $this->Seasons_model->getSeasonStats($teamid, $season->id, $hideStats);
            if ($stats == null) continue;
            $arr[] = $stats;
            
            if ($season->id == $activeSeason->id)
                $total_points = $stats->points;
        }
        $this->twiggy->set('season_stats', $arr);
        $this->twiggy->set('season_points', $total_points);
        $this->twiggy->template('portal')->display();

    }
    
    public function save()
    {
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if (!$this->ion_auth->logged_in())
        {
             redirect('/', 'refresh');
        }
        
        $user = $this->ion_auth->user()->row();
        $isManager = $this->ion_auth->is_manager();
        $isTeamOwner = false;
        {
            $isTeamOwner = $this->ion_auth->is_team_owner();
            if ($isTeamOwner)
            {
                if ($id != $user->team_id)
                    $isTeamOwner = false;
            }
        }
        if ($isManager) $isTeamOwner = true;
        
        if (!$isTeamOwner) return;
        
        $update = json_decode($data);
        
        $this->Teams_model->edit($id, $update);

    }
    
    function create($userName='', $strife_id=0, $fromAjax=false)
    {
        if (is_array($userName))
        {
            $fromAjax = $userName['fromajax'];
            $strife_id = $userName['strife_id'];
            $userName = $userName['userName'];
        }
        
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        $user = $this->ion_auth->user()->row(); 
        
        //make sure this user is not already part of a team.
        if ($user->team_id > 0)
        {
            redirect('main', 'refresh');            
        }
        get_instance()->load->library('form_validation');
        
        //validate form input
        $this->form_validation->set_rules('teamname', 'Team name', 'required|xss_clean');

        $errMsg = "";
        if ($this->form_validation->run() == true)
        {
            $teamname = $this->input->post('teamname');
            
            //see if we can create this team first.
            $this->load->model('Teams_model');
            $id = $this->Teams_model->add($teamname);
            
            if ($id != false)
            {
                //now join this user to this team
                $this->Teams_model->joinUser($id, $user->id);

                //promote this user to the captain
                $this->Teams_model->promote($id, $user->id, 0);

                redirect('team/portal/', 'refresh');            
            }
            
            $errMsg = "Team name is in use or invalid.";
        }

        $this->data = array();
        $this->data['message'] = (validation_errors() ? validation_errors() : $errMsg);


        $this->data['teamname'] = array(
            'name'  => 'teamname',
            'id'    => 'teamname',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('teamname'),
        );

        $this->twiggy->set('data', $this->data);
        $view = 'create';
        if ( ! $fromAjax)
        {
            $x = $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }        

    }

    
    function upgrade($userName, $strife_id=0, $fromAjax=false)
    {
        if (is_array($userName))
        {
            $fromAjax = $userName['fromajax'];
            $strife_id = $userName['strife_id'];
            $userName = $userName['userName'];
        }        
        $userName = rawurldecode($userName);

        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        $isManager = $this->ion_auth->is_manager();

        //check if this user is the team owner.
        $isTeamOwner = false;
        $user = $this->ion_auth->user()->row();
        if (isset($user))
        {
            $isTeamOwner = $this->ion_auth->is_team_owner();            
        }
        
        if ($isManager)
            $isTeamOwner = true;     
        
        if (!$isTeamOwner)
        {
             redirect('auth', 'refresh');           
        }
        
        get_instance()->load->library('form_validation');

                
        $flashMsg = "";
        
        //validate form input
        $this->form_validation->set_rules('email', 'Email', 'required|matches[email2]');
        $this->form_validation->set_rules('email2', 'Verify Email', 'required');
        
        if ($this->form_validation->run() == true) 
        {
           $email = $this->input->post('email');
             
            $additional_data = array();
            $id = $this->ion_auth->register($userName, 'Fx3qW0p', $email, $additional_data, $additional_data, true);
            if ($id != FALSE)
            {   
                //clean up the new user now.
                $additional_data['team_id'] = $user->team_id;
                $additional_data['strife_id'] = $strife_id;
                $additional_data['teamname'] = $user->teamname;
                $this->ion_auth->update($id, $additional_data);
                                
                
                redirect("team/portal/" . $user->team_id, 'refresh');
            }
        }
        
        $this->data = array();        
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() :$flashMsg));
        $this->twiggy->set('fromajax', $fromAjax);
        $this->twiggy->set('username', $userName);
        $this->twiggy->set('strife_id',$strife_id);

               
        $this->data['email'] = array(
                'name'  => 'email',
                'id'    => 'email',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('email'),
            );        
 
               
        $this->data['email2'] = array(
                'name'  => 'email2',
                'id'    => 'email2',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('email2'),
            );        
 

        $this->twiggy->set('data', $this->data);
        $view = 'upgrade';
        if ( ! $fromAjax)
        {
            $x = $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }        
        
    }
    
    function leave()
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        $user = $this->ion_auth->user()->row(); 
        
        if ($user->team_id <= 0)
        {
            redirect('main', 'refresh');            
        }
        
        get_instance()->load->library('form_validation');
        
        $isTeamOwner = false;
        if (isset($user))
        {
            $isTeamOwner = $this->ion_auth->is_team_owner();            
        }
        $this->twiggy->set('isTeamOwner', $isTeamOwner);
        
        //grab the team
        $this->load->model('Teams_model');
        $team = $this->Teams_model->getById($user->team_id);
        
        $this->form_validation->set_rules('confirm', 'Confirm', 'required');
        $errMsg = "";
        if ($this->form_validation->run() == true) 
        {
            $confirm = $this->input->post('confirm');
            if ($confirm == 'yes')
            {
                $new_cap = $this->input->post('new_cap');
                
                //okay if there is a new_cap we remove ourselves from the team and promote them.
                $this->Teams_model->removeUser($team->id, $user->id);
                $team = $this->Teams_model->getById($user->team_id);
                 
                if ($new_cap != false)
                {
                    $this->Teams_model->promote($team->id, $new_cap, 0);
                }
                else
                {
                    //double check that there are no more users to delete.
                    if (sizeof($team->players) != 0)
                    {
                        //promote the first user
                        $player = $team->players[0];
                        $this->Teams_model->promote($team->id, $player->id, 0);
                    }
                    else
                    {
                        //disable this team.
                        $this->Teams_model->deactivate($team->id);
                    }
                }
                
                redirect('user/portal', 'refresh');            

                
            }
            else
            {
                $errMsg = "You must type 'yes' to confirm.";
            }
        }
        
        $this->data = array();        
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() :$errMsg));

        $this->data['confirm'] = array(
                'name'  => 'confirm',
                'id'    => 'confirm',
                'type'  => 'text',
                'value' => '',
            );
                
        $teamList = array();
        $new_cap = null;
        foreach($team->players AS $player)
        {
            if ($player->id == $user->id) continue;
            
            if ($new_cap == null)
            {
                $new_cap = $this->form_validation->set_value('new_cap', $player->id);
            }
            $teamList[$player->id] = $player->name;
        }
        
        $this->data['new_cap'] = $new_cap;
        $this->data['teamList'] = $teamList;
                 
        $this->twiggy->set('data', $this->data);
        $view = 'leave';
        $this->twiggy->template($view)->display();
        
    }
}