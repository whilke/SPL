<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  Ion Auth Model
*
* Author:  Ben Edmunds
*          ben.edmunds@gmail.com
*          @benedmunds
*
* Added Awesomeness: Phil Sturgeon
*
* Location: http://github.com/benedmunds/CodeIgniter-Ion-Auth
*
* Created:  10.01.2009
* 
* Last Change: 3.22.13
*
* Changelog:
* * 3-22-13 - Additional entropy added - 52aa456eef8b60ad6754b31fbdcc77bb
* 
* Description:  Modified auth system based on redux_auth with extensive customization.  This is basically what Redux Auth 2 should be.
* Original Author name has been kept but that does not mean that the method has not been modified.
*
* Requirements: PHP5 or above
*
*/

class Teams_model extends CI_Model
{
    function __construct()
    {
         parent::__construct();
    }

    function get_list()
    {
        $query = $this->db->
                select('teams.id, teams.name')->
                from('teams')->
                join('users', 'users.id = teams.userid')->
                where('active', true)->
                get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = array('id'=> $row->id, 'name' => $row->name);
        }
        return $arr;
    }
    
    function get($teamname)
    {
        $query = $this->db->
                where('name', $teamname)->
                limit(1)->
                from('teams')->
                get();
        
        if ($query->num_rows() === 1)
        {
            $team = $query->row();
            return $team;
        }
        
        return NULL;
        
    }
    
    function getById($id)
    {
        $query = $this->db->
            from('teams t')->
            where('id', $id)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $team = $query->row();
            return $team;
        }
        
        return NULL;   
    }
    
    function addNewTeam($teamname, $userid)
    {
        $data = array(
            'name'=>$teamname,
            'userid'=>$userid
        );
        
        $this->db->insert('teams', $data);
    }
    
    function edit($teamid, $captain, $contact, $region, $data = array())
    {
        $team = $this->getById($teamid);
      
        $data['captain'] = $captain;
        $data['contact'] = $contact;
        $data['region'] = $region;
        
        $this->db->trans_begin();
         
        $this->db->update('teams', $data, array('id' => $team->id));
         
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;        
    }
    
    function getEmail($teamId)
    {
        $query = $this->db->
                select('u.email')->
                from('teams t')->
                join('users u', 'u.teamname=t.name')->
                where('t.id', $teamId)->
                limit(1)->
                get();
        
        if ($query->num_rows() === 1)
        {
            $team = $query->row();
            return $team->email;
        }
        
        return NULL;

    }
    
    function getPlayer($playerId)
    {
        $query = $this->db->
            select('*')->
            from('players p')->
            where('p.strife_id', $playerId)->
            limit(1)->
            get();

           
        if ($query->num_rows() === 1)
        {
            return $query->row();
        }
        
        return NULL;        
    }
    
    function getPlayerMatchStats($playerId)
    {
        $sql = 'SELECT m.id,t.name as teamname, m.length / 1000 + 90 as matchlength, 
                    ROUND(s.total_gold / (m.length / 1000 / 60 + 1.5))  as gpm,
                    s.kills, s.assists, s.deaths,
                    IF(s.deaths=0, (s.kills+s.assists), ROUND((s.kills+s.assists) / s.deaths,1)) as kda,
                    s.total_creep as creeps,
                    s.total_neut as neutrals
                    FROM spl.players p
                    join stats s on s.player_id = p.strife_id
                    join matches m on m.id = s.match_id
                    join teams t on t.id = s.team_id
                    where p.strife_id= ?';
        
        $query = $this->db->
                query($sql, $playerId);

           
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;
        
        return NULL;          
    }
}
