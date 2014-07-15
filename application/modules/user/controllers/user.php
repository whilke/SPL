<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('authentication', NULL, 'ion_auth');
        $this->load->library('form_validation');
        $this->load->helper('url');


        $this->load->database();
        $this->lang->load('auth');
        $this->load->helper('language');    
     
    }
      
    
    function index()
    {
        $this->twiggy->template('index')->display();
    }
    
    function invite($id, $r)
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }
        
        $user = $this->ion_auth->user()->row(); 
        
        $this->load->model('Teaminvites_model');
        $invite = $this->Teaminvites_model->get($id);
        if ($invite == null || $invite->user_id != $user->id)
        {
            redirect('main', 'refresh');            
        }
        
        if ($r == 1)
        {
            $this->load->model('Teams_model');
            
            //lets get them on the team!
            $invitePlayer = $this->ion_auth->user($invite->user_id)->row();
            $isRegPlayer = $invitePlayer->isregplayer;
            
            
            $this->load->model('Seasons_model');
            $season = $this->Seasons_model->GetCurrentSeason();
            $this->Teams_model->joinUser($invite->team_id, $invite->user_id, $season->id);
            
            //defaults to a sub.
            //
            //promote to the right level
            if ($invite->role == 'Member')
            {
                $this->Teams_model->promote($invite->team_id, $invite->user_id, 2);
            }            
            
            //remove all pending invites.
            $this->load->model('Teaminvites_model');
            $invites =$this->Teaminvites_model->getByUser($invite->user_id);
            foreach($invites AS $invite)
                $this->Teaminvites_model->delete($invite->id);

            //remove an invite from the team count if they are in a season, and this user was a registered player.
            
            $this->load->model('Seasons_model');
            $season = $this->Seasons_model->GetCurrentSeason();
            $isInSeason = $this->Seasons_model->isTeamInSeason($invite->team_id, $season->id);
            $team = $this->Teams_model->getById($invite->team_id);
            if ($isInSeason && $isRegPlayer)
            {
                if ($team->invites == 1)
                {
                    //this is their last invite, so we also need to remove all pending invites to this team.
                    $invites =$this->Teaminvites_model->getbyTeam($invite->team_id);
                    foreach($invites AS $invite)
                        $this->Teaminvites_model->delete($invite->id);                
                }                
                $data = array();
                $data['invites'] = $team->invites-1;
                $this->Teams_model->edit($team->id, $data);
                
            }
            
            
            //send out an invite message
            $msg = $user->username . " has accepted your team invite";
            $this->load->library('mahana_messaging');
            $this->mahana_messaging->send_new_message($invite->user_id, $invite->sender_id, "Team invite accepted", $msg);
                        
        }
        else
        {
            //send out an invite message
            $msg = $user->username . " has declined your team invite";
            $this->load->library('mahana_messaging');
            $this->mahana_messaging->send_new_message($invite->user_id, $invite->sender_id, "Team invite rejected", $msg);            
        }

        redirect('user/portal', 'refresh');            

    }
    
    function portal($id=0)
    {
        $user = null;
        $isTeamOwner = false;
        if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();
            $isTeamOwner = $this->ion_auth->is_team_owner();            
        }
        $this->twiggy->set('isTeamOwner', $isTeamOwner);

        $this->load->model('Teams_model');
        $this->load->model('Teaminvites_model');
        
        
        if ($id == 0)
        {
            if ($user == null)
            {
                 redirect('/', 'refresh');
            }            
            $id = $user->id;
        }
        
        $portalUser = $this->ion_auth->user($id)->row();
        $portalUser->extra = $this->ion_auth->user_extra($id)->row();
        
        if ($portalUser->extra->bio != null)
        {
            $this->load->library('markdown');
            $bio = $this->markdown->parse($portalUser->extra->bio);
            $bio = $this->security->xss_clean($bio);
            $portalUser->extra->bioHtml = $bio;            
        }
                
        //pull stats for this user.
        $player = new stdClass();
        $player->matches = null;
        $player->AvgStats = new stdClass();
        $player->AvgStats->gpm = 0;
        $player->AvgStats->kda = 0;
        $player->AvgStats->matchlength = 0;
        $player->AvgStats->kills = 0;
        $player->AvgStats->deaths = 0;
        $player->AvgStats->assists = 0;
        $player->AvgStats->creeps = 0;
        $player->AvgStats->neuatrals = 0;
        
        $strifeId = $portalUser->strife_id;
        if ($strifeId > 0)
        {
            $player = $this->Teams_model->getPlayer($strifeId);
            if ($player != null)
            {
                $matchid = 0;     
                $matches = $this->Teams_model->getPlayerMatchStats($strifeId, $matchid);

                if ($matches == null)
                {
                    
                }
                else
                {
                    if ($matchid != 0)
                    {
                        $player->stats = $matches[0];
                    }
                    else
                    {
                        $gpm = 0.0;
                        $avgLen = 0.0;
                        $avgKills = 0;
                        $avgDeaths = 0;
                        $avgAssists = 0;
                        $avgkda = 0.0;
                        $avgCreeps = 0;
                        $avgNeut = 0;

                        $totalMatches = 0;
                        foreach($matches AS $match)
                        {
                            $gpm += $match->gpm;
                            $avgLen += $match->matchlength;
                            $avgKills += $match->kills;
                            $avgDeaths += $match->deaths;
                            $avgAssists += $match->assists;
                            $avgCreeps += $match->creeps;
                            $avgNeut += $match->neutrals;

                            $totalMatches++;
                        }

                        $d = $avgDeaths;
                        if ($d == 0) $d = 1;
                        $avgkda = round(($avgKills + $avgAssists) / $d, 1);

                        $gpm =  round($gpm / $totalMatches);
                        $avgLen = round($avgLen / $totalMatches);
                        $avgKills = round($avgKills / $totalMatches);
                        $avgDeaths = round($avgDeaths / $totalMatches);
                        $avgAssists = round($avgAssists / $totalMatches);
                        $avgCreeps = round($avgCreeps / $totalMatches);
                        $avgNeut = round($avgNeut / $totalMatches);

                        $player->AvgStats = new stdClass();
                        $player->AvgStats->gpm = $gpm;
                        $player->AvgStats->kda = $avgkda;
                        $player->AvgStats->matchlength = $avgLen;
                        $player->AvgStats->kills = $avgKills;
                        $player->AvgStats->deaths = $avgDeaths;
                        $player->AvgStats->assists = $avgAssists;
                        $player->AvgStats->creeps = $avgCreeps;
                        $player->AvgStats->neuatrals = $avgNeut;

                        $player->matches = $matches;                   
                    }
                }

            }
        }               
        $portalUser->strifePlayer = $player;
        
        $isOwner = false;
        $isManager = $this->ion_auth->is_manager();
        if ($isManager ||  
           ( $user != null && $id == $user->id) 
                    )
            $isOwner = true;        
        
        $this->twiggy->set('isOwner', $isOwner);        
        $this->twiggy->set('portalUser', $portalUser);
        
        
        $invites = $this->Teaminvites_model->getByUser($portalUser->id);
        foreach($invites AS $invite)
        {
            $invite->team_name = $this->Teams_model->getById($invite->team_id)->name;
        }
        $this->twiggy->set('invites', $invites);
        
        $canInviteMember = false;
        $canInviteSub = false;
        if ($isTeamOwner)
        {
            //does this player alreay have an outstanding invite from this team?
            $bHasInvite=false;
            foreach($invites AS $invite)
            {
                if ($invite->team_id == $user->team_id)
                {
                    $bHasInvite = true;
                    break;
                }
            }
            
            //does this team even have an invite ticket and in an active season.
            $this->load->model('Seasons_model');
            $season = $this->Seasons_model->GetCurrentSeason();
            $isInSeason = $this->Seasons_model->isTeamInSeason($user->team_id, $season->id);
            $team = $this->Teams_model->getById($user->team_id);
            if ($isInSeason && ( $team->invites == 0 && $portalUser->isregplayer) )
                $bHasInvite = true;

            
            if (!$bHasInvite)
            {
                //does this owner have spots to invite someone with?
                $players= $team->players;
                $tot_members = 0;
                $tot_subs = 0;

                foreach($players AS $player)
                {
                    if (array_key_exists( 'isMember', $player->bestGroup ) )
                        $to_members++;
                    else if (array_key_exists( 'isSub', $player->bestGroup ))
                        $tot_subs++;
                }

                if ($tot_members < 4)
                    $canInviteMember = true;
                if ($tot_subs < 2)
                    $canInviteSub = true;   
                
                if ($isInSeason)
                    $this->twiggy->set('inviteCount', $team->invites);
                
                if ($portalUser->isregplayer)
                    $this->twiggy->set('playerType', 'Registered Player');
                else
                    $this->twiggy->set('playerType', 'Free Agent');
            }
        }
        $this->twiggy->set('canInviteMember', $canInviteMember);
        $this->twiggy->set('canInviteSub', $canInviteSub);
                
        
        $this->twiggy->template('portal')->display();
    }
    
    public function save()
    {
        $id = $this->input->post('id');
        $data = $this->input->post('data');
        
        if (!$this->ion_auth->logged_in())
        {
             redirect('/', 'refresh');
        }
        
        $portalUser = $this->ion_auth->user($id)->row();
        $user = $this->ion_auth->user()->row();
        $isManager = $this->ion_auth->is_manager();
        $isOwner = false;
        {
            if ($user->id == $portalUser->id)
                $isOwner = true;
        }
        
        if ($isManager) $isOwner = true;
        
        if (!$isOwner) return;
        
        $update = json_decode($data);
        
        if (property_exists($update, "username"))
        {
            $update2 = array();
            $update2['username'] = $update->username;
            
            
            $obj = new stdClass();
            $obj->username = $update->username;
            //see if there is a user by this name already.
            if ( !$this->ion_auth->username_check($update->username))
            {
                $this->ion_auth->update($id, $update2);    
                $obj->code = 1;
            }
            else
            {
                $obj->code = 0;
            }
            
            $json = json_encode($obj);
            $this->output
            ->set_content_type('application/json')
            ->set_output($json);                
        }
        else
        {
            $this->ion_auth->update_user_extra($id, $update);        
        }

    }    
    
}

