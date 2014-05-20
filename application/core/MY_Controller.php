<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

// Load the MX_Controller class
require APPPATH . 'third_party/MX/Controller.php';

class MY_Controller extends MX_Controller {

    private $_ci;
    
    public function __destruct() 
    {
        if (isset($this->_ci->db))
        {
            if (isset($this->_ci->db->queries))
            {
                $queries = $this->_ci->db->queries;
                foreach($queries AS $query)
                {
                    if (strpos($query, 'UPDATE') === 0 ||
                        strpos($query, 'INSERT') === 0 ||
                        strpos($query, 'DELETE') === 0     )
                    {
                        $userid = 0;
                        if ($this->ion_auth->logged_in())
                            $userid = $this->ion_auth->user()->row()->id;
                        
                        try {
                            $this->ion_auth->add_audit($userid, $query);                        
                        } catch (Exception $ex) {

                        }
                    }
                }
            }
        }
    }

    public function __construct()
    {
        parent::__construct();

        $this->_ci =& get_instance();
        
        $this->load->library('session');
        $this->load->library('authentication', NULL, 'ion_auth');
        
        if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();
            $isAdmin = $this->ion_auth->is_admin();
            $isGlobalManager = false;
            $isManager = false;
            if (!$isAdmin)
            {
                $isGlobalManager = $this->ion_auth->in_group(3);
                if (!$isGlobalManager)
                {
                    $isManager = $this->ion_auth->in_group(4);
                }
                else
                {
                    $isManager = true;
                }
            }
            else
            {
                $isGlobalManager = true;
                $isManager = true;
            }
            
            $this->twiggy->set('user', array(
                'uname' => $user->teamname, 
                'isvalid' => true, 
                'isAdmin' => $isAdmin, 
                'isGlobalManager' => $isGlobalManager, 
                'isManager' => $isManager
               ), TRUE);
        }
        else
        {
            $this->twiggy->set('user', array(
                'uname' => '', 
                'isvalid' => false,
                'isAdmin' => false, 
                'isGlobalManager' => false, 
                'isManager' => false                 
             ), TRUE);
        }
    }

    /**
     * Load Javascript inside the page's body
     * @access  public
     * @param   string  $script
     */
    public function _load_script($script)
    {
        if (isset($this->_ci->template) && is_object($this->_ci->template))
        {
            // Queue up the script to be executed after the page is completely rendered
            echo <<< JS
<script>
    var CIS = CIS || { Script: { queue: [] } };
    CIS.Script.queue.push(function() { $script });
</script>
JS;
        }
        else
        {
            echo '<script>' . $script . '</script>';
        }
    }
}

class Ajax_Controller extends MY_Controller {

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->library('response');
        $this->load->library('authentication', NULL, 'ion_auth');
        
       if ($this->ion_auth->logged_in())
        {
            $user = $this->ion_auth->user()->row();
            $isAdmin = $this->ion_auth->is_admin();
            $isGlobalManager = false;
            $isManager = false;
            if (!$isAdmin)
            {
                $isGlobalManager = $this->ion_auth->in_group(3);
                if (!$isGlobalManager)
                {
                    $isManager = $this->ion_auth->in_group(4);
                }
                else
                {
                    $isManager = true;
                }
            }
            else
            {
                $isGlobalManager = true;
                $isManager = true;
            }
            
            $this->twiggy->set('user', array(
                'uname' => $user->teamname, 
                'isvalid' => 'true', 
                'isAdmin' => $isAdmin, 
                'isGlobalManager' => $isGlobalManager, 
                'isManager' => $isManager
               ), TRUE);
        }
        else
        {
            $this->twiggy->set('user', array(
                'uname' => '', 
                'isvalid' => 'false',
                'isAdmin' => 'false', 
                'isGlobalManager' => 'false', 
                'isManager' => 'false'                 
             ), TRUE);
        }
 
    }
}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */