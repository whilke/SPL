<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Standings extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->helper('url');
        $this->lang->load('auth');
        $this->load->helper('language');
        
        $this->load->library('email');
        
        $a = array(
            'mailtype' => 'html',
        );
        
        $this->email->initialize($a);
        $this->model('Teams_model');

    }
    
    public function index($id=0)
    {
        $this->model('Seasons_model');
        $this->model('Teams_model');
        
        $isManager = $this->ion_auth->is_manager();
        
        if ($id == 0)
        {
            $season = $this->Seasons_model->GetCurrentSeason();
        }
        else
        {
            $season = $this->Seasons_model->get($id);            
        }
        if ($season != null)
        {
            $stats = $this->Seasons_model->getTeamsByPoints($season->id, 0, !$isManager);
            
            foreach($stats as $stat)
            {
                $team = $this->Teams_model->getById($stat->teamId);
                $plrs = $team->getStarters();
                $subs = $team->getSubs();
                if (count($plrs)+count($subs) >= 5)
                    $stat->isValid = true;
                else
                    $stat->isValid = false;
            }
            
            $this->twiggy->set('stats', $stats);
            $this->twiggy->set('season', $season);
            
            //grab playoffs and tourny stats.
            $Playoff_stats = $this->Seasons_model->getTeamsByPoints($season->id, 1, false);
            if (sizeof($Playoff_stats) > 0)
                $this->twiggy->set('stats_playoffs', $Playoff_stats);

            $Tourny_stats = $this->Seasons_model->getTeamsByPoints($season->id, 2, false);
            if (sizeof($Tourny_stats) > 0)
                $this->twiggy->set('stats_tourny', $Tourny_stats);

        }
        
                       
        $this->twiggy->template('index')->display();
        
        
    }
    public function schedule($id=0)
    {
        $this->model('Seasons_model');
        $isManger = $this->ion_auth->is_manager();
        
        if ($id == 0)
        {
            $season = $this->Seasons_model->GetCurrentSeason();
        }
        else
        {
            $season = $this->Seasons_model->get($id);            
        }

        if ($season != null)
        {
            $matches = $this->Seasons_model->getMatchesForSeason($season->id, 0, !$isManger);
            $this->twiggy->set('matches', $matches);            
            $this->twiggy->set('season', $season);
            
            $matches_playoffs = $this->Seasons_model->getMatchesForSeason($season->id, 1, false);
            if (sizeof($matches_playoffs)>0)
                $this->twiggy->set('matches_playoffs', $matches_playoffs);            

            $matches_tourny = $this->Seasons_model->getMatchesForSeason($season->id, 2, false);
            if (sizeof($matches_tourny)>0)
            $this->twiggy->set('matches_tourny', $matches_tourny);            

        }
                
        $this->twiggy->template('schedule')->display();
    }
    
    public function match($id)
    {
        $this->model('Seasons_model');
        $this->model('Stats_model');
        
        $isManager = $this->ion_auth->is_manager();       
        $user = $this->ion_auth->user()->row();

        $match = $this->Seasons_model->getMatch($id, !$isManager);

        $isOwner = false;
        if($user != null && ($match->homeTeam == $user->teamname ||
           $match->awayTeam == $user->teamname))
            $isOwner = true;

        if ($isOwner && !$isManager)
            $match = $this->Seasons_model->getMatch($id, false);
        
        if ($match->who_proposed_team_id != '')
        {
            $team = $this->Teams_model->getById($match->who_proposed_team_id);
            if ($team == null)
            {
                $team = new stdClass();
                $team->name = "Admin";
            }
            
            $match->who_proposed_team = $team->name;
        }
        else
        {
            $match->who_proposed_team = "";
        }
        
        
        $this->twiggy->set('isOwner', $isOwner);
        $this->twiggy->set('match', $match);
        $stats = $this->Stats_model->getStats($id);
        $this->twiggy->set('stats', $stats);
        
        $home_team = $this->Teams_model->getById($match->home_team_id);
        $away_team = $this->Teams_model->getById($match->away_team_id);

        $this->twiggy->set('home_team', $home_team);
        $this->twiggy->set('away_team', $away_team);
        
        $this->twiggy->template('match')->display();

    }
    
    private function calcPointsFromState($state)
    {
        if ($state == '1' || $state == '4') // win
        {
            return  25 + 10;
        }
        if ($state == '2' || $state == '3') // loss
        {
            return  5 + 10;
        }
        if ($state == '5') // no show
        {
            return 0;
        }
        
        return 0;
    }
    
    private function updatePoints($match, $gloryteam, $gloryState, $valorState)
    {
        if ($gloryteam == $match->homeTeam)
        {
            $match->home_team_points = $this->calcPointsFromState($gloryState);
            $match->home_team_state_id = $gloryState;
            
            $match->away_team_points = $this->calcPointsFromState($valorState);
            $match->away_team_state_id = $valorState;
        }
        else
        {
            $match->away_team_points = $this->calcPointsFromState($gloryState);
            $match->away_team_state_id = $gloryState;
            
            $match->home_team_points = $this->calcPointsFromState($valorState);
            $match->home_team_state_id = $valorState;
        }        
    }
    
    public function edit_match($id, $fromajax=false)
    {          
        
        $user = $this->ion_auth->user()->row();

        if (is_array($id))
        {
            $fromajax = $id['fromajax'];
            $id = $id['id'];
        }
        
        $flashMsg =  "";
        
        //validate form input
        $this->form_validation->set_rules('matchid', 'Match ID', 'xss_clean');
        $this->form_validation->set_rules('replayfile', 'Replay File', 'xss_clean');
        $this->form_validation->set_rules('gloryteam', 'Glory Team', 'required');
        $this->form_validation->set_rules('valorteam', 'Valor Team', 'required');
        $this->form_validation->set_rules('matchjson', 'Match Json', '');
        $this->form_validation->set_rules('glory_state', 'Glory Outcome', 'xss_clean');
        $this->form_validation->set_rules('valor_state', 'Valor Outcome', 'xss_clean');
        $this->form_validation->set_rules('glory_ban', 'Glory Ban', 'xss_clean');
        $this->form_validation->set_rules('valor_ban', 'Valor Ban', 'xss_clean');

        $this->model('Seasons_model');          
        $this->model('Stats_model');
        $match = $this->Seasons_model->getMatch($id);
        
        $isOwner = false;
        if($user != null && ($match->homeTeam == $user->teamname ||
           $match->awayTeam == $user->teamname))
            $isOwner = true;
        
        if (!$this->ion_auth->is_manager() || $isOwner != true)
        {
            
        }
        
        $matchid    = $this->input->post('matchid');
        $gloryteam  = $this->input->post('gloryteam');
        $valorteam  = $this->input->post('valorteam');
        $matchjson =  $this->input->post('matchjson');
        $glory_state = $this->input->post('glory_state');
        $valor_state = $this->input->post('valor_state');
        $glory_ban = $this->input->post('glory_ban');
        $valor_ban = $this->input->post('valor_ban');
        $override = $this->input->post('override');
        
        if ($this->form_validation->run() == true)
        {
            $showDialog = false;
            if ($override == false && ($glory_ban <= 0 || $valor_ban <= 0))
            {
                $flashMsg = "Bans must be selected";
                $showDialog = true;
            }
            
            if ($override == true && (
                    $glory_state > 0 && $glory_state < 3 ||
                    $valor_state > 0 && $valor_state < 3
                    )
                )
            {
                if ($glory_ban <= 0  || $valor_ban <= 0 )
                {
                    $flashMsg = "Bans must be selected";
                    $showDialog = true;
                }
            }
            
            if (!$showDialog)
            {
                $matchObj = json_decode($matchjson);
                if ($matchObj || $override == true)
                {                    
                    if ($matchObj)
                    {
                        $realMatchId = $matchObj->matchid;
                        $winner = $matchObj->winner;

                        $match->strife_match_id = $realMatchId;
                        $match->length = $matchObj->length;

                        if ($winner == "Glory")
                        {
                            $glory_state = "1";
                            $valor_state = "2";
                        }
                        else
                        {
                            $glory_state = "2";
                            $valor_state = "1";     
                        }

                        $this->updatePoints($match, $gloryteam, $glory_state, $valor_state);   

                        $players = $matchObj->players;
                        
                    }
                    else if ($override == true)
                    {
                        $this->updatePoints($match, $gloryteam, $glory_state, $valor_state);
                        $players = null;
                    }
                    
                    if ($gloryteam == $match->homeTeam)
                    {
                        $match->home_team_ban_hero_id = $glory_ban;
                        $match->away_team_ban_hero_id = $valor_ban;
                    }
                    else
                    {
                        $match->away_team_ban_hero_id = $glory_ban;
                        $match->home_team_ban_hero_id = $valor_ban;
                        
                    }
                                        
                                
                    $match->active=false;

                    $this->Seasons_model->editMatch($match);
                    $this->Stats_model->updateMatchStats($id, $gloryteam == $match->homeTeam, 
                            $match->home_team_id, $match->away_team_id, $players );

                    
                    $this->twiggy->set('match', $match);
                    $msg = $this->twiggy->layout('email')->template('match_edit')->render();
                    
                    $email = "";
                    if ($user->teamname == $match->homeTeam)
                        $email = $this->Teams_model->getEmail($match->away_team_id);
                    else
                        $email = $this->Teams_model->getEmail($match->home_team_id);

                    
                    redirect('standings/match/'.$id, 'refresh'); //use redirects instead of loading views for compatibility with MY_Controller libraries
                    
                }    
                else
                {
                   $flashMsg = "Replay file must be uploaded";
                    
                }
            }
        }
        
       
        
        $this->data = Array();
        
        $this->data['teams'] = array( 
            $match->homeTeam => $match->homeTeam,
            $match->awayTeam => $match->awayTeam,
        );
        
        $this->data['gloryteam'] = $this->form_validation->set_value('gloryteam', $match->homeTeam);
        $this->data['valorteam'] = $this->form_validation->set_value('valorteam', $match->awayTeam);
        
        $this->data['matchid'] = array(
                'name'  => 'matchid',
                'id'    => 'matchid',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('matchid'),
            );        
        
        $this->data['glory_state'] = $this->form_validation->set_value('glory_state');
        $this->data['valor_state'] = $this->form_validation->set_value('glory_state');
        $this->data['glory_ban'] = $this->form_validation->set_value('glory_ban');
        $this->data['valor_ban'] = $this->form_validation->set_value('valor_ban');
                     
        
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);

        $this->twiggy->set('match', $match);
        $this->twiggy->set('fromajax', $fromajax);
        
        $states = $this->Seasons_model->getStates();
        $this->data['states'] = $states;

        $heroList[0] = 'Select a hero';
        $heroes = $this->Stats_model->getHeroList();
        foreach($heroes AS $key=>$hero)
            $heroList[$key] = $hero;
        $this->data['heroList'] = $heroList;
        
        
        
        $this->twiggy->set('data', $this->data);
        $view = 'edit_match';
        if ( ! $fromajax)
        {
            $x = $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }        
        
    }
    
    function propose_time($id, $fromajax=false)
    {
        
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth/login', 'refresh');
        }

        $user = $this->ion_auth->user()->row();
         
        if (is_array($id))
        {
            $fromajax = $id['fromajax'];
            $id = $id['id'];
        }
                
        $flashMsg =  "";
        $this->twiggy->set('fromajax', $fromajax);
        
        $this->form_validation->set_rules('check', 'Invalid Data', 'required|xss_clean');

        
        $this->model('Seasons_model');          
        $match = $this->Seasons_model->getMatch($id);
        
        if ($match->who_proposed_team_id != '')
        {
            $team = $this->Teams_model->getById($match->who_proposed_team_id);
            if ($team == null)
            {
                $team = new stdClass();
                $team->name = "Admin";
            }
            
            $match->who_proposed_team = $team->name;
        }
        else
        {
            $match->who_proposed_team = "";
        }
        
        
        $this->twiggy->set('match', $match);

        if ( ($user->teamname != $match->homeTeam && $user->teamname != $match->awayTeam )
           )                
        {
            redirect('auth/login', 'refresh');            
        }


        $check = $this->input->post('check');
        $prop_date = $this->input->post('prop_date');
        $prop_time = $this->input->post('prop_time');
        $gmt_prop_date = $this->input->post('gmt_prop_date');
        $gmt_prop_time = $this->input->post('gmt_prop_time');
        $confirm = $this->input->post('confirm');
        
        if ($this->form_validation->run() == true)
        {        
            if ($check == 1)
            {
                if ($prop_date == "" || $gmt_prop_time == "")
                    $flashMsg = "Date and/or time is invalid";
                else
                {
                    //this is a new challenge request.  
                    
                    $team = $this->Teams_model->get($user->teamname);
                    if ($team == null)
                    {
                        $team = new stdClass();
                        $team->id = 0;
                    }
                    
                    $this->twiggy->set('match', $match);
                    $msg = $this->twiggy->layout('email')->template('matchtime_new')->render();
                    
                    $contactId = 0;
                    if ($user->teamname == $match->homeTeam)
                    {
                        $p = $this->Teams_model->getById($match->away_team_id)->getManager();
                        if ($p != null)
                        {
                            $contactId = $p->id;
                            $this->sendEmail($contactId, 
                                'Match Time System: New Time Request', $msg);
                        }
                        $p = $this->Teams_model->getById($match->away_team_id)->getCaptain();
                        if ($p != null)
                        {
                            $contactId = $p->id;
                            $this->sendEmail($contactId, 
                                'Match Time System: New Time Request', $msg);
                        }   
                    }
                    else
                    {
                        $p = $this->Teams_model->getById($match->home_team_id)->getManager();
                        if ($p != null)
                        {
                            $contactId = $p->id;
                            $this->sendEmail($contactId, 
                                'Match Time System: New Time Request', $msg);
                        }
                        $p = $this->Teams_model->getById($match->home_team_id)->getCaptain();
                        if ($p != null)
                        {
                            $contactId = $p->id;
                            $this->sendEmail($contactId, 
                                'Match Time System: New Time Request', $msg);
                        }              
                    }
                                       
                    $this->Seasons_model->setMatchProposedTime($match, $gmt_prop_date ." ".$gmt_prop_time, $team->id);                    
                    redirect('standings/match/' . $id, 'refresh');
                }
            }
            else if ($check == 2)
            {
                $flashMsg = 'Invalid Request';
                //handle team canceling their request.
                $team = $this->Teams_model->get($user->teamname);
                if ($team == null)
                {
                    $team = new stdClass();
                    $team->id = 0;
                }
                
                if ($team->id == $match->who_proposed_team_id )
                {
                    if ($confirm == 'yes')
                    {
                        $this->Seasons_model->unsetMatchProposedTime($match);                    
                    }
                    redirect('standings/match/' . $id, 'refresh');                                    
                }                
            }
            else if ($check == 3)
            {
                $flashMsg = 'Invalid Request';
                //handle team canceling their request.
                $team = $this->Teams_model->get($user->teamname);
                if ($team == null)
                {
                    $team = new stdClass();
                    $team->id = 0;
                }
                
                if ($team->id != $match->who_proposed_team_id )
                {
                    if ($team->id == $match->home_team_id || $team->id == $match->away_team_id)
                    {
                        $this->twiggy->set('match', $match);
                        $msg = "";
                       
                        
                        if ($confirm == 'yes')
                        {
                            $msg = $this->twiggy->layout('email')->template('matchtime_accept')->render();
                            $this->Seasons_model->confirmMatchProposedTime($match);                    
                        }     
                        else
                        {
                            $msg = $this->twiggy->layout('email')->template('matchtime_reject')->render();
                            $this->Seasons_model->unsetMatchProposedTime($match);                     
                        }
                                                
                        $email = "";
                        if ($user->teamname == $match->homeTeam)
                        {
                            $p = $this->Teams_model->getById($match->away_team_id)->getManager();
                            if ($p != null)
                            {
                                $contactId = $p->id;
                                $this->sendEmail($contactId, 
                                    'Match Time System: Request Update', $msg);
                            }
                            $p = $this->Teams_model->getById($match->away_team_id)->getCaptain();
                            if ($p != null)
                            {
                                $contactId = $p->id;
                                $this->sendEmail($contactId, 
                                    'Match Time System: Request Update', $msg);
                            }
                        } 
                        else
                        {
                            $p = $this->Teams_model->getById($match->home_team_id)->getManager();
                            if ($p != null)
                            {
                                $contactId = $p->id;
                                $this->sendEmail($contactId, 
                                    'Match Time System: Request Update', $msg);
                            }
                            $p = $this->Teams_model->getById($match->home_team_id)->getCaptain();
                            if ($p != null)
                            {
                                $contactId = $p->id;
                                $this->sendEmail($contactId, 
                                    'Match Time System: Request Update', $msg);
                            }
                        }

                        
                        redirect('standings/match/' . $id, 'refresh');                                    
                    }
                }                
            }
        }
        
        $this->data = array();
        
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);

        
        
        $this->data['prop_date'] = array(
                'name'  => 'prop_date',
                'id'    => 'prop_date',
                'type'  => 'text',
                'value' => '',
            );        
        
        $this->data['prop_time'] = array(
                'name'  => 'prop_time',
                'id'    => 'prop_time',
                'type'  => 'text',
                'value' => '',
            ); 
        
        
        $this->data['gmt_prop_date'] = array(
                'name'  => 'gmt_prop_date',
                'id'    => 'gmt_prop_date',
                'type'  => 'hidden',
                'value' => $this->form_validation->set_value('gmt_prop_date'),
            ); 
        $this->data['gmt_prop_time'] = array(
                'name'  => 'gmt_prop_time',
                'id'    => 'gmt_prop_time',
                'type'  => 'hidden',
                'value' => $this->form_validation->set_value('gmt_prop_time'),
            ); 
        
        
        $this->twiggy->set('data', $this->data);
        
        $view = 'propose_time';
        if ( ! $fromajax)
        {
            $x = $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }        
        
    }
    
    private function sendEmail($to, $subject, $message)
    {
        $this->load->library('mahana_messaging');
        $this->mahana_messaging->send_new_message(2, $to, $subject, $message, true);            
    }  
}
