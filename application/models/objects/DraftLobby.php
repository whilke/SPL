<?php  
namespace Objects;
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DraftLobby
{
    protected $draft_model = null;
    public $glory_ban = null;
    public $valor_ban = null;
    public $glory_picks = array();
    public $valor_picks = array();
    protected $teams = array();
    protected $static_round_time = 60;
    public function __construct()
    {
        $this->id = 0;
        $this->match_id = 0;
        $this->title = ""; 
        $this->state = 0;
        $this->glory_seat = null;
        $this->valor_seat = null;
        $this->round_time = 0;
        $this->glory_extra_time = 60;
        $this->valor_extra_time = 60;        
        $this->draft_model = null;
    }
    
    public function isUserValid($user)
    {
        //first lets see if this user is part of a team.
        if($user->team_id > 0)
        {
            if ($this->teams != null)
            {
                foreach($this->teams as $team)
                {
                    if ($team->id == $user->team_id)
                        return true;
                }                
            }
        }
        
        //check if this user is allowed already
        return $this->model->isValidUser($this->id, $user->id);
    }
    
    public function validatePickUser($user)
    {
        $pick = $this->getPickUser();
        if ($pick == null) 
        {return false;}
        
        return ($pick->id == $user->id);
    }
    
    public function getPickUser()
    {
        $state = $this->state;
         if ($state == 0 || $state >= 13) return null;
        
        
        if ($state == 1 ||
            $state == 3 ||
            $state == 6 ||
            $state == 7 ||
            $state == 10 ||
            $state == 11)            
            return $this->glory_seat;
        else
            return $this->valor_seat;
    }
    
    public function isGloryRound()
    {
         $state = $this->state;
         if ($state == 0 || $state >= 13) return false;
        
        
        if ($state == 1 ||
            $state == 3 ||
            $state == 6 ||
            $state == 7 ||
            $state == 10 ||
            $state == 11)            
            return true;
        else
            return false;
    }
    
    public function isTimerActive()
    {
         $state = $this->state;
         if ($state == 0 || $state >= 13) return false;
         
         return true;
    }
    
    public function pickHero($user, $heroId)
    {
        $pickUser = $this->getPickUser();
        if ($pickUser == null || $user->id != $pickUser->id) return;
        
        $state = $this->state;
        if ($state == 0 || $state >= 13) return;
        
        if ($state == 1)
        {
            $this->model->pickHero($this->id, $user->id, $heroId, 1);
        }
        else if ($state == 2)
        {
            $this->model->pickHero($this->id, $user->id, $heroId, 2);
        }
        else if ($state == 3 || $state == 6 || $state == 7 || $state == 10 | $state == 11)
        {
            $this->model->pickHero($this->id, $user->id, $heroId, 3);
        }
        else
        {
            $this->model->pickHero($this->id, $user->id, $heroId, 4);
        }
               
        $obj = array();

        if ($this->getRoundTimeLeft() < 0)
        {
            $u = 0;
            if ($this->isGloryRound())
            {
                $u = $this->glory_extra_time + $this->getRoundTimeLeft();
                if ($u < 0) $u = 0;
                $obj['glory_extra_time'] = $u;
            }
            else
            {
                $u = $this->valor_extra_time + $this->getRoundTimeLeft();
                if ($u < 0) $u = 0;
                $obj['valor_extra_time'] = $u;
            }
        }
        
        $this->state++; 
        $obj['state'] = $this->state;
        
        if ($state == 1 || $state  == 2 || $state == 3 || $state == 5 ||
                $state == 7 || $state == 9 || $state == 11 || $state == 12)
            $obj['round_time'] = time()+ $this->static_round_time;
        
        
       
        
        $this->model->update($this->id, $obj);
        
        return true;
    }
    
    public function isUserSitting($user)
    {
        if ($this->glory_seat != null && $this->glory_seat->id == $user->id)
            return true;
        if ($this->valor_seat != null && $this->valor_seat->id == $user->id)
            return true;
        
        return false;
    }
    
    public function sitDown($isGlory, $user)
    {
        $isSitting = $this->isUserSitting($user);
        if ($isGlory)
        {
            if ($this->glory_seat != null && $this->glory_seat->id == $user->id)
            {
                $this->glory_seat = null;
                $obj = array();
                $obj['glory_seat'] = null;
                $this->model->update($this->id, $obj);           
            }
            else if ($this->glory_seat == null)
            {
                $obj = array();
                if ($this->valor_seat != null && $this->valor_seat->id == $user->id)
                {
                    $this->valor_seat = null;
                    $obj['valor_seat'] = null;
                }
                $this->glory_seat = $user;                
                $obj['glory_seat'] = $user->id;
                $this->model->update($this->id, $obj);           
            }
            
        }
        else
        {
            if ($this->valor_seat != null && $this->valor_seat->id == $user->id)
            {
                $this->valor_seat = null;
                $obj = array();
                $obj['valor_seat'] = null;
                $this->model->update($this->id, $obj); 
            }
            else if ($this->valor_seat == null)
            {
                $obj = array();
                if ($this->glory_seat != null && $this->glory_seat->id == $user->id)
                {
                    $this->glory_seat = null;
                    $obj['glory_seat'] = null;                    
                }
                
                $this->valor_seat = $user;
                
                $obj['valor_seat'] = $user->id;
                $this->model->update($this->id, $obj);                             
            }
        }
        
        if ($this->glory_seat != null && $this->valor_seat != null)
        {
            $obj = array();
            $obj['state'] = 1;
            $this->model->update($this->id, $obj);                             
        }
    }
    
    public function randomPick()
    {
        $this->pickHero( $this->getPickUser(), $this->getRandomHero());
    }
    
    protected function getRandomHero()
    {
        $heroList = $this->model->getHeroList();
        shuffle($heroList);
        
        //find the first hero not selected
        foreach($heroList as $hero)
        {
            if ($this->glory_ban != null && $this->glory_ban == $hero->id)
                continue;
            if ($this->valor_ban != null && $this->valor_ban == $hero->id)
                continue;

            $bFound = false;
            foreach($this->glory_picks as $gp)
            {
                if ($gp == $hero->id)
                {
                    $bFound = true;
                    break;
                }
            }
            if ($bFound) continue;
            
            foreach($this->valor_picks as $vp)
            {
                if ($vp == $hero->id)
                {
                    $bFound = true;
                    break;
                }
            }
            
            if ($bFound) continue;
            
            return $hero->id;
        }
    }
    
    public function addTeam($id)
    {
        $this->teams[] = $id;
    }
    
    public function getRoundTimeLeft()
    {
        $t = $this->round_time - time();
        
        return $t;
    }
    
    public function isTimerExpired()
    {
        $t = $this->getRoundTimeLeft();
        if ($t < 0)
        {
            if ($this->isGloryRound())
                return ($t + $this->glory_extra_time) < 0 ;
            else
                return ($t + $this->valor_extra_time) < 0 ;
                
        }
    }
            
    public function getGloryBan()
    {
        return $this->glory_ban;
    }
    
    public function getValorBan()
    {
        return $this->valor_ban;
    }
    
    public function getGloryPicks()
    {
        return $this->glory_picks;
    }
    
    public function getValorPicks()
    {
        return $this->valor_picks;
    }
   
    
}