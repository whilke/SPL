<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('session');
        $this->model('Teams_model');
    }
    
    function index()
    {
        $this->twiggy->template('index')->display();
    }
    
    function about()
    {
        $this->twiggy->template('about')->display();
    }
    
    function search()
    {
        $this->load->model('Teams_model');
        $search = $this->input->post('search');
        $search = trim($search);
        if ($search == '' || $search == null || strlen($search) < 3)
        {
            if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_manager())
            {
                $teams = null;
                $users = null;
                
                $this->twiggy->set('teams', $teams);
                $this->twiggy->set('users', $users);
                $this->twiggy->set('search', '---Invalid Search Term---');
                $this->twiggy->template('search')->display();  
                return;
            }
        }
        
        $teams = $this->Teams_model->search($search);
        $users = $this->ion_auth->search($search);
        
        $this->twiggy->set('teams', $teams);
        $this->twiggy->set('users', $users);
        $this->twiggy->set('search', $search);
        $this->twiggy->template('search')->display();
        
    }
    
    function rules()
    {
        $this->model('Issues_model');
        $issues = $this->Issues_model->getList(false);
        $this->twiggy->set('issues', $issues);
        
        $this->twiggy->template('rules')->display();
        
    }
    
    function prizepool($id=0)
    {
        $this->model('Seasons_model');
        if ($id == 0)
        {
            $season = $this->Seasons_model->GetCurrentSeason();
        }
        else
        {
            $season = $this->Seasons_model->get($id);            
        }
        
        if ($season == null)
        {
            $this->twiggy->template('prizepool')->display();                    
            
        }
        else
        {
            $this->twiggy->template('prizepool' . $season->id)->display();                    
        }
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
        $avgkda = round(($avgKills + $avgAssists) / $d / $totalMatches, 1);
        

        $gpm =  round($gpm / $totalMatches);
        $avgLen = round($avgLen / $totalMatches,1);
        $avgKills = round($avgKills / $totalMatches,1);
        $avgDeaths = round($avgDeaths / $totalMatches,1);
        $avgAssists = round($avgAssists / $totalMatches,1);
        $avgCreeps = round($avgCreeps / $totalMatches,1);
        $avgNeut = round($avgNeut / $totalMatches,1);

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
    function stats($id=0)
    {
        $this->model('Seasons_model');
        $this->model('Stats_model');

        if ($id == 0)
        {
            $season = $this->Seasons_model->GetCurrentSeason();
        }
        else
        {
            $season = $this->Seasons_model->get($id);            
        }

        $this->twiggy->set('season', $season);
        
        if ($season == null) 
        {
            $this->twiggy->template('stats')->display();     
            return;
        }
        
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
        $heroBans = $this->Stats_model->GetHeroBanStats($season->id);
        $hstats = array();
        $totalBans = 0;
        foreach($heroBans as $ban)
        {
            $totalBans += $ban->count;
        }
        $totalBans /= 2;
        
        foreach($heroStats AS $hs)
        {
            if (!array_key_exists($hs->name, $hstats ))
            {
               $newstat = new stdClass(); 
               $newstat->name = $hs->name;
               $newstat->count = 0;
               $newstat->win = 0;
               $newstat->ban = 0;
               $newstat->banperc = 0;
               $newstat->winperc = 0;
                          
                if (array_key_exists($hs->name, $heroBans ))
                {
                     $newstat->ban = $heroBans[$hs->name]->count;
                }

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
            
            if ($totalBans == 0) $totalBans = 1;
            $hstats[$hs->name]->winper = round($hstats[$hs->name]->win / $hstats[$hs->name]->count * 100, 1);                
            $hstats[$hs->name]->banperc = round($hstats[$hs->name]->ban / $totalBans * 100, 1);                
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
            $this->email->to('requests@strifeproleague.org');
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
