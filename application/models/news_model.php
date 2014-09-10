<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class News_model extends CI_Model
{   
    
    public function get($id)
    {
        $query = $this->db->
            from('news n')->
            where('n.id', $id)->
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
       
        $this->db->insert('news', $props);
        $id = $this->db->insert_id();
        return $id;        
    }
    
    public function edit($id, $props)
    {
        $this->db->trans_begin();
        $this->db->update('news', $props, array('id' => $id));
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
                ->select('n.*')
                ->from('news n')
                ->where('n.deleted', 0)
                ->order_by('n.timestamp desc')
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
