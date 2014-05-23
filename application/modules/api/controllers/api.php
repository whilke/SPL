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
        
        $matchid = $this->get('match');
        if (!$matchid) $matchid = 0;
        
        $matches = $this->Teams_model->getPlayerMatchStats($id, $matchid);
        
        if ($matches == null)
        {
            $player->matches = new stdClass();
            $player->AvgStats = new stdClass();
        }
        else
        {
            if ($matchid != 0)
            {
                $player->stats = $matches[0];
            }
            else
            {
                $gpm = 0.0;
                $avgLen = 0.0;
                $avgKills = 0;
                $avgDeaths = 0;
                $avgAssists = 0;
                $avgkda = 0.0;
                $avgCreeps = 0;
                $avgNeut = 0;

                $totalMatches = 0;
                foreach($matches AS $match)
                {
                    $gpm += $match->gpm;
                    $avgLen += $match->matchlength;
                    $avgKills += $match->kills;
                    $avgDeaths += $match->deaths;
                    $avgAssists += $match->assists;
                    $avgCreeps += $match->creeps;
                    $avgNeut += $match->neutrals;

                    $totalMatches++;
                }

                $d = $avgDeaths;
                if ($d == 0) $d = 1;
                $avgkda = round(($avgKills + $avgAssists) / $d, 1);

                $gpm =  round($gpm / $totalMatches);
                $avgLen = round($avgLen / $totalMatches);
                $avgKills = round($avgKills / $totalMatches);
                $avgDeaths = round($avgDeaths / $totalMatches);
                $avgAssists = round($avgAssists / $totalMatches);
                $avgCreeps = round($avgCreeps / $totalMatches);
                $avgNeut = round($avgNeut / $totalMatches);

                $player->AvgStats = new stdClass();
                $player->AvgStats->gpm = $gpm;
                $player->AvgStats->kda = $avgkda;
                $player->AvgStats->matchlength = $avgLen;
                $player->AvgStats->kills = $avgKills;
                $player->AvgStats->deaths = $avgDeaths;
                $player->AvgStats->assists = $avgAssists;
                $player->AvgStats->creeps = $avgCreeps;
                $player->AvgStats->neuatrals = $avgNeut;

                $player->matches = $matches;                   
            }
                    
        }

        $this->response($player, 200);                

    }
}