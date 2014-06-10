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
            
            //fixup a basic team to support player objects.
            $realTeam = new stdClass();
            $realTeam->id = $team->id;
            $realTeam->name = $team->name;
            $realTeam->logo = $team->logo;
            $realTeam->contact = $team->contact;
            $realTeam->region = $team->region;
            $realTeam->contact_twitter = $team->contact_twitter;
            $realTeam->contact_facebook = $team->contact_facebook;
            $realTeam->contact_twitch = $team->contact_twitch;
            $realTeam->players = array();
            
            //check each slot for a real account.
            $this->_mergePlayerToTeam($realTeam, $team->captain, $team->captain_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot1, $team->slot1_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot2, $team->slot2_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot3, $team->slot3_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot4, $team->slot4_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot5, $team->slot5_strife_id, true);
            $this->_mergePlayerToTeam($realTeam, $team->slot6, $team->slot6_strife_id, true);
            
            return $realTeam;
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
            
            //fixup a basic team to support player objects.
            $realTeam = new stdClass();
            $realTeam->id = $team->id;
            $realTeam->name = $team->name;
            $realTeam->logo = $team->logo;
            $realTeam->contact = $team->contact;
            $realTeam->region = $team->region;
            $realTeam->contact_twitter = $team->contact_twitter;
            $realTeam->contact_facebook = $team->contact_facebook;
            $realTeam->contact_twitch = $team->contact_twitch;
            $realTeam->players = array();
            
            //check each slot for a real account.
            $this->_mergePlayerToTeam($realTeam, $team->captain, $team->captain_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot1, $team->slot1_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot2, $team->slot2_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot3, $team->slot3_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot4, $team->slot4_strife_id);
            $this->_mergePlayerToTeam($realTeam, $team->slot5, $team->slot5_strife_id, true);
            $this->_mergePlayerToTeam($realTeam, $team->slot6, $team->slot6_strife_id, true);
            
            return $realTeam;
        }
        
        return NULL;   
    }
    
    private function _mergePlayerToTeam($team, $slotName, $slotId, $isSub=false)
    {
        if ($slotName == null) return;
        
        $p = NULL;
        $plr = $this->_findPlayerForTeam($team->id, $slotName);
        if ($plr == NULL)
        {
            $p = new stdClass();
            $p->name = $slotName;
            $p->id = 0;
            $p->strife_id = $slotId;
            $p->converted = false;
            if ($isSub)
            {
                $p->bestGroup = ['isSub' => true];            
            }
            else
            {
                $p->bestGroup = ['isMember' => true];
            }
        }
        else
        {
            $p = new stdClass();
            $p->name = $plr->username;
            $p->id = $plr->id;
            $p->strife_id = $plr->strife_id;
            $p->converted = true;
            $p->bestGroup = $plr->bestGroup;
        }
        
        if ($p->strife_id <= 0)
            $p->strife_id = 0;

        $team->players[] = $p;
    }
    private function _findPlayerForTeam($teamid, $pname)
    {
        if ($pname == "") return NULL;

        $query = $this->db->
            from('users u')->
            where('u.username', $pname)->
            where('u.team_id', $teamid)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $player = $query->row();
            $player->bestGroup = $this->_findBestGroupForPlayer($player->id);
            return $player;
        }
        return NULL;
    }
    
    private function _findBestGroupForPlayer($playerId)
    {
        $query = $this->db->
            from('users_groups ug')->
            join('groups g', 'g.id = ug.group_id')->
            where('ug.user_id', $playerId)->
            get();

        $bIsSub = false;
        $bIsMember = false;
        $bIsManager = false;
        $bIsOwner = false;
        
        foreach($query->result() as $row)
        {
            if ($row->name == "teamsub")
            {
                $bIsSub = true;
            }
            else if ($row->name == "members")
            {
                $bIsMember = true;
            }
            else if ($row->name == "teampow")
            {
                $bIsManager = true;
            }
            else if ($row->name == "teamowner")
            {
                $bIsOwner = true;            
            }
        }
        
        if ($bIsOwner)
        {
            return ['isOwner'=> true];
        }
        else if ($bIsManager)
        {
            return ['isManager'=> true];
        }
        else if ($bIsMember)
        {
            return ['isMember'=> true];
        }
        else if ($bIsSub)
        {
            return ['isSub'=> true];
        }
        else
        {
            return ['isSub'=> false];
        }
    }
    
    function addNewTeam($teamname, $userid)
    {
        $data = array(
            'name'=>$teamname,
            'userid'=>$userid
        );
        
        $this->db->insert('teams', $data);
    }
    
    function edit($teamid, $data = array())
    {
        $team = $this->getById($teamid);
             
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
    
    function editPlayerLinks($team)
    {
        $this->db->trans_begin();
         
        $this->db->update('teams', $team, array('id' => $team->id));
         
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
    
    function getPlayerByName($name)
    {
        $query = $this->db->
            select('*')->
            from('players p')->
            where('p.name', $name)->
            limit(1)->
            get();

           
        if ($query->num_rows() === 1)
        {
            return $query->row();
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
    
    function getTeamForPlayer($playerId)
    {
        $sql= "SELECT t.name  FROM 
        stats s
        join players p on p.strife_id = s.player_id
        join teams t on t.id = s.team_id
        where p.strife_id=?
        order by match_id desc
        LIMIT 1";
        
        $query = $this->db->
                query($sql, $playerId);

           
        if ($query->num_rows() === 1)
        {
            return $query->row();
        }
        
        return NULL;   
    }
    
    function getActivePlayersInSeason($seasonId)
    {
        $sql = "select DISTINCT(p.name) as name, p.strife_id
	from players p
	join stats s on s.player_id = p.strife_id
	join matches m on m.id = s.match_id
	where m.seasoN_id=?";
        
        $query = $this->db->
                query($sql, $seasonId);

           
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;
        
        return NULL;    
    }
    
    function getPlayerMatchStats($playerId, $matchid=0, $seasonId=0)
    {
        $sql = "SELECT m.id,t.name as teamname, TRIM( LEADING 'Hero_' FROM h.name) as hero, 
                    TRIM( LEADING 'Familiar_' FROM pets.name) as pet,
                    m.length / 1000 + 90 as matchlength, 
                    ROUND(s.total_gold / (m.length / 1000 / 60 + 1.5))  as gpm,
                    s.kills, s.assists, s.deaths,
                    IF(s.deaths=0, (s.kills+s.assists), ROUND((s.kills+s.assists) / s.deaths,1)) as kda,
                    s.total_creep as creeps, s.total_neut as neutrals
                    FROM spl.players p
                    join stats s on s.player_id = p.strife_id
                    join matches m on m.id = s.match_id
                    join teams t on t.id = s.team_id
                    join heroes h on h.id = s.hero_id
                    join pets pets on pets.id = s.pet_id
                    where p.strife_id= ?";
        
        if ($matchid != 0)
        {
            $sql = $sql . " AND m.id= " . $matchid; 
        }
        else if ($seasonId != 0)
        {
            $sql = $sql . " AND m.season_id= " . $seasonId;             
        }
        
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
