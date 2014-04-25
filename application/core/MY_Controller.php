<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

// Load the MX_Controller class
require APPPATH . 'third_party/MX/Controller.php';

class MY_Controller extends MX_Controller {

    private $_ci;

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
            
            $this->twiggy->set('user', array(
                'uname' => $user->teamname, 
                'isvalid' => 'true', 
                'isAdmin' => $isAdmin 
               ), TRUE);
        }
        else
        {
            $this->twiggy->set('user', array(
                'uname' => '', 
                'isvalid' => 'false',
                'isAdmin' => 'false'
                
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
            
            $this->twiggy->set('user', array('uname' => $user->username, 'isvalid' => 'true'), TRUE);
        }
        else
        {
            $this->twiggy->set('user', array('uname' => '', 'isvalid' => 'false'), TRUE);
        }

    }
}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */