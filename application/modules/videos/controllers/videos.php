<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function SortCasts($a, $b)
{
    $t1 = strtotime($a->timestamp);
    $t2 = strtotime($b->timestamp);
    
    return ($t1 - $t2);
}

class Videos extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Videos_model', 'Videos');
    }
    
    function index()
    {
        $videos = $this->Videos->getList();
                      
        $this->twiggy->set('videos', $videos);
        $this->twiggy->template('index')->display();
    }
    
    function player($id)
    {
        $video = $this->Videos->get($id);
        $this->twiggy->set('video', $video);
        $this->twiggy->template('player')->display();
    }
    
    function create($id=0,$fromAjax=false)
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

        //validate form input        
        $flashMsg = '';
        $dateline = $this->input->post('dateline');
        if ($dateline != '')
        {
            $bId = $this->input->post('id');
            $title = $this->input->post('title');
            $url = $this->input->post('url');
            $who = $this->input->post('who');
            $desc = $this->input->post('desc');
            $feature = $this->input->post('isFeatured');
            $playlist = $this->input->post('isPlaylist');
            
            $props = array();
            $props['title'] = $title;
            $props['who'] = $who;
            if ($playlist)
            {
                $props['playlist'] = $url;
                $props['url'] = null;
            }
            else
            {
                $props['url'] = $url;
                $props['playlist'] = null;
            }
            
            $props['dateline'] = $dateline;
            $props['desc'] = $desc;
            $props['isFeatured'] = $feature;
            
            if ($bId > 0)
            {
                $this->Videos->edit($bId, $props);
                
            }
            else 
            {
                $this->Videos->add($props);
            }
            
            redirect('videos', 'refresh');            
            return;
        }
        
      
        $video = null;
        if ($id > 0)
        {
            $video = $this->Videos->get($id);
            if ($video->url == '')
            {
                $video->isPlaylist = true;
                $video->url = $video->playlist;
            }
        }
        else
        {
            $video = (object)array(
              'id'=>'0',
              'title'=>'',
              'who'=>'',
              'dateline'=>'',
              'url'=>'',
              'desc'=>'',
              'isFeatured'=>'',  
              'isPlaylist'=>'',
            );
        }

        $this->data = array();
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashMsg);
        
        $this->data['title'] = array(
                'name'  => 'title',
                'id'    => 'title',
                'type'  => 'text',
                'value' => set_value('title', $video->title),
            );        
        
    
        $this->data['desc'] = array(
                'name'  => 'desc',
                'id'    => 'desc',
                'type'  => 'text',
                'cols'  => '40',
                'rows'  => '5',
                'value' => set_value('desc', $video->desc),
            );    
        $this->data['who'] = array(
                'name'  => 'who',
                'id'    => 'who',
                'type'  => 'text',
                'value' => set_value('who', $video->who),           
            );    
        
        $this->data['url'] = array(
                'name'  => 'url',
                'id'    => 'url',
                'type'  => 'text',
                'value' => set_value('url', $video->url),
            );            

        $this->data['isFeatured'] = array(
                'name'  => 'isFeatured',
                'id'    => 'isFeatured',
                'value' => '1',
                'checked' => set_value('isFeatured', $video->isFeatured),
            );            
        
        
        $this->data['isPlaylist'] = array(
                'name'  => 'isPlaylist',
                'id'    => 'isPlaylist',
                'value' => '1',
                'checked' => set_value('isPlaylist', $video->isPlaylist),
            );
       
        $this->data['dateline'] = array(
                'name'  => 'dateline',
                'id'    => 'dateline',
                'type'  => 'text',
                'value' => set_value('dateline', $video->dateline),
            );        
        
        
        $this->data['id'] = array(
                'name'  => 'id',
                'id'    => 'id',
                'type'  => 'hidden',
                'value' => set_value('broadcast_id', $video->id),
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