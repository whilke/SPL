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

        
        $this->load->model('Teams_model');
    }
    
    public function index()
    {
        $this->twiggy->template('index')->display();
    }
    
    public function portal($teamid=0)
    {
        $team = null;
        if ($teamid == 0)
        {
            if (!$this->ion_auth->logged_in())
            {
                 redirect('/', 'refresh');
            }
            
            $user = $this->ion_auth->user()->row();
            $team = $this->Teams_model->get($user->teamname);
            $teamid = $team->id;
        }
        else
        {
            $team = $this->Teams_model->getById($teamid);
            if ($team == NULL)
            {
                redirect('/', 'refresh');
            }
        }

        $this->load->model('Seasons_model');
        
        $matches = $this->Seasons_model->getMatchesForPortal($teamid);
        $this->twiggy->set('matches', $matches);

        $this->twiggy->set('team', $team);
        
        $seasons = $this->Seasons_model->GetAllSeasons();
        $activeSeason = $this->Seasons_model->GetRunningSeason($teamid);
        
        $total_points = 0;
        $arr = Array();
        foreach($seasons AS $season)
        {
            $stats = $this->Seasons_model->getSeasonStats($teamid, $season->id);
            $arr[] = $stats;
            
            if ($season->id == $activeSeason[0]['id'])
                $total_points = $stats->points;
        }
        $this->twiggy->set('season_stats', $arr);
        $this->twiggy->set('season_points', $total_points);
        $this->twiggy->template('portal')->display();

    }
    
    function standings()
    {
        
    }
    
    public function edit($id=0)
    {
        get_instance()->load->library('form_validation');
        
        if (!$this->ion_auth->logged_in())
        {
             redirect('/', 'refresh');
        }
        
        if ($id == 0)
        {
            $id = $this->input->post('id');
            if ($id == false)
                $id = 0;
        }
        
        $seasonData = $this->input->post('inSeasons');
        $subSeason = false;
        if (isset($seasonData) && !empty($seasonData)) 
        {
            $subSeason = true;
        }

        
        //validate form input
        $this->form_validation->set_rules('captain', 'Captain', 'required|xss_clean');
        $this->form_validation->set_rules('contact', 'Contact', 'required|xss_clean');
        $this->form_validation->set_rules('region', 'Region', 'required|xss_clean');
        $this->form_validation->set_rules('bio', 'Bio', 'xss_clean');
        $this->form_validation->set_rules('slot5', 'Member', 'xss_clean');
        $this->form_validation->set_rules('slot6', 'Member', 'xss_clean');
        $this->form_validation->set_rules('inSeasons', 'Season', 'xss_clean');
        if ($subSeason)
        {
            $this->form_validation->set_rules('slot1', 'Member', 'required|xss_clean');
            $this->form_validation->set_rules('slot2', 'Member', 'required|xss_clean');
            $this->form_validation->set_rules('slot3', 'Member', 'required|xss_clean');
            $this->form_validation->set_rules('slot4', 'Member', 'required|xss_clean');            
        }
        else
        {
            $this->form_validation->set_rules('slot1', 'Member', 'xss_clean');
            $this->form_validation->set_rules('slot2', 'Member', 'xss_clean');
            $this->form_validation->set_rules('slot3', 'Member', 'xss_clean');
            $this->form_validation->set_rules('slot4', 'Member', 'xss_clean');            
        }

        $isAdmin = $this->ion_auth->is_admin();
        $user = $this->ion_auth->user()->row();

        $this->load->model('Seasons_model');

        if ($this->form_validation->run() == true)
        {
             $teamid = $this->input->post('id');
             $captain = $this->input->post('captain');
             $contact = $this->input->post('contact');
             $region = $this->input->post('region');
             $additional_data = array(
                 'slot1' => $this->input->post('slot1'),
                 'slot2' => $this->input->post('slot2'),
                 'slot3' => $this->input->post('slot3'),
                 'slot4' => $this->input->post('slot4'),
                 'slot5' => $this->input->post('slot5'),
                 'slot6' => $this->input->post('slot6'),
                 'bio' => $this->input->post('bio'),
                 );
             
            $seasonData = $this->input->post('inSeasons');
             
             if ($isAdmin || $this->Teams_model->get($user->teamname)->id == $teamid)
             {
                 $this->Seasons_model->updateTeamInSeasons($teamid, $seasonData);
                 
                 $this->Teams_model->edit($teamid, $captain, $contact, $region, $additional_data);
             }
             
             redirect('/', 'refresh');
        }
        else
        {
            $this->data = array();
            $this->data['message']= (validation_errors() ? validation_errors() : $this->session->flashdata('message') );
            
            $teamname = '';
            $team = 0;
            if ($isAdmin == FALSE && $id == 0)
            {
                $teamname = $user->teamname;
                $team = $this->Teams_model->get($teamname);
                $id = $team->id;
            }            

            if ($user->teamname == $teamname || ($isAdmin && $id != 0) )
            {
                if (!$team)
                {
                    $team = $this->Teams_model->getById($id);
                }
                               
                $seasons = $this->Seasons_model->GetActiveSeasons($id);
                $this->data['seasons'] = $seasons;
                                
                $this->data['name'] = array(
                'name'  => 'name',
                'id'    => 'name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('name', $team->name),
                );
                $this->data['captain'] = array(
                    'name'  => 'captain',
                    'id'    => 'captain',
                    'type'  => 'text',
                    'value' => $this->form_validation->set_value('captain', $team->captain),
                );
                $this->data['contact'] = array(
                    'name'  => 'contact',
                    'id'    => 'contact',
                    'type'  => 'text',
                    'value' => $this->form_validation->set_value('contact', $team->contact),
                );         
                $this->data['region'] = $this->form_validation->set_value('region', $team->region);
                $this->data['bio'] = array(
                    'name'  => 'bio',
                    'id'    => 'bio',
                    'value' => $this->form_validation->set_value('bio', $team->bio),
                );         
                $this->data['slot1'] = array(
                    'name'  => 'slot1',
                    'id'    => 'slot1',
                    'value' => $this->form_validation->set_value('slot1', $team->slot1),
                );         
                $this->data['slot2'] = array(
                    'name'  => 'slot2',
                    'id'    => 'slot2',
                    'value' => $this->form_validation->set_value('slot2', $team->slot2),
                );         
                $this->data['slot3'] = array(
                    'name'  => 'slot3',
                    'id'    => 'slot3',
                    'value' => $this->form_validation->set_value('slot3', $team->slot3),
                );         
                $this->data['slot4'] = array(
                    'name'  => 'slot4',
                    'id'    => 'slot4',
                    'value' => $this->form_validation->set_value('slot4', $team->slot4),
                );      
                $this->data['slot5'] = array(
                    'name'  => 'slot5',
                    'id'    => 'slot5',
                    'value' => $this->form_validation->set_value('slot5', $team->slot5),
                );         
                $this->data['slot6'] = array(
                    'name'  => 'slot6',
                    'id'    => 'slot6',
                    'value' => $this->form_validation->set_value('slot6', $team->slot6),
                );                      
                $this->twiggy->set('team', $team);
                $this->twiggy->set('data', $this->data);
                $this->twiggy->template('edit')->display();
            }
            else
            {    
                redirect('/', 'refresh');
            }

        }
                
    }
    
}