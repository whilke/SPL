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

class Stats_model extends CI_Model
{
    function __construct()
    {
         parent::__construct();
    }
    
    function updatePlayer($stat)
    {
        $query = $this->db->where('strife_id', $stat->accountid)->limit(1)->
            from('players')->get();
        if ($query->num_rows() === 0)
        {
            $data = array(
                'name'=>$stat->name,
                'strife_id'=>$stat->accountid
            );

            $this->db->insert('players', $data);   
        }
        else
        {
            $row = $query->row();
            if ($row->name != $stat->name)
            {
                $data['name'] = $stat->name;
                $this->db->trans_begin();
                $this->db->update('players', $data, array('strife_id' => $stat->accountid));
                if ($this->db->trans_status() === FALSE)
                {
                    $this->db->trans_rollback();
                    return FALSE;
                }
                $this->db->trans_commit();                    
            }
        }
    }
    
    function getHeroList()
    {
        $query = $this->db->from('heroes')->get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $row->name = strtolower(str_replace("Hero_","", $row->name));
            $arr[$row->id] = $row->name;
        }
        return $arr;
    }
    
    function getHeroId($hero_name)
    {
        $query = $this->db->where('name', $hero_name)->limit(1)->
            from('heroes')->get();
        
        if ($query->num_rows() == 1)
        {
            return ($query->row()->id);
        }
        return 0;
    }
    
    function getPetId($pet_id)
    {
        $query = $this->db->where('name', $pet_id)->limit(1)->
            from('pets')->get();
        
        if ($query->num_rows() == 1)
        {
            return ($query->row()->id);
        }
        return 0;
    }
    
    function updateMatchStats($matchId, $isGloryHome, $home_id, $away_id, $stats)
    {
        //clear out any stats for this match first.
        $this->db->delete('stats', array('match_id'=>$matchId));
        
        if ($stats)
        {
            foreach($stats AS $stat)
            {
                if ($stat->team == 0) continue; //skip specs;
                // 
                //first see if we need a new player record.
                $this->updatePlayer($stat);
                $hero_id = $this->getHeroId($stat->heroname);
                $pet_id = $this->getPetId($stat->familiar);

                $data = new stdClass();
                $data->match_id = $matchId;
                $data->player_id = $stat->accountid;
                if ($isGloryHome)
                {
                    if ($stat->team == 1)
                        $data->team_id = $home_id;            
                    else
                        $data->team_id = $away_id;            
                }
                else
                {
                    if ($stat->team == 1)
                        $data->team_id = $away_id;            
                    else
                        $data->team_id = $home_id;                            
                }
                $data->hero_id = $hero_id;
                $data->pet_id = $pet_id;

                $this->db->insert('stats', $data);   
            }            
        }
    }
    
    function getStats($matchId)
    {
        $query = $this->db->
                select('s.id, s.team_id, p.strife_id, p.name as player, h.name as hero, pe.name as pet')->
                from('stats s')->
                join ('players p', 'p.strife_id = s.player_id')->
                join ('heroes h', 'h.id = s.hero_id')->
                join ('pets pe', 'pe.id = s.pet_id')->
                where('match_id', $matchId)->
                get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $row->hero = strtolower(str_replace("Hero_","", $row->hero));
            $arr[] = $row;
        }
        return $arr;        
    }
    
    function getStatsToParse()
    {
        $query = $this->db->
               select('m.strife_match_id, m.id')->
               from('matches m')->
               where('m.replay_processed', 'false')-> 
               where('m.strife_match_id !=', '')->
               get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;        
        
    }
    
    function update_match_set_adv_stats($matchId, $matchLength)
    {
        $data = array();
        $data['length'] = $matchLength;
        $data['replay_processed'] = true;
        
        $this->db->trans_begin();
        $this->db->update('matches', $data, array('id' => $matchId));
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();                    

    }
    
    function update_player_match_stats($matchId, $accountId, $player_data)
    {
        $data = array();
        $data['advanced'] = true;
        $data['total_gold'] = $player_data['gold'];
        $data['total_creep'] = $player_data['creeps'];
        $data['total_neut'] = $player_data['neutrals'];
        $data['kills'] = $player_data['kills'];
        $data['assists'] = $player_data['assists'];
        $data['deaths'] = $player_data['deaths'];
        

        $this->db->trans_begin();
        $this->db->update('stats', $data, array('player_id' => $accountId, 
                                           'match_id'=> $matchId));
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();                    
        
        
    }
    
        
    function GetHeroPlayStats($seasonId)
    {
        $sql="
            select TRIM( LEADING 'Hero_' FROM h.name) as name, 
            s.team_id, m.home_team_id, m.away_team_id, m.home_team_state_id, m.away_team_state_id from stats s
            join heroes h on h.id = s.hero_id
            join matches m on m.id = s.match_id
            order by name
        ";
        
        $query = $this->db->
                query($sql, $seasonId);
           
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;        
    }
   
}
