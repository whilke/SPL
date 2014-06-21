<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tools extends MY_Controller 
{
    function __construct()
    {       
        $this->load->database();       
        $this->load->library('email');
        
        $a = array(
            'mailtype' => 'html',
        );
        
        $this->email->initialize($a);
        
        $this->model('Stats_model');
        $this->model('Seasons_model');
        $this->model('Teams_model');
    }
    
    public function index()
    {

    }
    
    private function sendEmail($from, $to, $subject, $message)
    {
        /*
        if (true)
        {
            print("Sending email to " . $to . " subj: " . $subject);
            return;
        }
        */
         
        
        $this->email->clear();
        $this->email->from($from, 'SPL Game');
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);

        if ($this->email->send())
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }        
    }
    
    public function migratePlayers()
    {
        $this->model('Ion_auth_model');
        $users = $this->Ion_auth_model->users();
        foreach($users->result() AS $user)
        {
            if ($user->username != '')
            {
                //see if this user is upgraded.
                $extra = $this->Ion_auth_model->user_extra($user->id)->row();
                if ($extra == null)
                {
                    $this->Ion_auth_model->create_user_extra($user->id);
                }
                else
                {
                    if ($extra->region == null || $extra->region == '' || $extra->region == 'Unknown')
                    {
                      if ($user->team_id > 0)
                      {
                          $this->model('Teams_model');
                          $team = $this->Teams_model->getById($user->team_id);
                          
                          $data = array();
                          $data['region'] = $team->region;
                          $this->Ion_auth_model->update_user_extra($user->id, $data);                          
                      }
                    }
                }
            }
        }
    }
    
    public function migrateOwners()
    {
        $this->model('Ion_auth_model');
        
        $season = $this->Seasons_model->GetCurrentSeason();
        $rawteams = $this->Seasons_model->getTeamsByPoints($season->id, false);
        //scrub out teams below our point cap
        $teams = array();
        foreach($rawteams AS $team)
        {
            if ($team->points >140)
                $teams[] = $team;
            
        }
                
        //now go through this list of teams and upgrade the captain to the owner.
        foreach($teams AS $team)
        {
            $id = $team->teamId;
            //find the userid set with this.
            $teamBlob = $this->Teams_model->getById($id);
            $userName = $teamBlob->players[0]->name;
         
            $oUser = $this->Ion_auth_model->where('teamname',$teamBlob->name)->limit(1)->users()->row();
            $this->Ion_auth_model->update($oUser->id, ['username'=>$userName, 'team_id'=>$id]);  
            $this->Ion_auth_model->add_to_group(5, $oUser->id);
        }
        
        //delete teams/users not in this season now.
        $teams = $this->Seasons_model->getTeamsNotInSeason(0);
        foreach($teams AS $team)
        {
            $this->Teams_model->delete($team->id);
            $this->Ion_auth_model->delete_user($team->userid);
        }

    }
    
    public function createTimeBuckets()
    {
        $arr = array();
        for($i =0; $i < 24; $i+=2)
        {
            $o = new stdClass();
            $o->idx = $i;
            $o->matches = array();
                        
            $arr[] = $o;
        }
        return $arr;
    }
    
    public function groupMatches($matches)
    {
        $arr = array();
        
        foreach($matches AS $match)
        {
            $hTeam = $match->home_team_id;
            $aTeam = $match->away_team_id;
            
            //see if we have this pair already in the system.
            $bFound = false;
            foreach($arr as $group)
            {
                if ($group->g1->hTeam == $aTeam &&
                    $group->g1->aTeam == $hTeam)
                {
                    $bFound = true;
                    //we should add this in
                    $group->g2 = new stdClass();
                    $group->g2->id = $match->id;
                    $group->g2->hTeam = $hTeam;
                    $group->g2->aTeam = $aTeam;
                    $group->g2->region = $match->server_region;
                    $group->g2->match = $match;
                    
                    break;
                }
            }
            
            if (!$bFound)
            {
                //new match pair.
                $group = new stdClass();
                $date = new DateTime($match->gamedate);
                $group->date = date_format($date, "Y-m-d");
                $group->g1 = new stdClass();
                $group->g1->id = $match->id;
                $group->g1->hTeam = $hTeam;
                $group->g1->aTeam = $aTeam;
                $group->g1->region = $match->server_region;
                $group->g1->match = $match;
                
                $arr[] = $group;
                                
            }            
        }
        
        return $arr;
    }
    
    public function fixMatch($matches, $match)
    {
        $hTeam = $match->home_team_id;
        $aTeam = $match->away_team_id;
        $date = new DateTime($match->gamedate);
        while(true)
        {
            $date->add(new DateInterval('PT1H'));            
            $date_str = date_format($date, "Y-m-d H:i:s");
            //see if this overlaps with another match for these teams now.
            $bFound = false;
            foreach($matches AS $cM)
            {
                if ($cM->home_team_id == $hTeam || $cM->away_team_id == $aTeam ||
                   $cM->home_team_id == $aTeam || $cM->away_team_id == $hTeam)
                {
                    if ($cM->gamedate == $date_str)
                    {
                        $bFound = true;
                        break;
                    }
                }
            }
            if ($bFound)
                continue;
            
            break;
        }  
        $match->gamedate = date_format($date, "Y-m-d H:i:s");
        $this->Seasons_model->changeMatchTime($match);
    }
    
    public function regionStartTime($group)
    {
        if ($group->g1->region === $group->g2->region)
        {
            if ($group->g1->region === "EU")
            {
                return 14;
            }
            else if ($group->g1->region === "SEA")
            {
                return 4;
            }
            else if ($group->g1->region === "CIS")
            {
                return 14;
            }
            else if ($group->g1->region === "USE")
            {
                return 18;
            }
            else if ($group->g1->region === "USW")
            {
                return 20;
            }
        }
        else
        {
            if (
                    ($group->g1->region === "USE" && $group->g2->region === "USW") ||
                    ($group->g1->region === "USW" && $group->g2->region === "USE") 
                )
            {
                return 18;
            }
            if (
                    ($group->g1->region === "USE" && $group->g2->region === "EU") ||
                    ($group->g1->region === "EU" && $group->g2->region === "USE") 
                )
            {
                return 16;
            }

        }
        print ($group->g1->region);
        print ($group->g2->region);
        return 12;
    }
    
    public function adjustStart($buckets, $group, $start)
    {
        while(true)
        {
            $bucket = null;
            foreach($buckets AS $b)
            {
                if ($b->idx == $start)
                {
                    $bucket = $b;
                    break;
                }
            }
            
            //see if this bucket can fit this match.
            $bFound = false;
            foreach($bucket->matches AS $match)
            {
                if (
                        ($match->g1->hTeam == $group->g1->hTeam || $match->g1->hTeam == $group->g1->aTeam) ||
                        ($match->g1->aTeam == $group->g1->hTeam || $match->g1->aTeam == $group->g1->aTeam)
                   )
                {
                    $bFound = true;
                    break;
                }
            }
            
            if (!$bFound)
            {
                $bucket->matches[] = $group;
                return;
            }
            
            $start+=2;
        }
    }
    
    public function syncPlayers()
    {
        $this->model('Ion_auth_model');
        
        //get all teams.
        $teams = $this->Teams_model->get_list();
        
        foreach($teams AS $team)
        {
            $team = $this->Teams_model->getById($team['id']);
            foreach($team->players AS $player)
            {
                if (!$player->converted) continue;
                
                $p = $this->Teams_model->getPlayerByName($player->name);
                if ($p!= null)
                {
                    $data = array();
                    $data['strife_id'] = $p->strife_id;
                    $oUser = $this->Ion_auth_model->update($player->id, $data);               
                }                
            }

            $this->Teams_model->editPlayerLinks($team);
        }
        
    }
    public function fixWeek($weekId, $groupId)
    {
        if(!$this->input->is_cli_request())
        {
            echo "This script can only be accessed via the command line" . PHP_EOL;
            return;
        }      
                
        $matches = $this->Seasons_model->GetMatchesInWeek($weekId, $groupId);
        $times = $this->createTimeBuckets();        
        $groups = $this->groupMatches($matches);
        
        foreach($groups as $group)
        {
            $region_start = $this->regionStartTime($group);
            $this->adjustStart($times, $group, $region_start);
        }
        
        foreach($times as $blocks)
        {
            $m1Time = $blocks->idx;
            $m2Time = $blocks->idx + 1;
            
            foreach($blocks->matches as $local_match)
            {
                $date = new DateTime($local_match->date . " " . $m1Time . ":00:00");
                $match = $local_match->g1->match;                
                $match->gamedate = date_format($date, "Y-m-d H:i:s");
                $this->Seasons_model->changeMatchTime($match);

                $date = new DateTime($local_match->date . " " . $m2Time . ":00:00");
                $match = $local_match->g2->match;                
                $match->gamedate = date_format($date, "Y-m-d H:i:s");
                $this->Seasons_model->changeMatchTime($match);

            }
            
        }
        
        return;
        
    }
    
    public function startweek()
    {
        if(!$this->input->is_cli_request())
        {
            echo "This script can only be accessed via the command line" . PHP_EOL;
            return;
        }      
        
        $season = $this->Seasons_model->GetCurrentSeason();
        $week = $this->Seasons_model->getCurrentWeek($season->id);
        $teams = $this->Seasons_model->getTeamsPlayingInWeek($week->id);
        
        foreach($teams AS $team)
        {
            $this->twiggy->set('team', $team);

            $msg = $this->twiggy->layout('email')->template('startweek')->render();

            $this->sendEmail('game@strifeproleague.com', $team->email, 
                    'SPL: Start of week', $msg);        
        }
        
    }
    
    public function update_match_props()
    {
        if(!$this->input->is_cli_request())
        {
            echo "This script can only be accessed via the command line" . PHP_EOL;
            return;
        }        

        $this->syncPlayers();
        
        $time = date("Y-m-d H:i:s");
        $now = new DateTime($time);
        $time = gmdate("Y-m-d H:i:s");
        $gmnow = new DateTime($time);

        $matches = $this->Seasons_model->getAllMatchProposals();
        foreach($matches AS $match)
        {
            //okay let's see if we are first past the proposed deadline.
            $time = strtotime($match->proposeddate. ' UTC');
            $prop_date = date("Y-m-d H:i:s", $time);
            $prop_date = new DateTime($prop_date);
            
            $time = strtotime($match->proposeddate_timestamp);
            $timestamp = date("Y-m-d H:i:s", $time);
            $timestamp = new DateTime($timestamp);

            $diff = $now->diff($timestamp);                
            $days = intval( $diff->format('%d') );
            if ($gmnow > $prop_date)
            { 
                //okay, this match is past the proposed time, check if it's been less
                //then 24 hours from when this was created.
               
                if ($days < 1)
                {
                    //auto reject this 
                    $this->twiggy->set('match', $match);

                    $msg = $this->twiggy->layout('email')->template('matchtime_reject')->render();

                    $email = $this->Teams_model->getEmail($match->home_team_id);                
                    $this->sendEmail('game@strifeproleague.com', $email, 
                            'Match Time System: Auto Accepted', $msg);

                    $email = $this->Teams_model->getEmail($match->away_team_id);                
                    $this->sendEmail('game@strifeproleague.com', $email, 
                            'Match Time System: Auto Accepted', $msg);


                    
                    $this->Seasons_model->unsetMatchProposedTime($match); 
                }
            }
            
            if ($days >= 1)
            {
                //we've pasted 24 hours from this proposal.
                //auto accept it now.

                $this->twiggy->set('match', $match);

                $msg = $this->twiggy->layout('email')->template('matchtime_accept')->render();

                $email = $this->Teams_model->getEmail($match->home_team_id);                
                $this->sendEmail('game@strifeproleague.com', $email, 
                        'Match Time System: Auto Accepted', $msg);
                
                $email = $this->Teams_model->getEmail($match->away_team_id);                
                $this->sendEmail('game@strifeproleague.com', $email, 
                        'Match Time System: Auto Accepted', $msg);
                
                                
                $this->Seasons_model->confirmMatchProposedTime($match, false);                    
            }
            
        }
        $matchs = $this->Seasons_model->getExpiredMatches();
        foreacH($matchs as $match)
        {
            $match->home_team_state_id = $match->away_team_state_id = 5;
            $match->home_team_points = $match->away_team_points = 0;
            $match->active =false;

            $this->twiggy->set('match', $match);

            $msg = $this->twiggy->layout('email')->template('matchtime_expire')->render();

            $email = $this->Teams_model->getEmail($match->home_team_id);                
            $this->sendEmail('game@strifeproleague.com', $email, 
                    'Match: Expired', $msg);

            $email = $this->Teams_model->getEmail($match->away_team_id);                
            $this->sendEmail('game@strifeproleague.com', $email, 
                    'Match: Expired', $msg);
                        
            $this->Seasons_model->editMatch($match);
            
            
           
        }
    }
    
    public function team_email($seasonId)
    {
        if(!$this->input->is_cli_request())
        {
            echo "This script can only be accessed via the command line" . PHP_EOL;
            return;
        }        
        
        $emails = $this->Seasons_model->getEmailListForSeason($seasonId);
        
        $email_list = "";
        foreach($emails AS $email){
            $email_list .= $email . ";";
        }
        echo($email_list);
    }
    
    public function parse_stats()
    {
        if(!$this->input->is_cli_request())
        {
            echo "This script can only be accessed via the command line" . PHP_EOL;
            return;
        }        
        
        $matches = $this->Stats_model->getStatsToParse();
        
        //replay path:
        $dir = dirname($_SERVER['SCRIPT_FILENAME']) . "/files/" ;
        $tmp_dir = "/tmp/spl_replay/";
        $replay_parser = dirname($_SERVER['SCRIPT_FILENAME']) . "/tools/strifereplayparser";
        foreach($matches AS $match)
        {
            //lets see if we can find the replay file first.
            $replay_file = $dir . "M". $match->strife_match_id . ".k2r";
            
            if (!file_exists($replay_file))
            {
                continue;
            }
            
            //extract the replay data to a temp file.
            $this->unzip($replay_file, $tmp_dir, false, true);
            
            //read in the basic match stats.
            $replaybasic = $tmp_dir . "replayinfo";
            $contents = file_get_contents($replaybasic); 
            $contents = str_replace(array("\n", "\r", "\t"), '', $contents);
            $contents = trim(str_replace('"', "'", $contents));
            $simpleXml = simplexml_load_string($contents);
            $json = json_encode($simpleXml);
            $array = json_decode($json,TRUE);

            $match_length = $array['@attributes']['matchlength'];
            $basic_players = $array['player'];
                        
            //generate player_stat_xml
            $replaydata = $replay_parser . " " . $tmp_dir . "replaydata";
            $stat_xml = shell_exec($replaydata);
            
            $simpleXml = simplexml_load_string($stat_xml);
            $json = json_encode($simpleXml);
            $array = json_decode($json,TRUE);
            
            $players = $array['player'];
            foreach($players AS $player)
            {
                $bplayer = 0;
                foreach($basic_players AS $bPlr)
                {
                    if ($bPlr['@attributes']['name'] == $player['name'])
                    {
                        $bplayer = $bPlr;
                        break;
                    }
                }
                if ($bplayer==0) continue; //skip specs
                
                $player['accountid'] = $bplayer['@attributes']['accountid'];
                
                $this->Stats_model->update_player_match_stats($match->id, $player['accountid'], $player);
               
            }           
            
            $this->Stats_model->update_match_set_adv_stats($match->id,$match_length );
                        
            //cleanup
            $this->delTree($tmp_dir);
        }
        
    }
    
    /**
 * Unzip the source_file in the destination dir
 *
 * @param   string      The path to the ZIP-file.
 * @param   string      The path where the zipfile should be unpacked, if false the directory of the zip-file is used
 * @param   boolean     Indicates if the files will be unpacked in a directory with the name of the zip-file (true) or not (false) (only if the destination directory is set to false!)
 * @param   boolean     Overwrite existing files (true) or not (false)
 *  
 * @return  boolean     Succesful or not
 */
protected function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true) 
{
  if ($zip = zip_open($src_file)) 
  {
    if ($zip) 
    {
      $splitter = ($create_zip_name_dir === true) ? "." : "/";
      if ($dest_dir === false) $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";
      
      // Create the directories to the destination dir if they don't already exist
      $this->create_dirs($dest_dir);

      // For every file in the zip-packet
      while ($zip_entry = zip_read($zip)) 
      {
        // Now we're going to create the directories in the destination directories
        
        // If the file is not in the root dir
        $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
        if ($pos_last_slash !== false)
        {
          // Create the directory where the zip-entry should be saved (with a "/" at the end)
          create_dirs($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
        }

        // Open the entry
        if (zip_entry_open($zip,$zip_entry,"r")) 
        {
          
          // The name of the file to save on the disk
          $file_name = $dest_dir.zip_entry_name($zip_entry);
          
          // Check if the files should be overwritten or not
          if ($overwrite === true || $overwrite === false && !is_file($file_name))
          {
            // Get the content of the zip entry
            $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            file_put_contents($file_name, $fstream );
            // Set the rights
            chmod($file_name, 0777);
          }
          
          // Close the entry
          zip_entry_close($zip_entry);
        }       
      }
      // Close the zip-file
      zip_close($zip);
    }
  } 
  else
  {
    return false;
  }
  
  return true;
}

/**
 * This function creates recursive directories if it doesn't already exist
 *
 * @param String  The path that should be created
 *  
 * @return  void
 */
protected function create_dirs($path)
{
  if (!is_dir($path))
  {
    $directory_path = "";
    $directories = explode("/",$path);
    array_pop($directories);
    
    foreach($directories as $directory)
    {
      $directory_path .= $directory."/";
      if (!is_dir($directory_path))
      {
        mkdir($directory_path);
        chmod($directory_path, 0777);
      }
    }
  }
}

protected function delTree($dir) { 
   $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
  } 
}