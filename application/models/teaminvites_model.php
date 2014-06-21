<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Teaminvites_model extends CI_Model
{
    
    public function add($teamId, $senderId, $userId, $role, $type=0)
    {
        $data = array(
            'team_id'=>$teamId,
            'user_id'=>$userId,
            'sender_id'=>$senderId,
            'role'=>$role,
            'invite_type'=>$type,
        );
        
        $this->db->insert('team_invites', $data);
        $id = $this->db->insert_id();
        return $id;
    }
    
    public function delete($id)
    {
        $this->db->delete('team_invites', array('id'=>$id));        
    }
    
    public function get($id)
    {
         $query = $this->db->
            from('team_invites ti')->
            where('id', $id)->
            limit(1)->
            get();
        
        if ($query->num_rows() === 1)
        {
            $invite = $query->row();
            return $invite;
        }
        
        return null;
    }
    
    public function getByUser($userId)
    {
        $query = $this->db
         ->from('team_invites ti')
         ->where('user_id', $userId)
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $row;
        }
        return $data;
    }
    
    public function getbyTeam($teamId)
    {
        $query = $this->db
         ->from('team_invites ti')
         ->where('team_id', $teamId)
         ->get();   
        
        $data = array();
        foreach($query->result() as $row)
        {
           $data[] = $row;
        }
        return $data;
    }
}