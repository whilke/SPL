<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'models/objects/DraftLobby.php';

class Draft_model extends CI_Model
{  
    public function __construct()
    {
        parent::__construct();
    }
    
    private function convertToObject($props)
    {
        $o = new Objects\DraftLobby();
        $o->model = $this;
        $o->id = $props->id;
        $o->match_id = $props->match_id;
        $o->title = $props->title;
        $o->state = $props->state;
        $o->password = $props->password;
        
        if ($props->glory_seat > 0)
        {
            $o->glory_seat = $this->getDraftUser($props->glory_seat);
        }
        if ($props->valor_seat > 0)
        {
            $o->valor_seat = $this->getDraftUser($props->valor_seat);
        }
        

        $o->round_time = $props->round_time;
        $o->glory_extra_time = $props->glory_extra_time;
        $o->valor_extra_time = $props->valor_extra_time;
        
        $teams = $this->getDraftTeams($o->id);
        if ($teams != null)
        {
            foreach($teams as $team)
            {
                $o->addTeam($team->team_id);
            }            
        }
        
        $picks = $this->getPicks($o->id);
        if ($picks != null)
        {
            foreach($picks as $pick)
            {
                if ($pick->pick_type == 1)
                {
                    $o->glory_ban = $pick->hero_id;
                }
                else if ($pick->pick_type == 2)
                {
                    $o->valor_ban = $pick->hero_id;                    
                }
                else if ($pick->pick_type == 3)
                {
                    $o->glory_picks[] = $pick->hero_id;                    
                }            
                else if ($pick->pick_type == 4)
                {
                    $o->valor_picks[] = $pick->hero_id;                    
                }                            
            }
        }
        
        return $o;
    }
    
    public function getHero($id)
    {
          $query = $this->db->
            select('*')->
            from('heroes')->
            where('id', $id)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            return $r;            
        }
        
        return null;           
    }
    
    public function getHeroList()
    {
         $query = $this->db
         ->from('heroes')
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $row;
        }
        return $data;    
    }
    
    public function getDraftUser($uId)
    {
        $query = $this->db->
            select('u.*')->
            from('users u')->
            where('u.id', $uId)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            return $r;            
        }
        
        return null;       
    }
    
    public function getDraftTeams($id)
    {
         $query = $this->db
         ->from('draft_teams')
         ->where('draft_id', $id)
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $row;
        }
        return $data;      
    }
    
    public function add($draft)
    {
        $props['match_id'] = $draft->match_id;
        $props['title'] = $draft->title;
        $props['state'] = 0;
        $props['glory_extra_time'] = $draft->glory_extra_time;
        $props['valor_extra_time'] = $draft->valor_extra_time;
        
        $this->db->insert('draft_lobby', $props);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function update($id, $draft)
    {
        $this->db->update('draft_lobby',$draft, array('id' => $id));
    }
    
    public function delete($id)
    {
        $this->db->delete('draft_lobby', array('id'=>$id));        
    }
    
    public function get($id)
    {
         $query = $this->db->
            select('d.*')->
            from('draft_lobby d')->
            where('d.id', $id)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            
            return $this->convertToObject($r);
            
        }
        
        return null;
    }
    
    public function getAll()
    {
        $query = $this->db
         ->from('draft_lobby')
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $this->convertToObject($row);
        }
        return $data;        
    }      
    
    public function pickHero($draft_id, $user_id, $hero_id, $type, $isR=false)
    {
        $data = array(
            'draft_id'=>$draft_id,
            'user_id'=>$user_id,
            'hero_id'=>$hero_id,
            'pick_type'=>$type,
        );
        
        $this->db->insert('draft_picks', $data);
        $id = $this->db->insert_id();
        
        $hero = $this->getHero($hero_id);
        
        if ($type == 1 || $type == 2)
        {
            if ($isR)
                $this->addChat($draft_id, $user_id, ("Random Bans " . $hero->desc) );
            else
                $this->addChat($draft_id, $user_id, ("Bans " . $hero->desc) );            
        }
        else
        {
            if ($isR)
                $this->addChat($draft_id, $user_id, ("Randoms " . $hero->desc) );
            else
                $this->addChat($draft_id, $user_id, ("Picks " . $hero->desc) );
        }
            
        
        return $id;
    }
    
    public function getPicks($draft_id)
    {
       $query = $this->db
         ->from('draft_picks')
         ->where('draft_id', $draft_id)
         ->order_by('timestamp')
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $row;
        }
        return $data;      
    }
    
    public function addUser($id, $userId)
    {
        $props['draft_id'] = $id;
        $props['user_id'] = $userId;
        
        $this->db->insert('draft_users', $props);
        $id = $this->db->insert_id();
        
        return $id;       
        
    }
    
    public function isValidUser($id, $userId)
    {
        $query = $this->db->
            select('du.*')->
            from('draft_users du')->
            where('du.draft_id', $id)->
            where('du.user_id', $userId)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            return true;            
        }
        
        return false;     
    }
    
    public function addChat($id, $pId, $msg)
    {
        $props['draft_id'] = $id;
        $props['user_id'] = $pId;
        $props['data'] = $msg;
        $props['timestamp'] = microtime(true);
        
        $this->db->insert('draft_chat', $props);
        $id = $this->db->insert_id();
        
        return $id;        
    }    
    
    public function getChat($id, $timestamp)
    {
        
        $query = $this->db
         ->select('users.username, data')
         ->from('draft_chat')
         ->join('users', 'users.id = draft_chat.user_id')
         ->where('draft_id', $id)
         ->where('timestamp >', $timestamp)
         ->order_by('timestamp')
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {          
           $data[] = $row;
        }
        return $data;                
        
    }       
}