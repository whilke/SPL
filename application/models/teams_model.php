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

require_once APPPATH . 'models/objects/Team.php';


class Teams_model extends CI_Model
{
    function __construct()
    {
         parent::__construct();
         
         $CI =& get_instance();
    }
    
    function add($name)
    {
        $chk = $this->get($name);
        if ($chk != NULL) return false;
        
        return $this->addNewTeam($name);
               
    }

    function get_list()
    {
        $query = $this->db->
                select('teams.id, teams.name')->
                from('teams')->
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
            $realTeam = new Objects\Team();
            $realTeam->id = $team->id;
            $realTeam->name = $team->name;
            $realTeam->invites = $team->invites;
            $realTeam->logo = $team->logo;
            $realTeam->contact = $team->contact;
            $realTeam->region = $team->region;
            $realTeam->contact_twitter = $team->contact_twitter;
            $realTeam->contact_facebook = $team->contact_facebook;
            $realTeam->contact_twitch = $team->contact_twitch;
            $realTeam->buys = $team->buys;
            $realTeam->players = $this->getPlayersForTeam($team->id);
            
            //first grab the upgraded player list.
            
            
            //see if there are any other registered players.
            
            
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
            $realTeam = new Objects\Team();
            $realTeam->id = $team->id;
            $realTeam->name = $team->name;
            $realTeam->logo = $team->logo;
            $realTeam->invites = $team->invites;
            $realTeam->contact = $team->contact;
            $realTeam->region = $team->region;
            $realTeam->contact_twitter = $team->contact_twitter;
            $realTeam->contact_facebook = $team->contact_facebook;
            $realTeam->contact_twitch = $team->contact_twitch;
            $realTeam->buys = $team->buys;
            $realTeam->players = $this->getPlayersForTeam($team->id);
                        
            //check if the team has a manager.
            if ($team->manager_id != null)
            {
                $query = $this->db->
                    from('users u')->
                    where('u.id', $team->manager_id)->
                    limit(1)->
                    get();
                
                if ($query->num_rows() === 1)
                {
                    $player = $query->row();
                    $realTeam->setManager($player);                    
                }                                
            }
            
            return $realTeam;
        }
        
        return NULL;   
    }
    
   
    
    
    private function getPlayersForTeam($teamid)
    {
        $query = $this->db->
            from('users u')->
            where('u.team_id', $teamid)->
            get();
        
        $players = array();
        foreach($query->result() as $row)
        {
            $plr = $row;
            $plr->bestGroup = $this->_findBestGroupForPlayer($plr->id);
            
            $p = new stdClass();
            $p->name = $plr->username;
            $p->id = $plr->id;
            $p->strife_id = $plr->strife_id;
            $p->converted = true;
            $p->bestGroup = $plr->bestGroup;
            
            $players[] = $p;
        }
        
        return $players;
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
        else if ($bIsSub)
        {
            return ['isSub'=> true];
        }
        else if ($bIsMember)
        {
            return ['isMember'=> true];
        }
        else
        {
            return ['isSub'=> false];
        }
    }
    
    public function addNewTeam($teamname)
    {
        $data = array(
            'name'=>$teamname,
            'region'=>'USE',
            'active'=>true,
            'invites'=>2,
        );
        
        $this->db->insert('teams', $data);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function joinUser($teamId, $userId, $seasonId)
    {
        //first remove any owner/mod/sub groups from this user since it's a fresh join.
        $CI =& get_instance();
        $CI->ion_auth->remove_from_group(array(5,6,7), $userId);
        
        //join this user to the group as a sub
        $CI->ion_auth->update($userId, array('team_id'=>$teamId, 'isregplayer'=>1) ); //now a registered player
        $this->promote($teamId, $userId, 3);
        
        //add a record for joining this team.
        $data = array(
            'user_id'=>$userId,
            'new_team'=>$teamId,
            'season_id'=>$seasonId,
        );
        
        $this->db->insert('users_team_history', $data);
    }
    
    public function removeUser($teamId, $userId, $seasonId, $bUserLeft=true)
    {
        //remove this user from any team groups.
        $CI =& get_instance();
        $CI->ion_auth->remove_from_group(array(5,6,7), $userId);
        $CI->ion_auth->update($userId, array('team_id'=>0, 'isregplayer'=>1) ); //quiting a team makes you a registered player still        
        
        //add a record for leaving this team.
        $data = array(
            'user_id'=>$userId,
            'old_team'=>$teamId,
            'season_id'=>$seasonId,
            'quit'=>$bUserLeft,
        );
        
        $this->db->insert('users_team_history', $data);
    }
    
    public function promote($teamId, $userId, $level)
    {
        $groups = array();
        $CI =& get_instance();
        
        if ($level == 0)
        {
            $groups = array(2,5);
        }
        else if ($level == 1)
        {
            $groups = array(2,6);
        }
        else if ($level == 2)
        {
            $groups = array(2);
        }
        else
        {
            $groups = array(2, 7);
        }
        
        //clear groups
        $CI->ion_auth->remove_from_group(array(5,6,7), $userId);
        
        //promote
        foreach($groups AS $g)
        {
            $CI->ion_auth->add_to_group($g, $userId);            
        }

    }
    
    function deactivate($teamid)
    {
       $data = array('active'=>false);
       $this->edit($teamid, $data);
    }
    
    function delete($teamid)
    {
        $this->db->delete('teams', array('id'=>$teamid));
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
                join('users u', 'u.team_id=t.id')->
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
    
    function search($search)
    {
        $search = '%' . $search . '%';
        $sql = "select t.id, t.name from teams t
              where t.active = true AND t.name like ?";
        
        $query = $this->db->
                query($sql, $search);

           
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;
        
        return NULL;           
    }
}


