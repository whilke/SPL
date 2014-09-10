<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Broadcasts_model extends CI_Model
{   
    
    public function get($id)
    {
        $query = $this->db->
            from('broadcasts b')->
            where('b.id', $id)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            return $r;
        }
        
        return null;        
    }
        
    
    public function add($props=array())
    {
       
        $this->db->insert('broadcasts', $props);
        $id = $this->db->insert_id();
        return $id;        
    }
    
    public function edit($id, $props)
    {
        $this->db->trans_begin();
        $this->db->update('broadcasts', $props, array('id' => $id));
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();       
        return TRUE;
    }
    
    public function getList()
    {
        $query = $this->db
                ->select('b.*')
                ->from('broadcasts b')
                ->where('(b.timestamp >= UTC_TIMESTAMP() or b.isMatch = 1) and b.deleted = 0')
                ->order_by('b.timestamp')
                ->get();
        
        if ($query->num_rows > 0)
        {
            return $query->result();
        }
        else
        {
            return null;
        }
    }
    
    
}
