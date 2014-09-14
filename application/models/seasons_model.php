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
    
    function GetNonCurrentSeasons()
    {
        $query = $this->db->
               select('seasons.*')->
               from('seasons')->
               where('current', false)->
               get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $season = $row;

            $oDate = new DateTime($season->start);
            $season->start = $oDate->format('m/d/Y');
            $oDate = new DateTime($season->end);
            $season->end = $oDate->format('m/d/Y');            
            
            $arr[] = $season;
        }
        return $arr;
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
               select('teams.*, sg.name as group_name, sg.id as group_id')->
               from('seasons_teams')->
               join('teams', 'teams.id = seasons_teams.team_id')->
               join('season_group_teams sgt', 'sgt.team_id = teams.id', 'left outer')->
               join('season_group sg', 'sg.id = sgt.season_group_id', 'left outer')->
               where('seasons_teams.season_id', $seasonId)->
               where('sg.season_id', $seasonId)->
               order_by('sg.id', 'ASC')->
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
        if (isset($seasonIds) && !empty($seasonIds)) {
            
            //first remove this team from all seasons, so it can be re-added.
            $data = array(
                'team_id'=>$teamId,
            );
            $this->db->delete('seasons_teams', $data);
         
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
    
    function newMatch($seasonId, $weekid, $homeTeam, $awayTeam, $server, $date, $name)
    {
        $data = array(
            'name'=>$name,
            'season_id'=>$seasonId,
            'week_id'=> $weekid,
            'home_team_id'=>$homeTeam,
            'away_team_id'=>$awayTeam,
            'gamedate' => $date,
            'active' => true,
            'server_region' => $server,
            'match_type'=>0
        );
        
        $this->db->insert('matches', $data);
        $id = $this->db->insert_id();
        return $id;
    }
    
    function getSeasonStats($teamId, $seasonId, $matchType=-1, $hideStats=true)
    {
        $season = $this->get($seasonId);

        $date = new DateTime();
        //$date->sub(new DateInterval('P8D'));
        if (!$hideStats)
            $date->add(new DateInterval('P1Y'));
            
        $addedQuery = '';
        if ($matchType > -1)
        {
            $addedQuery = " AND m.match_type = " . $matchType;
        }
        
        $query = $this->db->
          select('m.id, m.home_team_id, m.away_team_id,'
                  . 'm.home_team_points as home_points, m.away_team_points as away_points, '
                  . 'sth.code as home_code, sta.code as away_code')->
          from('matches m ')->
          join('states sth', 'sth.id = m.home_team_state_id', 'left outer')->
          join('states sta', 'sta.id = m.away_team_state_id', 'left outer')->
          join('weeks w', 'w.id = m.week_id', 'left outer')->
          where('m.season_id = '. $seasonId ." AND (m.home_team_id=".$teamId . " OR m.away_team_id=".$teamId.") and m.active=0" . $addedQuery)->
          where('w.end <', date_format($date, "Y-m-d H:i:s"))->
          get();        
        
        $arr = new stdClass();
        $arr->id =  $seasonId;
        $arr->name = $season->name;
        $arr->points = 0;
        $arr->wins = 0;
        $arr->loss = 0;
        
        $bFound = false;
        foreach($query->result() as $row)
        {
           $bFound = true;
           if ($row->home_team_id == $teamId)
           {
               $arr->points += $row->home_points;
               if ($row->home_code == 'W' || $row->home_code == 'FW')
                   $arr->wins++;
               else
                   $arr->loss++;
           }
           else
           {
               $arr->points += $row->away_points;
               if ($row->away_code == 'W'  || $row->away_code == 'FW')
                   $arr->wins++;
               else
                   $arr->loss++;               
           }
        }
        
        if (!$bFound && ($matchType > 0 || $matchType == -2))
            return NULL;
        
        $total = ($arr->wins + $arr->loss);
        if ($total == 0)
        {
            $arr->perc = 0;        
        }
        else
        {
            $arr->perc = round($arr->wins / ($arr->wins + $arr->loss) * 100.0);        
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

        date_default_timezone_set("GMT");
        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->gamedate);
            $row->gamedate = $oDate->format('m/d/Y H:i:s'). " GMT";

            $weekDate = new DateTimE($row->endweek);
            //$weekDate->add(new DateInterval('P8D'));
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
    
    function getMatchesForSeason($seasonId, $matchType=-1, $hideStats=true)
    {
        $season = $this->get($seasonId);
        $seasonId = $season->id;
        
        date_default_timezone_set("GMT");
        $date = new DateTime();
        $date->add(new DateInterval('P2W'));
        if (!$hideStats)
        {
            $date->add(new DateInterval('P1Y'));            
        }
        
        $addedQuery = '';
        if ($matchType > -1)
        {
            $addedQuery = "matches.match_type = " . $matchType;
        }
        else 
        {
            $addedQuery = "matches.match_type is not null";
        }

        
        $query = $this->db->
          select('ht.name as homeTeam, at.name as awayTeam, w.tag as weektag, s.tag as seasontag, '
                  . 'matches.*, w.end as endweek, hs.code as hscode, sg.name as group_name, '
                  . 'hs.desc as hsdesc, aw.code as awcode, aw.desc as awdesc')->
          from('matches')->
          join('states hs', 'hs.id = matches.home_team_state_id', 'left outer')->
          join('states aw', 'aw.id = matches.away_team_state_id', 'left outer')->
          join('weeks w', 'w.id = matches.week_id', 'left outer')->
          join('seasons s', 's.id = matches.season_id', 'left outer')->
          join('teams ht', 'ht.id = matches.home_team_id', 'left outer')->
          join('teams at', 'at.id = matches.away_team_id', 'left outer')->
          join('season_group_teams sgt', 'sgt.team_id = matches.home_team_id', 'left outer')->
          join('season_group sg', 'sg.id = sgt.season_group_id', 'left outer')->
          where('matches.season_id', $seasonId)->
          where($addedQuery)->
          where('sg.season_id', $seasonId)->
          where('matches.gamedate <=', date_format($date, "Y-m-d H:i:s"));
        
        if ($matchType == 0)
        {
            $query = $query->
                order_by('sg.id')->
                order_by('gamedate')->
                get();
        }
        else
        {
           $query = $query->
                order_by('gamedate')->
                get();             
        }
                
                

        $today = new DateTime();
        
        $intervalDate = new DateInterval('P8D');        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $oDate = new DateTime($row->gamedate);
            $row->gamedate = $oDate->format('m/d/Y H:i:s') . " GMT";

            $weekDate = new DateTimE($row->endweek);
            //$weekDate->add($intervalDate);
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
    
      
    function getTeamsByPoints($seasonId, $matchType=-1, $hideStats=true)
    {
        $teams = $this->GetTeamsInSeason($seasonId);
        $stats = Array();
        foreach($teams AS $team)
        {
            $stat = $this->getSeasonStats($team->id,$seasonId, $matchType, $hideStats);
            if ($stat == null) continue;
            $stat->teamName = $team->name;
            $stat->teamId = $team->id;
            $stat->group_name = $team->group_name;
            $stats[] = $stat;
        }
        
        usort($stats, "teamPointSort");
        return $stats;
    }
    
    function getMatch($id, $hideStats=true)
    {
        $query = $this->db->
          select('ht.name as homeTeam, at.name as awayTeam, w.tag as weektag, s.tag as seasontag, '
                  . 'matches.*, w.start as startWeek, w.end as endweek,hs.code as hscode, '
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
                date_default_timezone_set("GMT");
                $match = $query->row();

                $oDate = new DateTime($match->gamedate);
                $match->gamedate = $oDate->format('m/d/Y H:i:s') ." GMT";
                if ($match->proposeddate != null)
                {
                    $oDate = new DateTime($match->proposeddate);
                    $match->proposeddate = $oDate->format('m/d/Y H:i:s') . " GMT";                                
                }
                
                $today = new DateTime();
                $intervalDate = new DateInterval('P8D');        
                $weekDate = new DateTimE($match->endweek);
                //$weekDate->add($intervalDate);
                if ($weekDate > $today)
                {
                    if ($match->active == false && $hideStats)
                    {
                        $match->hscode = 'H';
                        $match->awcode = 'H';
                        $match->hsdesc = 'Hidden';
                        $match->awdesc = 'Hidden';
                        $match->home_team_points = '';
                        $match->away_team_points = '';                    
                    }
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
        $data->length = $match->length;
         
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

    function confirmMatchProposedTime($match, $formatDates=true)
    {
        $this->db->trans_begin();        
        date_default_timezone_set("GMT");
        $data = new stdClass();
        if ($formatDates)
        {
            $data->gamedate  = DateTime::createFromFormat('m/d/Y H:i:s', trim($match->proposeddate, " GMT"))->format('Y-m-d H:i:s');            
        }
        else
        {
           $data->gamedate = $match->proposeddate;
        }
        $data->proposeddate  = null;
        $data->who_proposed_team_id = null;
        $data->proposeddate_timestamp = null;
        $this->db->update('matches', $data, array('id' => $match->id));
         
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
    }    
    
    function unsetMatchProposedTime($match)
    {
        $this->db->trans_begin();
        
        $data = new stdClass();
        $data->proposeddate  = null;
        $data->who_proposed_team_id = null;
        $data->proposeddate_timestamp = null;
        $this->db->update('matches', $data, array('id' => $match->id));
         
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;               
    }
    
    function setMatchProposedTime($match, $newTime, $fromWho)
    {
        $this->db->trans_begin();
        
        date_default_timezone_set('GMT');
        $now = new DateTime('now');
        $data = new stdClass();
        $data->proposeddate  = DateTime::createFromFormat("m/d/Y h:ia", $newTime)->format('Y-m-d H:i:s');
        $data->who_proposed_team_id = $fromWho;
        $data->proposeddate_timestamp = $now->format('Y-m-d H:i:s');
        $this->db->update('matches', $data, array('id' => $match->id));
         
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;               
    }
    
    function getAllMatchProposals()
    {
        $query = $this->db->
               select('*')->
               from('matches')->
               where('proposeddate is not null', null)->
               get();        

        $arr = Array();
        foreach($query->result() as $row)
        {
            $match = $row;  
            $arr[] = $match;
        }
        return $arr;
    }
    
    function getEmailListForSeason($seasonId)
    {
        $query = $this->db->
               select('u.email')->
               from('season_group_teams sgt')->
               join('teams t', 't.id = sgt.team_id')->
               join('users u', 'u.team_id = t.id')->
               join('season_group sg', 'sg.id = sgt.season_group_id')->
               where('season_id', $seasonId)->
               get();        

        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row->email;
        }
        return $arr;        
    }
    
    function getExpiredMatches()
    {
        $query = $this->db->
               select('*')->
               from('matches')->
               where('gamedate +interval 4 hour < UTC_TIMESTAMP()', null)->
               where('active', true)->
               get();        

        $arr = Array();
        foreach($query->result() as $row)
        {
            $match = $row;  
            $arr[] = $match;
        }
        return $arr;
    }
    
    
    function getGroupsForSeason($seasonId)
    {
        $query = $this->db->
               select('*')->
               from('season_group sg')->
               where('sg.season_id', $seasonId)->
               where('sg.isopen', false)-> 
               get();        

        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;        
    }
    
    function getTeamsInGroup($seasonId, $groupId)
    {
        $query = $this->db->
               select('teams.*, sg.name as group_name')->
               from('seasons_teams')->
               join('teams', 'teams.id = seasons_teams.team_id')->
               join('season_group_teams sgt', 'sgt.team_id = teams.id', 'left outer')->
               join('season_group sg', 'sg.id = sgt.season_group_id', 'left outer')->
               where('sg.id', $groupId)->
               where('seasons_teams.season_id',$seasonId)->
               order_by('sgt.seeding')->
               get();

        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;                
    }
    
    function getCurrentWeek($seasonId)
    {
        $query = $this->db->
               select('*')->
               from('weeks')->
               where('season_id',$seasonId)->
               where('start <= now() and end >= now() ',null)->
               limit(1)->
               get();
        
        if ($query->num_rows() === 1)
        {
            $week = $query->row();
            return $week;
        }

        return null;
    }
    
    function getActiveChallengerTeams($seasonId)
    {
        $query = $this->db
                ->select('sgt.team_id, sgt.seeding')
                ->from('season_group sg')
                ->join('season_group_teams sgt', 'sgt.season_group_id = sg.id')
                ->where('sg.season_id', $seasonId)
                ->where('sg.isopen', true)
                ->get();
                
        $arr = Array();
        
        $ci = get_instance();
        $ci->load->model('Teams_model');
        foreach($query->result() as $row)
        {
            //okay, from this teamid, we want to go see if they have an active 5 man roster.
            $team = $ci->Teams_model->getById($row->team_id);
            if ($team != null)
            {
                $starters = $team->players;
                if (sizeof($starters) >= 5)
                {
                    //valid team! we now need the points.
                    $team->seeding = $row->seeding;
                    
                    $stats = $this->getSeasonStats($team->id, $seasonId, -2, false);
                    if ($stats != null)
                        $team->points = $stats->points;
                    else
                        $team->points = 0;
                    
                    $arr[] = $team;
                }
            }
        }
        
        return $arr;
        
    }
    
    function getTeamsPlayingInWeek($weekId)
    {
        $query = $this->db->
               select('t.id')->
               from('matches m')->
               join('teams t','t.id = m.home_team_id')->
               where('m.week_id',$weekId)->
               get();
        
        $CI =& get_instance();
        $CI->load->model('Teams_model');
        $arr = Array();
        foreach($query->result() as $row)
        {
            $team = $CI->Teams_model->getById($row->id);
            $arr[] = $team;
        }
        return $arr;                

    }
    
    function getMatchesInWeek($weekId, $groupId)
    {
        $query = $this->db->
               select('m.*')->
               from('matches m')->
               join('season_group_teams sgt','sgt.team_id = m.home_team_id' )->
               where('m.week_id',$weekId)->
               where('sgt.season_group_id', $groupId)->
               get();
        
        $arr = Array();
        foreach($query->result() as $row)
        {
            $arr[] = $row;
        }
        return $arr;                
        
    }
    
    function changeMatchTime($match)
    {        
        $this->db->trans_begin();
        
        $data = new stdClass();
        $data->gamedate = $match->gamedate;
         
        $this->db->update('matches', $data, array('id' => $match->id));
         
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;                
    }    
    
    function GetTeamActiveGroup($sid, $tid)
    {
        $query = $this->db
                ->select('*')
                ->from('season_group_teams sgt')
                ->join('season_group sg','sg.id = sgt.season_group_id')
                ->join('seasons s', 's.id = sg.season_id')
                ->where('s.id', $sid)
                ->where('sgt.team_id', $tid)
                ->limit(1)
                ->get();
        
        if ($query->num_rows() === 1)
        {
           $data = $query->row();
           return $data;
        }
        return null;
    }
    
    function SignTeamIntoChallenger($sid, $tid)
    {
        $this->RegisterTeamToSeason($tid, $sid);
        
        $query = $this->db
                ->select('*')
                ->from('season_group sg')
                ->where('season_id', $sid)
                ->where('isopen', true)
                ->limit(1)
                ->get();
        
        if ($query->num_rows() === 1)
        {
            $row = $query->row();
            $sg_id = $row->id;
            
            $data = array(
                'team_id'=>$tid,
                'season_group_id'=>$sg_id,
                );
        
            $this->db->insert('season_group_teams', $data);                                
            return true;
        }
        
        return false;
    }
    
    function SignTeamOutOfChallenger($sid, $tid)
    {
        $this->UnregisterTeamToSeason($tid, $sid);
        
        $query = $this->db
                ->select('*')
                ->from('season_group sg')
                ->where('season_id', $sid)
                ->where('isopen', true)
                ->limit(1)
                ->get();
        
        if ($query->num_rows() === 1)
        {
            $row = $query->row();
            $sg_id = $row->id;
            
            $data = array(
                'team_id'=>$tid,
                'season_group_id'=>$sg_id,
                );
        
            $this->db->delete('season_group_teams', $data);

            return true;
        }
        
        return false;
    }}

function teamPointSort($a, $b)
{
    if ($a->group_name == $b->group_name)
    {
        if ($a->points == $b->points)
        {
            return ($a->teamName < $b->teamName) ? -1: 1;        
        }
        return ($a->points < $b->points) ? 1 : -1;        
    }
    else
    {
        return ($a->group_name < $b->group_name) ? -1: 1;
    }  
}
