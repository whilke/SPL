<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Standings extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        $this->load->model('Teams_model');
        $this->load->model('Seasons_model');
        
        $season = $this->Seasons_model->GetCurrentSeason();

        $stats = $this->Seasons_model->getTeamsByPoints($season->id);
        $this->twiggy->set('stats', $stats);
                
        $this->twiggy->template('index')->display();
        
        
    }
    public function schedule()
    {
        $this->load->model('Seasons_model');
        
        $season = $this->Seasons_model->GetCurrentSeason();
        $matches = $this->Seasons_model->getMatchesForSeason($season->id);
        $this->twiggy->set('matches', $matches);
                
        $this->twiggy->template('schedule')->display();
    }
    
}
