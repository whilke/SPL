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
    }
    
    public function index()
    {
        $this->load->model('Teams_model');
        $this->load->model('Seasons_model');
        
        $isManager = $this->ion_auth->is_manager();
        
        $season = $this->Seasons_model->GetCurrentSeason();
        if ($season != null)
        {
            $stats = $this->Seasons_model->getTeamsByPoints($season->id, !$isManager);
            $this->twiggy->set('stats', $stats);
        }
        
        $teams = $this->Seasons_model->getTeamsNotInSeason(0);
        $this->twiggy->set('notTeams', $teams);
                
        $this->twiggy->template('index')->display();
        
        
    }
    public function schedule()
    {
        $this->load->model('Seasons_model');
        $isManger = $this->ion_auth->is_manager();
        
        $season = $this->Seasons_model->GetCurrentSeason();
        if ($season != null)
        {
            $matches = $this->Seasons_model->getMatchesForSeason($season->id, !$isManger);
            $this->twiggy->set('matches', $matches);            
        }
                
        $this->twiggy->template('schedule')->display();
    }
    
    public function match($id)
    {
        $this->load->model('Teams_model');
        $this->load->model('Seasons_model');
        $this->load->model('Stats_model');
        
        $match = $this->Seasons_model->getMatch($id);
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
            return  10 + 10;
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
        if (is_array($id))
        {
            $fromajax = $id['fromajax'];
            $id = $id['id'];
        }
        
        //validate form input
        $this->form_validation->set_rules('matchid', 'Match ID', 'xss_clean');
        $this->form_validation->set_rules('replayfile', 'Replay File', 'xss_clean');
        $this->form_validation->set_rules('gloryteam', 'Glory Team', 'required');
        $this->form_validation->set_rules('valorteam', 'Valor Team', 'required');
        $this->form_validation->set_rules('matchjson', 'Match Json', '');
        $this->form_validation->set_rules('glory_state', 'Glory Outcome', 'xss_clean');
        $this->form_validation->set_rules('valor_state', 'Valor Outcome', 'xss_clean');

        $this->load->model('Teams_model');
        $this->load->model('Seasons_model');          
        $this->load->model('Stats_model');
        $match = $this->Seasons_model->getMatch($id);
        
        $matchid    = $this->input->post('matchid');
        $gloryteam  = $this->input->post('gloryteam');
        $valorteam  = $this->input->post('valorteam');
        $matchjson =  $this->input->post('matchjson');
        $glory_state = $this->input->post('glory_state');
        $valor_state = $this->input->post('valor_state');
        $override = $this->input->post('override');
        
        if ($this->form_validation->run() == true)
        {
            
            if (true)
            {
                $matchObj = json_decode($matchjson);
                if ($matchObj)
                {
                    $realMatchId = $matchObj->matchid;
                    $winner = $matchObj->winner;

                    $match->strife_match_id = $realMatchId;
                    
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
                                
                $match->active=false;
                
                $this->Seasons_model->editMatch($match);
                $this->Stats_model->updateMatchStats($id, $gloryteam == $match->homeTeam, 
                        $match->home_team_id, $match->away_team_id, $players );
                
                redirect('standings/match/'.$id, 'refresh'); //use redirects instead of loading views for compatibility with MY_Controller libraries
                
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
        
                     
        
        $this->data['message'] = (validation_errors() ? validation_errors() : $this->session->flashdata('message'));

        $this->twiggy->set('match', $match);
        $this->twiggy->set('fromajax', $fromajax);
        
        $states = $this->Seasons_model->getStates();
        $this->data['states'] = $states;

        
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
    
}
