<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seasons_model extends CI_Model
{
    function __construct()
    {
         parent::__construct();
    }

    function get_listAsArray($activeOnly=true)
    {
        $query = 0;
        if ($activeOnly)
        {
            $query = $this->db->
                    select('seasons.id, seasons.name, seasons.start, seasons.end, seasons.tag, seasons.active')->
                    from('seasons')->
                    where('active', true)->
                    get();
        }
        else
        {
            $query = $this->db->
                    select('seasons.id, seasons.name, seasons.start, seasons.end, seasons.tag, seasons.active')->
                    from('seasons')->
                    get();
            
        }
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->start);
            $row->start = $oDate->format('m/d/Y');
            $oDate = new DateTime($row->end);
            $row->end = $oDate->format('m/d/Y');            
            
            $arr[] = array(
                'id'=> $row->id, 
                'name' => $row->name,
                'tag' => $row->tag,
                'start' => $row->start,
                'end' => $row->end,
                'active' => $row->active,
                    );
        }
        return $arr;
    }
    
    function get($id)
    {
        $query = $this->db->
                where('id', $id)->
                limit(1)->
                from('seasons')->
                get();
        
        if ($query->num_rows() === 1)
        {
            $season = $query->row();

            $oDate = new DateTime($season->start);
            $season->start = $oDate->format('m/d/Y');
            $oDate = new DateTime($season->end);
            $season->end = $oDate->format('m/d/Y');            
            return $season;
        }
        
        return NULL;
        
    }
    function add($name, $tag, $start, $end)
    {
        $sDate = new DateTime($start);
        $eDate = new DateTime($end);
        $data = array(
            'name'=>$name,
            'tag'=>$tag,
            'start'=> date_format($sDate, "Y-m-d H:i:s"),
            'end'=>date_format($eDate, "Y-m-d H:i:s")
        );
        
        $this->db->insert('seasons', $data);
    }
    
    function edit($id, $name, $tag, $start, $end)
    {
        $sDate = new DateTime($start);
        $eDate = new DateTime($end);

        $season = $this->get($id);
      
        $data = array();
        $data['name'] = $name;
        $data['tag'] = $tag;
        $data['start'] = date_format($sDate, "Y-m-d H:i:s");
        $data['end'] = date_format($eDate, "Y-m-d H:i:s");
        
        $this->db->trans_begin();
         
        $this->db->update('seasons', $data, array('id' => $season->id));
         
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;        
    }
    
    function changeActiveFlag($id, $active)
    {
        $data = array();
        $data['active'] = $active;
        $this->db->trans_begin();
         
        $this->db->update('seasons', $data, array('id' => $id));        
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;      
    }
    
    function GetActiveSeasons($teamId=0)
    {
        $query = $this->db->
               select('seasons.id, seasons.start, seasons.end, seasons.name')->
               from('seasons')->
               where('active', true)->
               where('start <=', date('Y-m-d'))->
               where('end  >=', date('Y-m-d'))->
               get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->start);
            $row->start = $oDate->format('m/d/Y');
            $oDate = new DateTime($row->end);
            $row->end = $oDate->format('m/d/Y');
            
            $bInSeason = false;
            if ($teamId != 0)
            {
                if ($this->isTeamInSeason($teamId, $row->id))
                    $bInSeason = true;
                
            }
            
            $arr[] = array(
                'id'=> $row->id, 
                'name' => $row->name,
                'start' => $row->start,
                'end' => $row->end,
                'inSeason' => $bInSeason,
                    );
        }
        return $arr;        
    }
    
    function RegisterTeamToSeason($teamId, $seasonId)       
    {
        $data = array(
            'team_id'=>$teamId,
            'season_id'=>$seasonId,
        );
        
        $this->db->insert('seasons_teams', $data);
    }

    function UnregisterTeamToSeason($teamId, $seasonId)       
    {
        $data = array(
            'team_id'=>$teamId,
            'season_id'=>$seasonId,
        );
        
        $this->db->delete('seasons_teams', $data);
    }
    
    function GetTeamsInSeason($seasonId)
    {
        $query = $this->db->
               select('teams.*')->
               from('seasons_teams')->
               join('teams', 'teams.id = seasons_teams.team_id')->
               join('users', 'users.id = teams.userid')->
               where('seasons_teams.season_id', $seasonId)->
               where('users.active', true)->
               get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {         
            $arr[] = $row;
        }
        return $arr;                       
    }
    
    function isTeamInSeason($teamId, $seasonId)
    {
         $query = $this->db->
               select('seasons_teams.id, seasons_teams.team_id, seasons_teams.season_id')->
               from('seasons_teams')->
               where('seasons_teams.team_id', $teamId)->
               where('seasons_teams.season_id', $seasonId)->
               get();
         
        if ($query->num_rows() === 1)
        {
            return true;
        }
        else 
        {
            return false;
        }
    }
    
    function updateTeamInSeasons($teamId, $seasonIds)
    {
        //first remove this team from all seasons, so it can be re-added.
        $data = array(
            'team_id'=>$teamId,
        );
         $this->db->delete('seasons_teams', $data);
         
        if (isset($seasonIds) && !empty($seasonIds)) {
            //now add them all back.
           foreach ($seasonIds as $seasonId) {
               $this->RegisterTeamToSeason($teamId, $seasonId);
           }            
        }                  
    }
    
    function addWeek($seasonId, $tag, $start, $end)
    {
        $sDate = new DateTime($start);
        $eDate = new DateTime($end);
        $data = array(
            'tag'=>$tag,
            'start'=> date_format($sDate, "Y-m-d H:i:s"),
            'end'=>date_format($eDate, "Y-m-d H:i:s"),
            'season_id' => $seasonId,
        );
        
        $this->db->insert('weeks', $data);
    }
    
    function getWeek($weekId)
    {        
        $query = $this->db->
               select('weeks.id,weeks.season_id,weeks.tag,weeks.start,weeks.end')->
               from('weeks')->
               where('weeks.id', $weekId)->
               get();

        if ($query->num_rows() === 1)
        {
            $week = $query->row();

            $oDate = new DateTime($week->start);
            $week->start = $oDate->format('m/d/Y');
            $oDate = new DateTime($week->end);
            $week->end = $oDate->format('m/d/Y');            
            return $week;
        }         
         
    }
    
    function getWeeksForSeason($seasonId)
    {
         $query = $this->db->
               select('weeks.id, weeks.tag,weeks.start,weeks.end')->
               from('weeks')->
               where('weeks.season_id', $seasonId)->
               get();
         
        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->start);
            $row->start = $oDate->format('m/d/Y');
            $oDate = new DateTime($row->end);
            $row->end = $oDate->format('m/d/Y');            
            
            $arr[] = array(
                'id'=> $row->id, 
                'tag' => $row->tag,
                'start' => $row->start,
                'end' => $row->end,
                    );
        }
        return $arr;
    }

}
