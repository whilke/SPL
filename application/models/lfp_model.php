<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lfp_model extends CI_Model
{
    
    public function add($teamId, $desc, $props)
    {
        $props['team_id'] = $teamId;
        $props['desc'] = $desc;
        
        $this->db->insert('looking_for_players', $props);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function delete($id)
    {
        $this->db->delete('looking_for_players', array('id'=>$id));        
    }
    
    public function get($id)
    {
         $query = $this->db->
            from('looking_for_players')->
            where('id', $id)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            return $r;
        }
        
        return null;
    }
    
    public function getAll()
    {
        $query = $this->db
         ->from('looking_for_players')
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $row;
        }
        return $data;        
    }
    
    public function getByTeam($teamId)
    {
        $query = $this->db
         ->from('looking_for_players')
         ->where('team_id', $teamId)
         ->limit(1)
         ->get();   
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            return $r;
        }
        
        return null;
        
    }
    
}