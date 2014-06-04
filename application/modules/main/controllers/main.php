<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('session');
    }
    
    function index()
    {
        $this->twiggy->template('index')->display();
    }
    
    function rules()
    {
        $this->twiggy->template('rules')->display();
    }
    
    function prizepool()
    {
        $this->twiggy->template('prizepool')->display();        
    }
    
    function bfbtournament()
    {
        $this->twiggy->template('bfbtournament')->display();
    }
    
    function bfbheropool()
    {
        $this->twiggy->template('bfbheropool')->display();
    }
    
    function avgStats($matches, $statBlock)
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
        
        if ($totalMatches == 0)
        {
            $statBlock->stats = new stdClass();
            $statBlock->stats->gpm = 0.0;
            $statBlock->stats->kda = 0.0;
            $statBlock->stats->matchlength = 0;
            $statBlock->stats->kills = 0;
            $statBlock->stats->deaths = 0;
            $statBlock->stats->assists = 0;
            $statBlock->stats->creeps = 0;
            $statBlock->stats->neuatrals = 0;
            return;
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

        $statBlock->stats = new stdClass();
        $statBlock->stats->gpm = $gpm;
        $statBlock->stats->kda = $avgkda;
        $statBlock->stats->matchlength = $avgLen;
        $statBlock->stats->kills = $avgKills;
        $statBlock->stats->deaths = $avgDeaths;
        $statBlock->stats->assists = $avgAssists;
        $statBlock->stats->creeps = $avgCreeps;
        $statBlock->stats->neuatrals = $avgNeut;
    }
    function stats()
    {
        $this->load->model('Seasons_model');
        $this->load->model('Teams_model');
        $this->load->model('Stats_model');
        $season = $this->Seasons_model->GetCurrentSeason();
        
        //grab all players with reported matches in this season.
        $players = $this->Teams_model->getActivePlayersInSeason($season->id);
        
        //now we can pull together the full stats for each player
        $stats = array();
        foreach($players AS $player)
        {
            $stat = new stdClass();
            $stat->name = $player->name;
            $stat->id = $player->strife_id;
            $stat->team = $this->Teams_model->getTeamForPlayer($stat->id)->name;
            
            $matches = $this->Teams_model->getPlayerMatchStats($stat->id,0, $season->id);
            $this->avgstats($matches, $stat);
            
            $stats[] = $stat;
        }        
        $this->twiggy->set('stats', $stats);
        
        $heroStats = $this->Stats_model->GetHeroPlayStats($season->id);
        $hstats = array();
        foreach($heroStats AS $hs)
        {
            if (!array_key_exists($hs->name, $hstats ))
            {
               $newstat = new stdClass(); 
               $newstat->name = $hs->name;
               $newstat->count = 0;
               $newstat->win = 0;
               $newstat->winperc = 0;
               $hstats[$hs->name] = $newstat;
            }
            
            $hstats[$hs->name]->count++;
            if ( 
                ($hs->team_id == $hs->home_team_id && $hs->home_team_state_id == 1) ||
                ($hs->team_id == $hs->away_team_id && $hs->away_team_state_id == 1)
                )
            {
                $hstats[$hs->name]->win++;                 
            }
            
            $hstats[$hs->name]->winper = round($hstats[$hs->name]->win / $hstats[$hs->name]->count * 100, 1);                
        }
        
        $this->twiggy->set('herostats', $hstats);
        
        
        $this->twiggy->template('stats')->display();
        
    }
        
    function contact()
    {
        get_instance()->load->library('form_validation');
        
        $this->form_validation->set_rules('from', 'From', 'required|valid_email');
        $this->form_validation->set_rules('subject', 'Subject', 'required|xss_clean');
        $this->form_validation->set_rules('content', 'Message', 'required|xss_clean');

        $msg = "";
        if ($this->form_validation->run() == true)
        {
            $from = $this->input->post('from');
            $subject    = $this->input->post('subject');
            $content = $this->input->post('content');

            $this->load->library('email');

            $this->email->clear();
            $this->email->from($from);
            $this->email->to('support@strifeproleague.com');
            $this->email->subject('CONTACT FORM: ' . $subject);
            $this->email->message($content);

            if ($this->email->send())
            {
                $msg = 'Thank you for the message, we will get back to you shortly';
            }
            else
            {
                $msg = 'Error sending message!';                
            }

            
        }
        
        $this->data = array();
        
                //display the create user form
        //set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : $msg);

        if ($msg != '')
            $this->data['enableBtn'] = false;
        else
            $this->data['enableBtn'] = true;
            

        $this->data['from'] = array(
            'name'  => 'from',
            'id'    => 'from',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('from'),
        );
        $this->data['subject'] = array(
            'name'  => 'subject',
            'id'    => 'subject',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('subject'),
        );
        $this->data['content'] = array(
            'name'  => 'content',
            'id'    => 'content',
            'value' => $this->form_validation->set_value('content'),
        );         


        $this->twiggy->set('data', $this->data);
         
        $this->twiggy->template('contact')->display();
        
    }
    
}
