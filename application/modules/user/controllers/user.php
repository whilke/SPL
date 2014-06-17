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
        
        $canInviteMember = false;
        $canInviteSub = false;
        if ($isTeamOwner)
        {
            //does this owner have spots to invite someone with?
            $team = $this->Teams_model->getById($user->team_id);
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
        }
        $this->twiggy->set('canInviteMember', $canInviteMember);
        $this->twiggy->set('canInviteSub', $canInviteSub);
        
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
                
        $this->ion_auth->update_user_extra($id, $update);

    }    
    
}

