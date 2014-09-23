<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function SortCasts($a, $b)
{
    $t1 = strtotime($a->timestamp);
    $t2 = strtotime($b->timestamp);
    
    return ($t1 - $t2);
}

class Broadcasts extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Broadcasts_model', 'Broadcasts');
    }
    
    function index()
    {
        $this->load->model('seasons_model', 'Matches');
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
            
            
        }
        
        $this->twiggy->set('casts', $casts);
        $this->twiggy->template('index')->display();
    }
    
    function create($id=0,$fromAjax=false)
    {        
        if (is_array($id))
        {
            $fromAjax = $id['fromajax'];
            $id = $id['id'];
        }
        
        $this->load->model('seasons_model', 'Matches');
        $this->load->library('form_validation');
        $this->load->helper('url');
        $this->lang->load('auth');
        $this->load->helper('language');

        //validate form input        
        $flashMsg = '';
        $desc = $this->input->post('desc');
        if ($desc != '')
        {
            $bId = $this->input->post('broadcast_id');
            $prop_date = $this->input->post('prop_date');
            $prop_time = $this->input->post('prop_time');
            $gmt_prop_date = $this->input->post('gmt_prop_date');
            $gmt_prop_time = $this->input->post('gmt_prop_time');
            $title = $this->input->post('title');
            $url = $this->input->post('url');
            $match_pick = $this->input->post('match_pick');
            
            $props = array();
            if ($match_pick == -1)
            {
                $props['title'] = $title;
                $props['desc'] = $desc;
                $props['url'] = $url;
                $props['isMatch'] = 0;
                $props['timestamp'] = $gmt_prop_date ." ".$gmt_prop_time;
            }
            else
            {
                $props['desc'] = $desc;
                $props['url'] = $url;
                $props['isMatch'] = 1;               
                $props['match_id'] = $match_pick;               
            }
            
            if ($bId > 0)
            {
                $this->Broadcasts->edit($bId, $props);
                
            }
            else 
            {
                $this->Broadcasts->add($props);
            }
            
            redirect('broadcasts', 'refresh');            
            return;
        }
        
        $season = $this->Matches->GetCurrentSeason();
        $matches = $this->Matches->getMatchesForSeason($season->id, -1, false);
        
        
        $cast = null;
        if ($id > 0)
        {
            $cast = $this->Broadcasts->get($id);
            $ex = explode(" ", $cast->timestamp);
            $cast->gmt_prop_date = $ex[0];
            $cast->gmt_prop_time = $ex[1];
        }
        else
        {
            $cast = (object)array(
              'id'=>'0',
              'title'=>'',
              'desc'=>'',
              'gmt_prop_date'=>'',
              'gmt_prop_time'=>'',
              'url'=>''
            );
        }
                       
        $matchList = array();
        $matchList[-1] = 'N/A';
        foreach($matches as $key=>$match)
        {
            $m = $match->homeTeam . ' vs ' . $match->awayTeam . " " . $match->name;            
            $matchList[$match->id] = $m;
        }        
        $this->twiggy->set('matchList', $matchList);        
        $this->twiggy->set('match_pick', set_value('match_pick') );
        
        $this->data = array();
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);
        
        $this->data['title'] = array(
                'name'  => 'title',
                'id'    => 'title',
                'type'  => 'text',
                'value' => set_value('title', $cast->title),
            );        
        
        $this->data['desc'] = array(
                'name'  => 'desc',
                'id'    => 'desc',
                'type'  => 'text',
                'cols'  => '40',
                'rows'  => '5',
                'value' => set_value('desc', $cast->desc),
            );    
        
        $this->data['url'] = array(
                'name'  => 'url',
                'id'    => 'url',
                'type'  => 'text',
                'value' => set_value('url', $cast->url),
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
                'value' => set_value('gmt_prop_date',$cast->gmt_prop_date),
            ); 
        $this->data['gmt_prop_time'] = array(
                'name'  => 'gmt_prop_time',
                'id'    => 'gmt_prop_time',
                'type'  => 'hidden',
                'value' => set_value('gmt_prop_time', $cast->gmt_prop_time),
            ); 

        $this->data['broadcast_id'] = array(
                'name'  => 'broadcast_id',
                'id'    => 'broadcast_id',
                'type'  => 'hidden',
                'value' => set_value('broadcast_id', $cast->id),
            ); 
        
        $this->twiggy->set('data', $this->data);        
        $view = 'create';
        if ( ! $fromAjax)
        {
            $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }
    }
}