<?php defined('BASEPATH') OR exit('No direct script access allowed');

class api extends REST_Controller
{
    function __construct()
    {
        $this->load->database();
        
        parent::__construct();
    }
   
    function teams_get()
    {
        $this->load->model('Teams_model');
                
        $teams = $this->Teams_model->get_list();
        $this->response($teams, 200);       
    }
    
    function team_get()
    {
        $id = $this->get('id');
        
        if(!$id)
        {
            $this->response(NULL, 400);
            return;
        }
         
        $this->load->model('Teams_model');
        
        $raw_team = $this->Teams_model->getById($id);
        
        if ($raw_team == null)
        {
            $this->response(NULL, 400);
            return;            
        }
        
        $team = new stdClass();
        $team->id = $raw_team->id;
        $team->name = $raw_team->name;
        $team->logo = $raw_team->logo;
        $team->region = $raw_team->region;
        $team->contact = $raw_team->contact;
        
        $team->players = array();        
        $team->players[] = $this->Teams_model->getPlayer($raw_team->captain_strife_id);
        $team->players[] = $this->Teams_model->getPlayer($raw_team->slot1_strife_id);
        $team->players[] = $this->Teams_model->getPlayer($raw_team->slot2_strife_id);
        $team->players[] = $this->Teams_model->getPlayer($raw_team->slot3_strife_id);
        $team->players[] = $this->Teams_model->getPlayer($raw_team->slot4_strife_id);
        $team->players[] = $this->Teams_model->getPlayer($raw_team->slot5_strife_id);
        $team->players[] = $this->Teams_model->getPlayer($raw_team->slot6_strife_id);


        $this->response($team, 200);                
    }
    
    function player_get()
    {
        $id = $this->get('id');
        
        if(!$id)
        {
            $this->response(NULL, 400);
            return;
        }

        $this->load->model('Teams_model');

        $player = $this->Teams_model->getPlayer($id);
        if ($player == null)
        {
            $this->response(NULL, 400);
            return;            
        }
        
        $matches = $this->Teams_model->getPlayerMatchStats($id);
        if ($matches == null)
            $player->matches = null;
        else
            $player->matches = $matches;

        $this->response($player, 200);                

    }
}