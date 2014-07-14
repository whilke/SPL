<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Issues_model extends CI_Model
{   
    public function getList($active=true, $userVoter=0)
    {
       
        $query = $this->db->
            from('admin_issues ai')->
            where('ai.active', $active)->
            order_by('ai.timestamp');
        
        if ($userVoter > 0)
        {
            $query = $query->
                    join('admin_issues_votes aiv', 'aiv.issue_id=ai.id', 'left outer')->
                    where('aiv.user_id', $userVoter);
        }   
                
        $query = $query->get();
        $data = array();
        foreach($query->result() as $row)
        {
           $row->desc = nl2br($row->desc);
           $row->votes_to_close = $row->votes_needed - $row->yes_votes - $row->no_votes - $row->ap_votes;
           $row->passed = 'No';
           if ( $row->yes_votes > $row->votes_needed/2)
           {
               $row->passed ='Yes';
           }
           
           $data[] = $row;
        }
        return $data;     
    }
    
    public function get($id, $userId=0)
    {
        $query = $this->db->
            from('admin_issues ai')->
            where('ai.id', $id)->
            limit(1);
        
        if ($userId > 0)
        {
            $query = $query->
                   join('admin_issues_votes aiv', 'aiv.issue_id=ai.id', 'left outer')->
                   where('aiv.user_id', $userId);
        }
        
        $query = $query->get();
        
        if ($query->num_rows() === 1)
        {
            $r = $query->row();
            $r->desc = nl2br($r->desc);

            return $r;
        }
        
        return null;        
    }
        
    
    public function add($name, $desc, $votesNeeded=3, $props=array())
    {
        $props['name'] = $name;
        $props['desc'] = $desc;
        $props['votes_needed'] = $votesNeeded;
        $props['active'] = true;
        
        $this->db->insert('admin_issues', $props);
        $id = $this->db->insert_id();
        return $id;        
    }
    
    public function addVote($id, $userId, $voteCode)
    {
        $issue = $this->get($id);
        
        //first insert a record that this player voted.
        $props = array();
        $props['user_id'] = $userId;
        $props['issue_id'] = $id;
        $this->db->insert('admin_issues_votes', $props);
        
        //update the issue with the correct vote now.
        $props = array();
        
        $total_votes = $issue->yes_votes + $issue->no_votes + $issue->ap_votes;
        $total_votes++;
        if ($total_votes >= $issue->votes_needed)
        {
            $props['active'] = false;
        }
        if ($voteCode == 0)
        {
            $props['yes_votes'] = $issue->yes_votes+1;
        }
        else if ($voteCode == 1)
        {
            $props['no_votes'] = $issue->no_votes+1;
        }
        else if ($voteCode == 2)
        {
            $props['ap_votes'] = $issue->ap_votes+1;
        }        

        $this->db->trans_begin();
        $this->db->update('admin_issues', $props, array('id' => $id));
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();     
        return TRUE;
    }
}
