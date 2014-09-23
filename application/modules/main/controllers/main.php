<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function SortCasts($a, $b)
{
    $t1 = strtotime($a->timestamp);
    $t2 = strtotime($b->timestamp);
    
    return ($t1 - $t2);
}


class Main extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('session');
        $this->model('Teams_model');
    }
    
    function getNextBroadcast()
    {
        $this->load->model('seasons_model', 'Matches');
        $this->load->model('broadcasts_model','Broadcasts');
        $casts = $this->Broadcasts->getList();
        
        if ($casts != null)
        {
            foreach($casts as $key=>$cast)
            {
                if ($cast->isMatch == 1)
                {
                    $match = $this->Matches->getMatch($cast->match_id, false);
                    $cast->title = $match->homeTeam . " vs " . $match->awayTeam;
                    $cast->timestamp = $match->gamedate;
                }
                else
                {
                    $cast->timestamp = $cast->timestamp . " GMT";
                    
                }
                
                date_default_timezone_set('GMT');
                $t = strtotime($cast->timestamp . " GMT");
                $now = strtotime('now');
                
                if ($t < $now)
                {
                    unset($casts[$key]);
                }
            }

            usort($casts, "SortCasts");        
            
            if (count($casts) > 0)
                return $casts[0];            
        }
        
        return null;
    }
    
    function index()
    {
        $cast = $this->getNextBroadcast();
        
        //get videos.
        $this->load->model('videos_model','Videos');
        $videos = $this->Videos->getList();
        
        if (count($videos) > 0 && $videos[0]->isFeatured)
        {
            $this->twiggy->set('featureVideo', $videos[0]);
            unset($videos[0]);
        }
        
        $videos = array_slice($videos, 0, 4);
        $this->twiggy->set('videos', $videos);
        
        $this->load->model('News_model', 'News');        
        $news = $this->News->getList();
        $news = array_slice($news,0,4);
        $this->twiggy->set('news', $news);
        
        $this->twiggy->set('nextCast', $cast);
        $this->twiggy->template('index')->display();
    }
    
    function about()
    {
        $this->twiggy->template('about')->display();
    }
    
    function tutorial()
    {
         $this->twiggy->template('tutorial')->display();
    }
    
    function donations()
    {
        
        $url = "https://streamtip.com/api/tips?client_id=541df69d9e47184227347edc&access_token=ODhiMjRmNDdmOTQyZTMzOTQxMTIxYWY1";
        $r = file_get_contents($url);       
        $o = json_decode($r);
        
        $this->twiggy->set('tips', $o->tips);
        
        $this->twiggy->template('donations')->display();
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
    
    function news()
    {
        $this->load->model('News_model', 'News');
        $news = $this->News->getList();
                
        $this->twiggy->set('news', $news);
        $this->twiggy->template('news')->display();
    }
    
    function article($id)
    {
        $this->load->model('News_model', 'News');
        $news = $this->News->get($id);
                
        $this->twiggy->set('news', $news);
        $this->twiggy->template('article')->display();
    }
    
    function newarticle($id=0,$fromAjax=false)
    {        
        if (is_array($id))
        {
            $fromAjax = $id['fromajax'];
            $id = $id['id'];
        }
        
        $this->load->library('form_validation');
        $this->load->helper('url');
        $this->lang->load('auth');
        $this->load->helper('language');
        $this->load->model('News_model', 'News');

        //validate form input        
        $flashMsg = '';
        $desc = $this->input->post('desc');
        if ($desc != '')
        {
            $bId = $this->input->post('id');
            $prop_date = $this->input->post('prop_date');
            $prop_time = $this->input->post('prop_time');
            $gmt_prop_date = $this->input->post('gmt_prop_date');
            $gmt_prop_time = $this->input->post('gmt_prop_time');
            $title = $this->input->post('title');
            
            $props = array();
                $props['title'] = $title;
                $props['desc'] = $desc;
                $props['timestamp'] = $gmt_prop_date ." ".$gmt_prop_time;
            
            if ($bId > 0)
            {
                $this->News->edit($bId, $props);
                
            }
            else 
            {
                $this->News->add($props);
            }
            
            redirect('main/news', 'refresh');            
            return;
        }
        
        
        
        $new = null;
        if ($id > 0)
        {
            $new = $this->News->get($id);
            $ex = explode(" ", $new->timestamp);
            $new->gmt_prop_date = $ex[0];
            $new->gmt_prop_time = $ex[1];
        }
        else
        {
            $new = (object)array(
              'id'=>'0',
              'title'=>'',
              'desc'=>'',
              'gmt_prop_date'=>'',
              'gmt_prop_time'=>'',
              'url'=>''
            );
        }
                       
        $this->data = array();
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);
        
        $this->data['title'] = array(
                'name'  => 'title',
                'id'    => 'title',
                'type'  => 'text',
                'value' => set_value('title', $new->title),
            );        
        
        $this->data['desc'] = array(
                'name'  => 'desc',
                'id'    => 'desc',
                'type'  => 'text',
                'cols'  => '40',
                'rows'  => '5',
                'value' => set_value('desc', $new->desc),
            );    
        
              
       
        $this->data['prop_date'] = array(
                'name'  => 'prop_date',
                'id'    => 'prop_date',
                'type'  => 'text',
                'value' => set_value('prop_date'),
            );        
        
        $this->data['prop_time'] = array(
                'name'  => 'prop_time',
                'id'    => 'prop_time',
                'type'  => 'text',
                'value' => set_value('prop_time'),
            ); 
        
        
        $this->data['gmt_prop_date'] = array(
                'name'  => 'gmt_prop_date',
                'id'    => 'gmt_prop_date',
                'type'  => 'hidden',
                'value' => set_value('gmt_prop_date',$new->gmt_prop_date),
            ); 
        $this->data['gmt_prop_time'] = array(
                'name'  => 'gmt_prop_time',
                'id'    => 'gmt_prop_time',
                'type'  => 'hidden',
                'value' => set_value('gmt_prop_time', $new->gmt_prop_time),
            ); 

        $this->data['id'] = array(
                'name'  => 'id',
                'id'    => 'id',
                'type'  => 'hidden',
                'value' => set_value('id', $new->id),
            ); 
        
        $this->twiggy->set('data', $this->data);        
        $view = 'newarticle';
        if ( ! $fromAjax)
        {
            $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }
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
       

        $gpm =  floor(round($gpm / $totalMatches));
        $avgLen = floor(round($avgLen / $totalMatches,1));
        $avgKills = floor(round($avgKills / $totalMatches,1));
        $avgDeaths = floor(round($avgDeaths / $totalMatches,1));
        $avgAssists = floor(round($avgAssists / $totalMatches,1));
        $avgCreeps = floor(round($avgCreeps / $totalMatches,1));
        $avgNeut = floor(round($avgNeut / $totalMatches,1));
        
        $d = $avgDeaths;
        if ($d < 1) 
           $d =1.0;
        $avgkda = (round(($avgKills + $avgAssists) / $d, 2));        


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
