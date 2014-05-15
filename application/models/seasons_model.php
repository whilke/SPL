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
    
    function GetCurrentSeason()
    {
        $query = $this->db->
               select('seasons.*')->
               from('seasons')->
               where('current', true)->
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
    
    function GetAllSeasons()
    {
        $query = $this->db->
               select('seasons.id, seasons.start, seasons.end, seasons.name')->
               from('seasons')->
               where('active', true)->
               get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->start);
            $row->start = $oDate->format('m/d/Y');
            $oDate = new DateTime($row->end);
            $row->end = $oDate->format('m/d/Y');                        
            $arr[] = $row;
        }
        return $arr;        
        
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
    
    function GetRunningSeason($teamId=0)
    {
        $query = $this->db->
               select('seasons.id, seasons.start, seasons.end, seasons.name')->
               from('seasons')->
               where('active', true)->
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
    
    function getTeamsNotInSeason($seasonId)
    {
        $query = $this->db->
               select('teams.*')->
               from('seasons_teams')->
               join('teams', 'teams.id = seasons_teams.team_id', 'right outer')->
               join('users', 'users.id = teams.userid')->
               where('seasons_teams.season_id is null', null)->
               where('users.active', true)->
               where('users.teamname !=', 'Admin')->
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
    
    function newMatch($seasonId, $weekid, $homeTeam, $awayTeam, $server, $name)
    {
        
        $week = $this->getWeek($weekid);
        $date = new DateTime($week->end);
        $date->sub(new DateInterval('P2D'));
                
        $data = array(
            'name'=>$name,
            'season_id'=>$seasonId,
            'week_id'=> $weekid,
            'home_team_id'=>$homeTeam,
            'away_team_id'=>$awayTeam,
            'gamedate' => date_format($date, "Y-m-d H:i:s"),
            'active' => true,
            'server_region' => $server
        );
        
        $this->db->insert('matches', $data);
    }
    
    function getSeasonStats($teamId, $seasonId, $hideStats=true)
    {
        $season = $this->get($seasonId);

        $date = new DateTime();
        $date->sub(new DateInterval('P8D'));
        if (!$hideStats)
            $date->add(new DateInterval('P1Y'));
            

        
        $query = $this->db->
          select('m.id, m.home_team_id, m.away_team_id,'
                  . 'm.home_team_points as home_points, m.away_team_points as away_points, '
                  . 'sth.code as home_code, sta.code as away_code')->
          from('matches m ')->
          join('states sth', 'sth.id = m.home_team_state_id', 'left outer')->
          join('states sta', 'sta.id = m.away_team_state_id', 'left outer')->
          join('weeks w', 'w.id = m.week_id', 'left outer')->
          where('m.season_id = '. $seasonId ." AND (m.home_team_id=".$teamId . " OR m.away_team_id=".$teamId.") and m.active=0")->
          where('w.end <', date_format($date, "Y-m-d H:i:s"))->
          get();        
        
        $arr = new stdClass();
        $arr->id =  $seasonId;
        $arr->name = $season->name;
        $arr->points = 0;
        $arr->wins = 0;
        $arr->loss = 0;
        
        foreach($query->result() as $row)
        {
           
           if ($row->home_team_id == $teamId)
           {
               $arr->points += $row->home_points;
               if ($row->home_code == 'W')
                   $arr->wins++;
               else
                   $arr->loss++;
           }
           else
           {
               $arr->points += $row->away_points;
               if ($row->away_code == 'W')
                   $arr->wins++;
               else
                   $arr->loss++;               
           }
        }
        
        $total = ($arr->wins + $arr->loss);
        if ($total == 0)
        {
            $arr->perc = 0;        
        }
        else
        {
            $arr->perc = $arr->wins / ($arr->wins + $arr->loss) * 100.0;        
        }

        return $arr;        
    }
    
    function getMatchesForPortal($teamId, $hideStats=true)
    {
        $season = $this->GetCurrentSeason();
        if ($season == null) 
        {
            $arr = Array();
            return $arr;
        }
        
        $date = new DateTime();
        $date->add(new DateInterval('P2W'));
        if (!$hideStats)
        {
        $date->add(new DateInterval('P1Y'));
            
        }

        $seasonId = $season->id;
        
        $query = $this->db->
          select('ht.name as homeTeam, at.name as awayTeam, w.tag as weektag, s.tag as seasontag, w.end as endweek, '
                  . 'matches.*, '
                  . 'hs.code as hscode, '
                  . 'hs.desc as hsdesc, aw.code as awcode, aw.desc as awdesc')->
          from('matches')->
          join('states hs', 'hs.id = matches.home_team_state_id', 'left outer')->
          join('states aw', 'aw.id = matches.away_team_state_id', 'left outer')->
          join('weeks w', 'w.id = matches.week_id', 'left outer')->
          join('seasons s', 's.id = matches.season_id', 'left outer')->
          join('teams ht', 'ht.id = matches.home_team_id', 'left outer')->
          join('teams at', 'at.id = matches.away_team_id', 'left outer')->
          where('matches.season_id = '. $seasonId ." AND (home_team_id=".$teamId . " OR away_team_id=".$teamId.")")->
          where('matches.gamedate <=', date_format($date, "Y-m-d H:i:s"))->
          order_by('gamedate')->
          get();

        $today = new DateTime();

        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->gamedate);
            $row->gamedate = $oDate->format('m/d/Y H:m:s'). " GMT";

            $weekDate = new DateTimE($row->endweek);
            $weekDate->add(new DateInterval('P8D'));
            if ($weekDate > $today)
            {
                if ($row->active == false && $hideStats)
                {
                    $row->hscode = 'H';
                    $row->awcode = 'H';
                    $row->hsdesc = 'Hidden';
                    $row->awdesc = 'Hidden';
                    $row->home_team_points = '';
                    $row->away_team_points = '';                    
                }
            }
            
            $arr[] = $row;
        }
        return $arr;        
    }
    
    function getMatchesForSeason($seasonId, $hideStats=true)
    {
        $season = $this->get($seasonId);
        $seasonId = $season->id;
        
        $date = new DateTime();
        $date->add(new DateInterval('P2W'));
        if (!$hideStats)
        {
            $date->add(new DateInterval('P1Y'));            
        }

        
        $query = $this->db->
          select('ht.name as homeTeam, at.name as awayTeam, w.tag as weektag, s.tag as seasontag, '
                  . 'matches.*, w.end as endweek, hs.code as hscode, '
                  . 'hs.desc as hsdesc, aw.code as awcode, aw.desc as awdesc')->
          from('matches')->
          join('states hs', 'hs.id = matches.home_team_state_id', 'left outer')->
          join('states aw', 'aw.id = matches.away_team_state_id', 'left outer')->
          join('weeks w', 'w.id = matches.week_id', 'left outer')->
          join('seasons s', 's.id = matches.season_id', 'left outer')->
          join('teams ht', 'ht.id = matches.home_team_id', 'left outer')->
          join('teams at', 'at.id = matches.away_team_id', 'left outer')->
          where('matches.season_id', $seasonId)->
          where('matches.gamedate <=', date_format($date, "Y-m-d H:i:s"))->
          order_by('gamedate')->
          get();

        $today = new DateTime();
        
        $intervalDate = new DateInterval('P8D');        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->gamedate);
            $row->gamedate = $oDate->format('m/d/Y H:m:s') . " GMT";

            $weekDate = new DateTimE($row->endweek);
            $weekDate->add($intervalDate);
            if ($weekDate > $today)
            {
                if ($row->active == false && $hideStats)
                {
                    $row->hscode = 'H';
                    $row->awcode = 'H';
                    $row->hsdesc = 'Hidden';
                    $row->awdesc = 'Hidden';
                    $row->home_team_points = '';
                    $row->away_team_points = '';                    
                }
            }
             
            $arr[] = $row;
        }
        return $arr;        
    }
    
      
    function getTeamsByPoints($seasonId, $hideStats=true)
    {
        $teams = $this->GetTeamsInSeason($seasonId);
        $stats = Array();
        foreach($teams AS $team)
        {
            $stat = $this->getSeasonStats($team->id,$seasonId, $hideStats);
            $stat->teamName = $team->name;
            $stat->teamId = $team->id;
            $stats[] = $stat;
        }
        
        usort($stats, "teamPointSort");
        return $stats;
    }
    
    function getMatch($id)
    {
        $query = $this->db->
          select('ht.name as homeTeam, at.name as awayTeam, w.tag as weektag, s.tag as seasontag, '
                  . 'matches.*, hs.code as hscode, '
                  . 'hs.desc as hsdesc, aw.code as awcode, aw.desc as awdesc')->
          from('matches')->
          join('states hs', 'hs.id = matches.home_team_state_id', 'left outer')->
          join('states aw', 'aw.id = matches.away_team_state_id', 'left outer')->
          join('weeks w', 'w.id = matches.week_id', 'left outer')->
          join('seasons s', 's.id = matches.season_id', 'left outer')->
          join('teams ht', 'ht.id = matches.home_team_id', 'left outer')->
          join('teams at', 'at.id = matches.away_team_id', 'left outer')->
          where('matches.id', $id)->
          get();
        
            if ($query->num_rows() === 1)
            {
                $match = $query->row();

                $oDate = new DateTime($match->gamedate);
                $match->gamedate = $oDate->format('m/d/Y h:m:s'). " GMT";
                if ($match->proposeddate != null)
                {
                    $oDate = new DateTime($match->proposeddate);
                    $match->proposeddate = $oDate->format('m/d/Y h:m:s'). " GMT";                                
                }
                return $match;
            }        
        
    }
    
    function editMatch($match)
    {        
        $this->db->trans_begin();
        
        $data = new stdClass();
        $data->home_team_state_id = $match->home_team_state_id;
        $data->away_team_state_id = $match->away_team_state_id;
        $data->home_team_points = $match->home_team_points;
        $data->away_team_points = $match->away_team_points;
        $data->strife_match_id = $match->strife_match_id;
        $data->home_team_ban_hero_id = $match->home_team_ban_hero_id;
        $data->away_team_ban_hero_id = $match->away_team_ban_hero_id;
        $data->active = $match->active;
         
        $this->db->update('matches', $data, array('id' => $match->id));
         
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;                
    }
    
    function getStates()
    {
        $query = $this->db->
            select('states.*')->
            from('states')->
            get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[$row->id] = $row->desc;
        }
        return $arr;

    }
}

function teamPointSort($a, $b)
{
    if ($a->points == $b->points) return 0;
    return ($a->points < $b->points) ? 1 : -1;
}
