<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class lobbys_model extends CI_Model
{
    public function getGameDefaults()
    {
         $query = $this->db->
            select('*')->
            from('game_info_global')->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            return $r;
        }
        
        return null;        
    }
    
    public function add($hostId, $name, $props)
    {
        $props['host'] = $hostId;
        $props['name'] = $name;
        
        $this->db->insert('lobbys', $props);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function update($id, $props)
    {
        $this->db->update('lobbys',$props, array('id' => $id));
    }
    
    public function delete($id)
    {
        $this->db->delete('lobbys', array('id'=>$id));        
    }
    
    public function get($id)
    {
         $query = $this->db->
            select('lobbys.*, users.username as host_name')->
            from('lobbys')->
            join('users', 'users.id = lobbys.host')->
            where('lobbys.id', $id)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            $r->players = $this->getPlayers($r->id);
            return $r;
        }
        
        return null;
    }
    
    public function getAll()
    {
        $query = $this->db
         ->from('lobbys')
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $row->players = $this->getPlayers($row->id);
           $data[] = $row;
        }
        return $data;        
    }      
    
    public function getPlayers($id)
    {
        $query = $this->db
         ->select('lobby_players.*, users.username, users.rating_mean, users.rating_sd, users.rating_games')
         ->from('lobby_players')
         ->join('users', 'users.id = player_id')
         ->where('lobby_id', $id)
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $row;
        }
        return $data;       
    }

    public function pingLobby($id)
    {
        $props = array();
        $props['timestamp'] = date("Y-m-d H:i:s");
        $this->db->update('lobbys',$props, array('id' => $id));
    }
    
    public function pingPlayer($id, $pId)
    {
        $props = array();
        $props['timestamp'] = date("Y-m-d H:i:s");
        $this->db->update('lobby_players',$props, array('player_id' => $pId, 'lobby_id'=>$id));
        
        $this->pingLobby($id);
    }
    
    public function delPlayer($lobby, $pId)
    {
        //only delete if not on a team in prog.
        if ($lobby->inprogress == 1 || $lobby->complete == 1)
        {
            foreach($lobby->players AS $player)
            {
                if ($player->player_id == $pId)
                {
                    $slot = $player->role-1;
                    if ($slot >=1 && $slot<=10)
                        return;
                }
            }
        }
        
        $this->db->delete('lobby_players', array('player_id' => $pId, 'lobby_id'=>$lobby->id));            
        $data = array();
        $data['playerCount'] = $lobby->playerCount-1;
        $this->update($lobby->id, $data);        
    }
    
    public function updateplayer($id, $pId, $props)
    {
        $props['timestamp'] = date("Y-m-d H:i:s");
        $this->db->update('lobby_players', $props, array('player_id' => $pId, 'lobby_id'=>$id));
        $this->pingLobby($id);
    }
    
    public function addPlayer($lobby, $player)
    {      
        if ($lobby == null) return;
        //is this user already added?
        restart:
        $bAdded = false;
        foreach($lobby->players AS $p)
        {
            //check if the player is timed out.
            $timestamp = new DateTime($p->timestamp);
            $time = date("Y-m-d H:i:s");
            $now = new DateTime($time);
            
            $diff = $now->diff($timestamp);                
            $days = intval( $diff->format('%d') );
            $mins = intval( $diff->format('%i') );
            
            if (($days > 0 || $mins >= 15) && $player->id != $p->player_id)
            {
                if ($lobby->complete == 0 && $lobby->inprogress == 0)
                {
                    //remove this player
                    $this->delPlayer($lobby, $p->player_id);
                    $lobby = $this->get($lobby->id);
                    //if the player was the host, get a new host
                    if ($p->player_id == $lobby->host)
                    {
                        $pNewHost = 0;
                        if (sizeof($lobby->players) > 0)
                            $pNewHost = $lobby->players[0]->player_id;
                        if ($pNewHost > 0)
                        {
                          $data = array();
                          $data['host'] = $pNewHost;
                          $this->update($lobby->id, $data);
                          $lobby->host = $pNewHost;
                        }
                        else
                        {
                            //this lobby has no players, delete it.
                            $this->delete($lobby->id);
                            return;
                        }
                    }

                    goto restart;
                }   
            }
            
            if ($p->player_id == $player->id)
            {
                $bAdded = true;
                break;
            }
        }
        
        if (!$bAdded)
        {           
            $props = array();
            $props['player_id'] = $player->id;
            $props['lobby_id'] = $lobby->id;
            $props['role'] = 0;

            $this->db->insert('lobby_players', $props);
            $id = $this->db->insert_id();

            if ($id != false)
            {
                //if not the host, add them
                if ($lobby->host != $player->id)
                {
                    $data = array();
                    $data['playerCount'] = $lobby->playerCount+1;
                    $this->update($lobby->id, $data);
                }
            }
            return $id;
       }
       else
       {
           //update the timestamp
           $this->pingPlayer($lobby->id, $player->id);
       }
        
        return false;
    }
    
    public function playerVote($id, $pId)
    {
        $props = array();
        $props['voted'] = true;
        $this->updateplayer($id, $pId, $props);
    }
    
    public function addChat($id, $pId, $msg)
    {
        $props['lobby_id'] = $id;
        $props['player_id'] = $pId;
        $props['msg'] = $msg;
        $props['timestamp'] = microtime(true);
        
        $this->db->insert('lobby_chat', $props);
        $id = $this->db->insert_id();
        
        $this->pingLobby($id);
        return $id;        
    }

    public function getChat($id, $timestamp)
    {
        
        $query = $this->db
         ->select('users.username, msg')
         ->from('lobby_chat')
         ->join('users', 'users.id = lobby_chat.player_id')
         ->where('lobby_id', $id)
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
    
    public function getLadderNotice()
    {
        $date = new DateTime();
        $date->sub(new DateInterval('PT1H'));
        
        $query = $this->db
                ->from('lobby_notice')
                ->where('timestamp <', date_format($date, "Y-m-d H:i:s"))
                ->limit(1)
                ->get();
        
        if ($query->num_rows() == 1)
        {
            //first cleanup anything older then an hour
            $query = $this->db
                    ->delete('lobby_notice',
                      array('timestamp <'=>date_format($date, "Y-m-d H:i:s"))
                            );            
        }
        
        
        //now grab the latest notice.
        $query = $this->db
                ->select('lobby_notice.*,users.username')
                ->from('lobby_notice')
                ->join('users', 'users.id=lobby_notice.player_id')
                ->order_by('timestamp', 'desc')
                ->limit(1)
                ->get();
        
        if ($query->num_rows() == 1)
        {
            $r = $query->row();
            return $r;
        }
        return null;                
    }
    
    public function addNotice($id, $msg)
    {        
        $props['player_id'] = $id;
        $props['msg'] = $msg;
        
        $this->db->insert('lobby_notice', $props);
        $id = $this->db->insert_id();
        return $id;                
    }
    
    public function getUsersForNotice()
    {
        $query = $this->db
            ->select('u.*')
            ->from('users u')
            ->join('users_extra ue', 'ue.id = u.id')
            ->where('rating_email', true)
            ->get(); 
        
        $data = array();
        foreach($query->result() as $row)
        {          
           $data[] = $row;
        }
        return $data;  
    }
}