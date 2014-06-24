<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/TrueSkill/GameInfo.php';
require_once APPPATH . 'libraries/TrueSkill/Player.php';
require_once APPPATH . 'libraries/TrueSkill/Team.php';
require_once APPPATH . 'libraries/TrueSkill/TrueSkill/TwoTeamTrueSkillCalculator.php';

class trueskill
{
    public function __construct()
    {
        $this->ci =& get_instance();
        $dbGameInfo = $this->ci->lobbys_model->getGameDefaults();
        
        $this->gameInfo = new Moserware\Skills\GameInfo($dbGameInfo->init_mean,$dbGameInfo->init_sd,
                $dbGameInfo->beta, $dbGameInfo->dynamics_factor, $dbGameInfo->draw_prob);
        
        $this->calc = new Moserware\Skills\TrueSkill\TwoTeamTrueSkillCalculator();
        
    }
    
    public function getDefaultScore()
    {
        return $this->gameInfo->getDefaultRating()->getConservativeRating();
    }
    
    public function getDefaultRating()
    {
        return $this->gameInfo->getDefaultRating();
    }
    
    public function getRating($mean, $sd)
    {
        return new Moserware\Skills\Rating($mean, $sd);
    }
    
    public function createPlayer($player)
    {
        return new Moserware\Skills\Player($player);
    }
    
    public function createTeam()
    {
        return new Moserware\Skills\Team();
    }
        
    public function getMatchQuality($team1, $team2)
    {
        return round($this->calc->calculateMatchQuality($this->gameInfo, array($team1, $team2)) * 100.0,0);
    }
    
    public function getNewRating($team1, $team2, $teamOneWon=true)
    {
        $ranks = null;
        if ($teamOneWon)
            $ranks = array(1, 2);
        else
            $ranks = array(2, 1);
            
        return $this->calc->calculateNewRatings($this->gameInfo, array($team1, $team2), $ranks);
    }
}