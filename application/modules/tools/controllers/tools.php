<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tools extends MY_Controller 
{
    function __construct()
    {       
        $this->load->database();       
        $this->load->model('Teams_model');
        $this->load->model('Stats_model');
    }
    
    public function index()
    {
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