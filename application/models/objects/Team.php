<?php  
namespace Objects;
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Team
{
    protected $__captain = null;
    protected $__manager = null;
    public function __construct()
    {
        $this->id = 0;
        $this->name = '';
        $this->invites = 0;
        $this->logo = '';
        $this->contact = '';
        $this->region = 'USE';
        $this->contact_twitter = null;
        $this->contact_facebook = null;
        $this->contact_twitch = null;
        $this->players = array();
    }
    
    public function getCaptain()
    {
        if ($this->__captain != null)
            return $this->__captain;
        
        foreach($this->players as $player)
        {
            if (array_key_exists( 'isOwner', $player->bestGroup ) )  
            {
                $this->__captain = $player;
                break;
            }
        }    
        
        return $this->__captain;
    }
    
    public function getManager()
    {
        if ($this->__manager != null)
            return $this->__manager;
        
        foreach($this->players as $player)
        {
            if (array_key_exists( 'isManager', $player->bestGroup ) )  
            {
                $this->__manager = $player;
                break;
            }
        }    
        
        return $this->__manager;
    }
    
    public function setManager($player)
    {
        $this->__manager = $player;   
    }

    
    public function getStarters()
    {
        $players = array();
        foreach($this->players as $player)
        {
            if (array_key_exists( 'isOwner', $player->bestGroup ) )  
            {
                $players[] = $player;
            }
            else if (array_key_exists( 'isMember', $player->bestGroup ) )  
            {
                $players[] = $player;
            }
        }    
        return $players;
    }
    public function getSubs()
    {
        $players = array();
        foreach($this->players as $player)
        {
            if (array_key_exists( 'isSub', $player->bestGroup ) )  
            {
                $players[] = $player;
            }
        }    
        return $players;
    }
    
    public function getContactPlayer()
    {
        $p = $this->getManager();
        if ($p == null)
        {
            $p = $this->getCaptain();
            if ($p == null) return null;
        }
        
        return $p;
    }
}