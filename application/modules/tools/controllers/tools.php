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
        
        $this->load->model('Teams_model');
        $this->load->model('Stats_model');
        $this->load->model('Seasons_model');
    }
    
    public function index()
    {

    }
    
    private function sendEmail($from, $to, $subject, $message)
    {
        $this->email->clear();
        $this->email->from($from);
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

        $now = new DateTime('now');
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
            if ($now > $prop_date)
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


                    $this->Seasons_model->confirmMatchProposedTime($match, false);                    
                    
                    $this->Seasons_model->unsetMatchProposedTime($match);                    
                }
                else
                {
                }
            }
            else if ($days >= 1)
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