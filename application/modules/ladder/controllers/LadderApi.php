<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class LadderApi extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->database();
        $this->lang->load('auth');
     
    }
    
    function getGame($id, $chatTs=0)
    {
        $chatTs = rawurldecode($chatTs);
        
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }        
        $user = $this->ion_auth->user()->row();

        $this->load->model('lobbys_model');
        $lobby = $this->lobbys_model->get($id);
        
        if ($lobby == null)
        {
            $lobby = new stdClass();
            $lobby->valid = false;
            $lobby->redirect = site_url('ladder/lobby');
        }
        else
        {
            $lobby->valid = true;
            
            //get latest chat.
            $now = microtime(true);
            $lobby->nowChatTime = $now;
            if ($chatTs != 0)
                $now = $chatTs;

            $chat = $this->lobbys_model->getChat($id, $now);
            $lobby->chat = $chat;            
            
            //update true skill data.
            $this->load->library('TrueSkill');  
            $team1 = $this->trueskill->createTeam();
            $team2 = $this->trueskill->createTeam();

            
            foreach($lobby->players AS $player)
            {
                if ($player->player_id == $user->id)
                    $lobby->me = $player;
                
                if ($player->role > 1 && $player->role < 12)
                {
                    //does this user have a rating yet or not?
                    $rating = null;
                    if ($player->rating_mean == null && $player->rating_sd == null)
                    {
                        $rating = $this->trueskill->getDefaultRating();
                        $player->rating_mean = $rating->getMean();
                        $player->rating_sd = $rating->getStandardDeviation();                       
                    }
                    else
                    {
                        $rating = $this->trueskill->getRating($player->rating_mean, $player->rating_sd);
                    }
                    
                    //put on the correct team.
                    $slot = $player->role - 1;
                    $player->sk = $this->trueskill->createPlayer($player);
                    if ($slot >= 1 && $slot <= 5)
                    {
                        $team1->addPlayer($player->sk, $rating);
                    }
                    else
                    {
                        $team2->addPlayer($player->sk, $rating);
                    }      
                    $player->rating_mean = round($player->rating_mean,0);
                }
            }
        
            //simulate the results of both teams winning and losing to gather +/- stats.
            try
            {
                $lobby->matchquality= $this->trueskill->getMatchQuality($team1, $team2);        
                
                $result1 = $this->trueskill->getNewRating($team1, $team2, true);
                $result2 = $this->trueskill->getNewRating($team1, $team2, false);
                
                foreach($lobby->players AS $player)
                {
                    $slot = $player->role - 1;
                    $isTeam1 = ($slot >= 1 && $slot <= 5);
                    
                    $rating1 = $result1->getRating($player->sk);
                    $rating2 = $result2->getRating($player->sk);
                    $player->sk = null;
                    if ($isTeam1)
                    {
                        $player->r1 = round($rating1->getMean() - $player->rating_mean,1);
                        $player->r2 = round($rating2->getMean() - $player->rating_mean,1);
                    }
                    else
                    {
                        $player->r2 = round($rating1->getMean() - $player->rating_mean,1);
                        $player->r1 = round($rating2->getMean() - $player->rating_mean,1);                     
                    }
                    
                }
                
            } catch (Exception $ex) {
                $lobby->matchquality= 0;
            }
        }
        
        $json = json_encode($lobby);
            
        $this->output
        ->set_content_type('application/json')
        ->set_output($json);
    }
    
    function updateMatch()
    {
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if (!$this->ion_auth->logged_in())
        {
            return;
        }    

        $user = $this->ion_auth->user()->row();        
        $this->load->model('lobbys_model');
        $lobby = $this->lobbys_model->get($id);
        $obj = json_decode($data);
        
        if ($obj->cmd == 1)
        {
            $bFound = false;
            //add a vote, but make sure it's from someone in a valid slot.
            foreach($lobby->players AS $player)
            {
                $slot = $player->role -1;
                if ($slot >=1 && $slot <=10)
                {
                    if ($player->player_id == $user->id)
                    {
                        $bFound = true;
                        break;
                    }
                }
            }
            
            if ($bFound)
            {
                $p = array();
                
                $votes = $lobby->votes;
                $votes++;
                $t1votes = $lobby->team1_votes;
                if ($obj->side == 1)
                    $t1votes++;
                
                if ($votes >= $lobby->votes_need)
                {
                    //close the match out too!
                    $p['votes'] = $votes;
                    $p['inprogress'] = false;
                    $p['complete'] = true;
                    
                    //update rating for everyone
                    $this->load->library('TrueSkill');  
                    $team1 = $this->trueskill->createTeam();
                    $team2 = $this->trueskill->createTeam();


                    foreach($lobby->players AS $player)
                    {
                        if ($player->role > 1 && $player->role < 12)
                        {
                            //does this user have a rating yet or not?
                            $rating = null;
                            if ($player->rating_mean == null && $player->rating_sd == null)
                            {
                                $rating = $this->trueskill->getDefaultRating();
                                $player->rating_mean = $rating->getMean();
                                $player->rating_sd = $rating->getStandardDeviation();                       
                            }
                            else
                            {
                                $rating = $this->trueskill->getRating($player->rating_mean, $player->rating_sd);
                            }

                            //put on the correct team.
                            $slot = $player->role - 1;
                            $player->sk = $this->trueskill->createPlayer($player);
                            if ($slot >= 1 && $slot <= 5)
                            {
                                $team1->addPlayer($player->sk, $rating);
                            }
                            else
                            {
                                $team2->addPlayer($player->sk, $rating);
                            }                 
                        }
                    }                    
                    
                    try
                    {
                        $bT1Won = false;
                        if ($t1votes >= $votes/2)
                            $bT1Won = true;
                        $result = $this->trueskill->getNewRating($team1, $team2, $bT1Won);

                        foreach($lobby->players AS $player)
                        {
                            $slot = $player->role - 1;
                            $isTeam1 = ($slot >= 1 && $slot <= 5);

                            $rating = $result->getRating($player->sk);
                            $player->sk = null;
                            
                            if ($player->rating_games == null)
                                $player->rating_games = 0;
                            
                            //update the lobby player data first
                            $rup= array();
                            $rup['starting_mean'] = $player->rating_mean;
                            $rup['starting_sd'] = $player->rating_sd;
                            $rup['new_mean'] = $rating->getMean();
                            $rup['new_sd'] = $rating->getStandardDeviation();
                            $rup['change'] = $rating->getMean() - $player->rating_mean;
                            $this->lobbys_model->updateplayer($lobby->id, $player->player_id, $rup);
                            
                            $rup = array();
                            $rup['rating_mean'] = $rating->getMean();
                            $rup['rating_sd'] = $rating->getStandardDeviation();
                            $rup['rating_games'] = $player->rating_games++;
                            $this->ion_auth->update($player->player_id, $rup);
                        }

                    } catch (Exception $ex) {
                        $lobby->matchquality= 0;
                    }
                    
                }
                
                $p['votes'] = $votes;
                $p['team1_votes'] = $t1votes;
                $this->lobbys_model->update($id, $p);
                
                //mark this user as voting.
                $this->lobbys_model->playerVote($id, $user->id);
                return $this->getGame($id);
                
            }
        }
        
    }
    
    function changeGamePlayer()
    {
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if (!$this->ion_auth->logged_in())
        {
            return;
        }
        
        $user = $this->ion_auth->user()->row();        
        $this->load->model('lobbys_model');
        $lobby = $this->lobbys_model->get($id);
        $obj = json_decode($data);
        if ($lobby->host != $user->id)
        {
            //users can modify themselves.
            if ($obj->p != 0 || $obj->r == 100) //100 is new host role.
                return;
            
            if ($obj->cmd > 0)
                return;
        }
        
        if ($obj->cmd > 0)
        {
            if ($obj->cmd == 1)
            {
                //lock slots.
                $p = array();
                $p['slots_locked'] = true;
                $this->lobbys_model->update($id, $p);
            }
            else if ($obj->cmd == 2)
            {
                //unlock slots
                $p = array();
                $p['slots_locked'] = false;
                $this->lobbys_model->update($id, $p);
            }
            else if ($obj->cmd == 3)
            {
                $votes_needed = 0;
                $total_players = 0;
                foreach($lobby->players AS $player)
                {
                    $slot = $player->role -1;
                    if ($slot >=1 && $slot <=10)
                        $total_players++;
                }
                $votes_needed = ceil($total_players/2);
                //start game.
                $p = array();
                $p['slots_locked'] = true;
                $p['inprogress'] = true;
                $p['votes_need'] = $votes_needed;
                $this->lobbys_model->update($id, $p);
            }
            
            return $this->getGame($id);
        }
        
        if ($obj->r == 100)
        {
            //change hosts.
            $p = array();
            $p['host'] = $obj->p;
            $this->lobbys_model->update($id, $p);
            return $this->getGame($id);
        }
        
        if ($obj->s > 0)
        {
            //voted
            $p = array();
            $p['host'] = $obj->p;
            $this->lobbys_model->update($id, $p);
            
        }
        
        if ($obj->p == 0)
            $obj->p = $user->id;
        
        $oldRole = 0;
        $plr = 0;
        $newPlr = 0;
        foreach($lobby->players as $player)
        {
          if ($player->player_id == $obj->p)
          {
              $oldRole = $player->role;
              $plr = $player;
          }
          
          if ($obj->r > 1 && $player->role == $obj->r)
              $newPlr = $player;
        }
        
        if ($newPlr != 0)
            $newPlr->role = $oldRole;
        
        $plr->role = $obj->r;
        
        if ($newPlr != 0)
        {
            $props = array();
            $props['role'] = $newPlr->role;
            $this->lobbys_model->updateplayer($id, $newPlr->player_id,$props);
        }

        $props = array();
        $props['role'] = $plr->role;
        $this->lobbys_model->updateplayer($id, $plr->player_id,$props);            
        return $this->getGame($id);        
    }
    
    function sendChat()
    {
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if (!$this->ion_auth->logged_in())
        {
            return;
        }
        
        $user = $this->ion_auth->user()->row();        
        $update = json_decode($data);
        $this->load->model('lobbys_model');
        $this->lobbys_model->addChat($id, $user->id, $update->msg);        
    }
    
    function leaveGame($id)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }        
        $user = $this->ion_auth->user()->row();

        $this->load->model('lobbys_model');
        $lobby = $this->lobbys_model->get($id);
        $this->lobbys_model->delPlayer($lobby, $user->id);
        
        $json = json_encode(array());
            
        $this->output
        ->set_content_type('application/json')
        ->set_output($json);
    }
    
    function getLobbys()
    { 
        $this->load->model('lobbys_model');
        $lobbys = $this->lobbys_model->getAll();
        
        $retLobbys = array();
        foreach($lobbys AS $lobby)
        {
            $timestamp = new DateTime($lobby->timestamp);
            $time = date("Y-m-d H:i:s");
            $now = new DateTime($time);
            
            $diff = $now->diff($timestamp);                
            $days = intval( $diff->format('%d') );
            $mins = intval( $diff->format('%i') );
            
            $stripLobby = new stdClass();
            
            //if the timestamp is too old or no players, then delete it.
            if ($lobby->playerCount <= 0 && ( $days > 0 || $mins > 2) )
            {
                if ($lobby->complete == 0 && $lobby->inproress == 0)
                    $this->lobbys_model->delete($lobby->id);
            }
            else
            {
                if ($lobby->complete == 1) continue;
                
                $stripLobby = new stdClass();
                $stripLobby->id = $lobby->id;
                $stripLobby->name = $lobby->name;
                $retLobbys[] = $stripLobby;
            }
        }
        
        $json = json_encode($retLobbys);
            
        $this->output
        ->set_content_type('application/json')
        ->set_output($json);
        
    }
    
}

/* End of file auth_ajax.php */
/* Location: ./application/modules/auth/controllers/auth_ajax.php */