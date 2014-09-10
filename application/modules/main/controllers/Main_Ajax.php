<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main_Ajax extends Ajax_Controller {

    /**
     * Launch dialog that contains a specific Ion auth page content
     *
     * This function is only be used for a quick example
     * about displaying page content (without master page) inside a dialog.
     * It also helps to keep the Ion auth's controller structure unchanged
     * so you can freely modify it yourself.
     */
    function dialog($page, $id=0)
    {
        if (in_array($page, array(
            'newarticle',
        )))
        {
            $this->response->dialog(array(
                'body' => Modules::run('main/' . $page, array('id'=>$id,'fromajax'=> true))
            ));
        }
        $this->response->send();
    }
}

/* End of file auth_ajax.php */
/* Location: ./application/modules/auth/controllers/auth_ajax.php */