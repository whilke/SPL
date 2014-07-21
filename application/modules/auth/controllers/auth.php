<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller {

    public $data;

    function __construct()
    {

        parent::__construct();
        $this->load->library('authentication', NULL, 'ion_auth');

        // Load MongoDB library instead of native db driver if required
        $this->config->item('use_mongodb', 'ion_auth') ?
        $this->load->library('mongo_db') :

        $this->load->database();

        $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

        $this->lang->load('auth');
        $this->load->helper('language');
    }

    //redirect if needed, otherwise display the user list
    function index($fromajax=false)
    {        
        $this->model('Teams_model');        
        if (!$this->ion_auth->logged_in())
        {
            //redirect them to the login page
            redirect('auth/login', 'refresh');
        }
        elseif (!$this->ion_auth->is_manager())
        {
            //redirect them to the home page because they must be an administrator to view this
            redirect('/', 'refresh');
        }
        else
        {
            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['isGlobalAdmin'] = $this->ion_auth->is_global_manager();

            //list the users
            $this->data['users'] = $this->ion_auth->users()->result();
            foreach ($this->data['users'] as $k => $user)
            {
                $team = $this->Teams_model->getById($user->team_id);
                if ($team != null)
                {
                    $this->data['users'][$k]->teamId = $team->id;
                }
                $this->data['users'][$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
            }
            
            //grab seasons
            $this->model('Seasons_model');
            $seasons = $this->Seasons_model->get_listAsArray(false);
            $this->data['seasons'] = $seasons;            
            
            //grab open issues
            $this->load->model('Issues_model');
            $issues = $this->Issues_model->getList();
            $this->data['issues'] = $issues;

            $this->_render_page('index', $this->data, $fromajax);
        }
    }

    //log the user in
    function login($fromajax=false)
    {
        $this->data['title'] = "Login";

        //validate form input
        $this->form_validation->set_rules('identity', 'Identity', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() == true)
        {
            //check to see if the user is logging in
            //check for "remember me"
            $remember = (bool) $this->input->post('remember');

            if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember))
            {
                //if the login is successful
                //redirect them back to the home page
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                redirect('/', 'refresh');
            }
            else
            {
                //if the login was un-successful
                //redirect them back to the login page
                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect('auth/login', 'refresh'); //use redirects instead of loading views for compatibility with MY_Controller libraries
            }
        }
        else
        {
            //the user is not logging in so display the login page
            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            if ($this->data['message'] == '' || $this->data['message'] == false)
            {
                $this->data['message'] = $this->session->userdata('AuthMessage');
                $this->session->unset_userdata('AuthMessage');
            }
            
            $this->data['identity'] = array('name' => 'identity',
                'id' => 'identity',
                'type' => 'text',
                'value' => $this->form_validation->set_value('identity'),
            );
            $this->data['password'] = array('name' => 'password',
                'id' => 'password',
                'type' => 'password',
            );

            $this->_render_page('login', $this->data, $fromajax);
        }
    }

    //log the user out
    function logout()
    {
        $this->data['title'] = "Logout";

        //log the user out
        $logout = $this->ion_auth->logout();

        //redirect them to the login page
        $this->session->set_flashdata('message', $this->ion_auth->messages());
        redirect('auth/login', 'refresh');
    }

    //change password
    function change_password($fromajax=false)
    {
        $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
        $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
        $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

        if (!$this->ion_auth->logged_in())
        {
            redirect('auth/login', 'refresh');
        }

        $user = $this->ion_auth->user()->row();

        if ($this->form_validation->run() == false)
        {
            //display the form
            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
            $this->data['old_password'] = array(
                'name' => 'old',
                'id'   => 'old',
                'type' => 'password',
            );
            $this->data['new_password'] = array(
                'name' => 'new',
                'id'   => 'new',
                'type' => 'password',
                'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
            );
            $this->data['new_password_confirm'] = array(
                'name' => 'new_confirm',
                'id'   => 'new_confirm',
                'type' => 'password',
                'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
            );
            $this->data['user_id'] = array(
                'name'  => 'user_id',
                'id'    => 'user_id',
                'type'  => 'hidden',
                'value' => $user->id,
            );

            //render
            $this->_render_page('auth/change_password', $this->data);
        }
        else
        {
            $identity = $this->session->userdata($this->config->item('identity', 'ion_auth'));

            $change = $this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'));

            if ($change)
            {
                //if the password was successfully changed
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                $this->logout();
            }
            else
            {
                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect('auth/change_password', 'refresh');
            }
        }
    }

    //forgot password
    function forgot_password($fromajax=false)
    {
        $this->form_validation->set_rules('email', $this->lang->line('forgot_password_validation_email_label'), 'required');
        if ($this->form_validation->run() == false)
        {
            //setup the input
            $this->data['email'] = array('name' => 'email',
                'id' => 'email',
            );

            if ( $this->config->item('identity', 'ion_auth') == 'teamname' ){
                $this->data['identity_label'] = $this->lang->line('forgot_password_teamname_identity_label');
            }
            else
            {
                $this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
            }

            //set any errors and display the form
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
            $this->_render_page('forgotpass', $this->data, $fromajax);
        }
        else
        {
            // get identity for that email
            $config_tables = $this->config->item('tables', 'ion_auth');
            $identity = $this->db->where('email', $this->input->post('email'))->limit('1')->get($config_tables['users'])->row();

            //run the forgotten password method to email an activation code to the user
            $forgotten = $this->ion_auth->forgotten_password($identity->{$this->config->item('identity', 'ion_auth')});

            if ($forgotten)
            {
                //if there were no errors
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                redirect("auth/login", 'refresh'); //we should display a confirmation page here instead of the login page
            }
            else
            {
                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect("auth/forgot_password", 'refresh');
            }
        }
    }

    //reset password - final step for forgotten password
    public function reset_password($code = NULL)
    {
        if (!$code)
        {
            show_404();
        }

        $user = $this->ion_auth->forgotten_password_check($code);

        if ($user)
        {
            //if the code is valid then display the password reset form

            $this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
            $this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

            if ($this->form_validation->run() == false)
            {
                //display the form

                //set the flash data error message if there is one
                $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

                $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
                $this->data['new_password'] = array(
                    'name' => 'new',
                    'id'   => 'new',
                'type' => 'password',
                    'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
                );
                $this->data['new_password_confirm'] = array(
                    'name' => 'new_confirm',
                    'id'   => 'new_confirm',
                    'type' => 'password',
                    'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
                );
                $this->data['user_id'] = array(
                    'name'  => 'user_id',
                    'id'    => 'user_id',
                    'type'  => 'hidden',
                    'value' => $user->id,
                );
                $this->data['csrf'] = $this->_get_csrf_nonce();
                $this->data['code'] = $code;

                //render
                $this->_render_page('resetpass', $this->data);
            }
            else
            {
                {
                    // finally change the password
                    $identity = $user->{$this->config->item('identity', 'ion_auth')};

                    $change = $this->ion_auth->reset_password($identity, $this->input->post('new'));

                    if ($change)
                    {
                        //if the password was successfully changed
                        $this->session->set_flashdata('message', $this->ion_auth->messages());
                        $this->logout();
                    }
                    else
                    {
                        $this->session->set_flashdata('message', $this->ion_auth->errors());
                        redirect('auth/reset_password/' . $code, 'refresh');
                    }
                }
            }
        }
        else
        {
            //if the code is invalid then send them back to the forgot password page
            $this->session->set_flashdata('message', $this->ion_auth->errors());
            redirect("auth/forgot_password", 'refresh');
        }
    }


    //activate the user
    function activate($id, $code=false)
    {
        if ($code !== false)
        {
            $activation = $this->ion_auth->activate($id, $code);
        }
        else if ($this->ion_auth->is_manager())
        {
            $activation = $this->ion_auth->activate($id);
        }

        if ($activation)
        {
            //redirect them to the auth page
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            redirect("auth", 'refresh');
        }
        else
        {
            //redirect them to the forgot password page
            $this->session->set_flashdata('message', $this->ion_auth->errors());
            redirect("auth/forgot_password", 'refresh');
        }
    }

    //deactivate the user
    function deactivate($id = NULL)
    {
        $id = $this->config->item('use_mongodb', 'ion_auth') ? (string) $id : (int) $id;

        $this->load->library('form_validation');
        $this->form_validation->set_rules('confirm', $this->lang->line('deactivate_validation_confirm_label'), 'required');
        $this->form_validation->set_rules('id', $this->lang->line('deactivate_validation_user_id_label'), 'required|alpha_numeric');

        if ($this->form_validation->run() == FALSE)
        {
            // insert csrf check
            $this->data['csrf'] = $this->_get_csrf_nonce();
            $this->data['user'] = $this->ion_auth->user($id)->row();

            $this->_render_page('deactivate', $this->data);
        }
        else
        {
            // do we really want to deactivate?
            if ($this->input->post('confirm') == 'yes')
            {
                // do we have the right userlevel?
                if ($this->ion_auth->logged_in() && $this->ion_auth->is_manager())
                {
                    $this->ion_auth->deactivate($id);
                }
            }

            //redirect them back to the auth page
            redirect('auth', 'refresh');
        }
    }

    //create a new user
    function create_user($fromajax=false)
    {
        get_instance()->load->library('form_validation');

        $this->data['title'] = "Create User";

        if ($this->ion_auth->logged_in())
        {
            redirect('auth', 'refresh');
        }

        //validate form input
        $this->form_validation->set_rules('username', $this->lang->line('create_user_validation_username_label'), 'required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email');
        $this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
        $this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');

        $displayForm = true;
        
        if ($this->form_validation->run() == true)
        {
            $username = $this->input->post('username');
            $email    = $this->input->post('email');
            $password = $this->input->post('password');

            $additional_data = array();
        }
        if ($this->form_validation->run() == true)
        {
            $displayForm = true;
            $id = $this->ion_auth->register($username, $password, $email, $additional_data);
            if ($id != FALSE)
            {         
                $displayForm = false;

                //check to see if we are creating the user
                //redirect them back to the admin page
                $this->session->set_userdata('AuthMessage', 'Validation email has been sent, please check for it.' );
                redirect("auth", 'refresh');
            }
        }
        
        if ($displayForm)
        {
            //display the create user form
            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

           
            $this->data['username'] = array(
                'name'  => 'username',
                'id'    => 'username',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('username'),
            );
            $this->data['email'] = array(
                'name'  => 'email',
                'id'    => 'email',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('email'),
            );
            $this->data['password'] = array(
                'name'  => 'password',
                'id'    => 'password',
                'type'  => 'password',
                'value' => $this->form_validation->set_value('password'),
            );
            $this->data['password_confirm'] = array(
                'name'  => 'password_confirm',
                'id'    => 'password_confirm',
                'type'  => 'password',
                'value' => $this->form_validation->set_value('password_confirm'),
            );

            $this->_render_page('createuser', $this->data, $fromajax);
        }
    }

    //edit a user
    function edit_user($id)
    {
        $this->data['title'] = "Edit User";

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }

        $user = $this->ion_auth->user($id)->row();
        $groups=$this->ion_auth->groups()->result_array();
        $currentGroups = $this->ion_auth->get_users_groups($id)->result();

        //validate form input
        $this->form_validation->set_rules('first_name', $this->lang->line('edit_user_validation_fname_label'), 'required|xss_clean');
        $this->form_validation->set_rules('last_name', $this->lang->line('edit_user_validation_lname_label'), 'required|xss_clean');
        $this->form_validation->set_rules('phone', $this->lang->line('edit_user_validation_phone_label'), 'required|xss_clean');
        $this->form_validation->set_rules('company', $this->lang->line('edit_user_validation_company_label'), 'required|xss_clean');
        $this->form_validation->set_rules('groups', $this->lang->line('edit_user_validation_groups_label'), 'xss_clean');

        if (isset($_POST) && !empty($_POST))
        {
            // do we have a valid request?
            if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id'))
            {
                show_error($this->lang->line('error_csrf'));
            }

            $data = array(
                'first_name' => $this->input->post('first_name'),
                'last_name'  => $this->input->post('last_name'),
                'company'    => $this->input->post('company'),
                'phone'      => $this->input->post('phone'),
            );

            //Update the groups user belongs to
            $groupData = $this->input->post('groups');

            if (isset($groupData) && !empty($groupData)) {

                $this->ion_auth->remove_from_group('', $id);

                foreach ($groupData as $grp) {
                    $this->ion_auth->add_to_group($grp, $id);
                }

            }

            //update the password if it was posted
            if ($this->input->post('password'))
            {
                $this->form_validation->set_rules('password', $this->lang->line('edit_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
                $this->form_validation->set_rules('password_confirm', $this->lang->line('edit_user_validation_password_confirm_label'), 'required');

                $data['password'] = $this->input->post('password');
            }

            if ($this->form_validation->run() === TRUE)
            {
                $this->ion_auth->update($user->id, $data);

                //check to see if we are creating the user
                //redirect them back to the admin page
                $this->session->set_flashdata('message', "User Saved");
                redirect("auth", 'refresh');
            }
        }

        //display the edit user form
        $this->data['csrf'] = $this->_get_csrf_nonce();

        //set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        //pass the user to the view
        $this->data['user'] = $user;
        $this->data['groups'] = $groups;
        $this->data['currentGroups'] = $currentGroups;

        $this->data['first_name'] = array(
            'name'  => 'first_name',
            'id'    => 'first_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('first_name', $user->first_name),
        );
        $this->data['last_name'] = array(
            'name'  => 'last_name',
            'id'    => 'last_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('last_name', $user->last_name),
        );
        $this->data['company'] = array(
            'name'  => 'company',
            'id'    => 'company',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('company', $user->company),
        );
        $this->data['phone'] = array(
            'name'  => 'phone',
            'id'    => 'phone',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('phone', $user->phone),
        );
        $this->data['password'] = array(
            'name' => 'password',
            'id'   => 'password',
            'type' => 'password'
        );
        $this->data['password_confirm'] = array(
            'name' => 'password_confirm',
            'id'   => 'password_confirm',
            'type' => 'password'
        );

        $this->_render_page('auth/edit_user', $this->data);
    }
    
    function seasons()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_manager())
        {
            redirect('auth', 'refresh');
        }
        
        $this->model('Seasons_model');
        $seasons = $this->Seasons_model->get_listAsArray(false);
        
        $this->data = array();
        $this->data['seasons'] = $seasons;
        $this->_render_page('auth/seaons', $this->data);

    }
    
    function season_activate($id)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_global_manager())
        {
            redirect('auth', 'refresh');
        }

        $this->model('Seasons_model');
        $this->Seasons_model->changeActiveFlag($id, true);
        redirect('auth', 'refresh');
    }
    function season_deactivate($id)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_global_manager())
        {
            redirect('auth', 'refresh');
        }

        $this->model('Seasons_model');
        $this->Seasons_model->changeActiveFlag($id, false);
        redirect('auth', 'refresh');
    }
    
    function create_issue()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_manager())
        {
            redirect('auth', 'refresh');
        }
        $userId = $this->ion_auth->user()->row()->id;
        
        get_instance()->load->library('form_validation');
        $this->model('Issues_model');
        
        $this->form_validation->set_rules('name', 'Title', 'required|xss_clean');
        $this->form_validation->set_rules('desc', 'Description', 'required|xss_clean');
        $flashmsg = '';
        if ($this->form_validation->run() == true)
        {
            $name = $this->input->post('name');
            $desc = $this->input->post('desc');
            
            $id = $this->Issues_model->add($name, $desc);
            if ($id > 0)
            {
                redirect('auth', 'refresh');            
            }
            $flashmsg = 'Error creating new issue.';
        }   
        
        $this->data = array();        
        $this->data['message'] = (validation_errors() ? validation_errors() : $flashmsg);

        $this->data['name'] = array(
             'name'  => 'name',
             'id'    => 'name',
             'type'  => 'text',
             'maxlength' => '100',
             'value' => $this->form_validation->set_value('name' ),
         );
        $this->data['desc'] = array(
             'name'  => 'desc',
             'id'    => 'desc',
             'type'  => 'text',
             'value' => $this->form_validation->set_value('desc' ),
         );

        
        $this->_render_page('create_issue', $this->data, false);        
    }
    
    function issue($id)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_manager())
        {
            redirect('auth', 'refresh');
        }
        $userId = $this->ion_auth->user()->row()->id;
        
        get_instance()->load->library('form_validation');
        $this->model('Issues_model');
            
        $issue = $this->Issues_model->get($id, $userId);
        $hasVoted = true;
        if ($issue == null)
        {
            //user hasn't voted for this yet, get the general issue.
            $hasVoted = false;
            $issue = $this->Issues_model->get($id);
            if ($issue == null)
                redirect('auth', 'refresh');            
        }
        
        $this->form_validation->set_rules('votecode', 'Vote', 'required|xss_clean');
        if ($this->form_validation->run() == true)
        {
            $votecode = $this->input->post('votecode');
            if ($votecode == 1)
            {
                $r = $this->Issues_model->addVote($id, $userId, $votecode-1);
                if ($r == true)
                {
                    redirect('auth', 'refresh');   
                }
            }
        }
        
        $this->data = array();
        $this->data['issue'] = $issue;
        $this->data['hasVoted'] = $hasVoted;
        
        $this->data['message'] = (validation_errors() ? validation_errors() : $this->session->flashdata('message'));

        
        $this->_render_page('issue', $this->data, false);

    }
    
    function season_edit($id)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_global_manager())
        {
            redirect('auth', 'refresh');
        }
        
        $this->data['isGlobalAdmin'] = $this->ion_auth->is_global_manager();

        get_instance()->load->library('form_validation');
        $this->model('Seasons_model');

        $this->data['title'] = "Create Season";

        $season = $this->Seasons_model->get($id);
        
        //validate form input
        $this->form_validation->set_rules('name', 'Name', 'required|xss_clean');
        $this->form_validation->set_rules('tag', 'Tag', 'required|xss_clean');
        $this->form_validation->set_rules('start', 'Open Registration', 'required|xss_clean');
        $this->form_validation->set_rules('end', 'Close Registration', 'required|xss_clean');

        if (isset($_POST) && !empty($_POST))
        {
           
            if ($this->form_validation->run() == true)
            {
                $name = $this->input->post('name');
                $tag = $this->input->post('tag');
                $start    = $this->input->post('start');
                $end = $this->input->post('end');
                $active = $this->input->post('active');
                $additional_data = array();

                $this->Seasons_model->edit($id, $name,$tag,$start,$end);
                redirect('auth', 'refresh');

            }
        }
                       
        {
            //display the edit season form
            $this->data['id'] = array(
                'id' => $season->id             
            );
            
            $this->data['csrf'] = $this->_get_csrf_nonce();

            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : $this->session->flashdata('message'));

           
            $this->data['name'] = array(
                'name'  => 'name',
                'id'    => 'name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('first_name', $season->name),
            );
            $this->data['tag'] = array(
                'name'  => 'tag',
                'id'    => 'tag',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('tag', $season->tag),
            );
            $this->data['start'] = array(
                'name'  => 'start',
                'id'    => 'start',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('start', $season->start),
            );
            $this->data['end'] = array(
                'name'  => 'end',
                'id'    => 'end',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('end', $season->end),
            );
            
            $weeks = $this->Seasons_model->getWeeksForSeason($id);
            $this->data['weeks'] = $weeks;

            $this->_render_page('editseason', $this->data, false);
        }
    }
    
    function scheduler($teams){
    // Check for even number or add a bye
        if (count($teams)%2 != 0){
            array_push($teams,"bye");
        }
    // Splitting the teams array into two arrays
        $away = array_splice($teams,(count($teams)/2));
        $home = $teams;
    // The actual scheduling based on round robin
        for ($i=0; $i < count($home)+count($away)-1; $i++){
            for ($j=0; $j<count($home); $j++){
                $round[$i][$j]["Home"]=$home[$j];
                $round[$i][$j]["Away"]=$away[$j];
            }
    // Check if total numbers of teams is > 2 otherwise shifting the arrays is not neccesary
            if(count($home)+count($away)-1 > 2){
                array_unshift($away,array_shift(array_splice($home,1,1)));
                array_push($home,array_pop($away));
            }
        }
        return $round;
    }     
    
    function season_seed($seasonId)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }

        $this->model('Seasons_model');

        $season = $this->Seasons_model->get($seasonId);
        $weeks = $this->Seasons_model->getWeeksForSeason($seasonId);
        
        $dWeeks = Array();
        $lastWeek = null;
        foreach($weeks AS $week)
        {
//            $dWeeks[] = $week;
            $dWeeks[] = $week;
            $lastWeek = $week;
        }
        $dWeeks[] = $lastWeek;
        $dWeeks[] = $lastWeek;
        
        $weeks = $dWeeks;
        
        $gameId = 1;
        //run throgh each group of the season and seed just that.
        $groups = $this->Seasons_model->getGroupsForSeason($seasonId);
        foreach($groups AS $group)
        {
            $registeredTeams = $this->Seasons_model->getTeamsInGroup($seasonId,$group->id);
        
            $schedule = $this->scheduler($registeredTeams);

            foreach($schedule AS $round => $games )
            {
                $localWeek = $weeks[$round];

                $date = new DateTime($localWeek['end']);
                $date->sub(new DateInterval('P1D'));
                
                $date_day = $date->format('Y-m-d');
                $match_day1 = $date_day;
                $match_day2 = $date_day;

                
                foreach($games AS $play)
                {            
                    $team1 = $play["Home"];
                    $team2 = $play["Away"];

                    if ($team1 == 'bye' || $team2 == 'bye') continue;

                    //figure out the correct server based on team choices.
                    $server1 = "ERR";
                    $server2 = "ERR";

                    if ($team1->region == "EU" && $team2->region == "EU")
                    {
                        $server1 = "EU";
                        $server2 = "EU";
                        
                        $match_day1 .= ' 14:00:00';
                        $match_day2 .= ' 16:00:00';
                    }
                    else if (($team1->region == "USE" || $team1->region =="USW") &&
                        ($team2->region == "USE" || $team2->region =="USW"))
                    {
                        //US vs US
                        $server1 = "USE";
                        $server2 = "USW";
                        
                        $match_day1 .= ' 19:00:00';
                        $match_day1 .= ' 21:00:00';
                        
                    }
                    else if ($team1->region == "USE" && $team2->region == "EU")
                    {
                        //USE vs EU
                        $server1 = "USE";
                        $server2 = "EU";    

                        $match_day1 .= ' 16:00:00';
                        $match_day2 .= ' 18:00:00';
                        
                    }
                    else if ($team1->region == "EU" && $team2->region == "USE")
                    {
                        //EU vs USE
                        $server1 = "EU";
                        $server2 = "USE";                    
                        
                        $match_day1 .= ' 16:00:00';
                        $match_day2 .= ' 18:00:00';
                        
                    }
                    else if ($team1->region == "SEA" && $team2->region == "SEA")
                    {
                        //SEA vs SEA
                        $server1 = "SEA";
                        $server2 = "SEA";  
                        
                        $match_day1 .= ' 05:00:00';
                        $match_day2 .= ' 07:00:00';
                        
                    }                
                    else if ( ($team1->region == "SEA" && $team2->region == "EU") ||
                            ($team1->region == "EU" && $team2->region == "SEA") )
                    {
                        //EU vs SEA
                        $server1 = "CIS";
                        $server2 = "CIS";   
                        
                        $match_day1 .= ' 13:00:00';
                        $match_day2 .= ' 15:00:00';
                        
                    }
                    else if ( ($team1->region == "USW" && $team2->region == "EU") ||
                            ($team1->region == "EU" && $team2->region == "USW") )
                    {
                        //EU vs USW
                        $server1 = "USE";
                        $server2 = "USE";       
                        
                        $match_day1 .= ' 19:00:00';
                        $match_day2 .= ' 21:00:00';
                        
                    }
                    else if ( ($team1->region == "USW" && $team2->region == "EU") ||
                            ($team1->region == "EU" && $team2->region == "USW") )
                    {
                        //EU vs USW
                        $server1 = "USE";
                        $server2 = "USE"; 
                        
                        $match_day1 .= ' 19:00:00';
                        $match_day2 .= ' 21:00:00';
                        
                    }
                    else if ( ($team1->region == "USE" || $team1->region == "USW") &&
                            $team2->region == "SEA")
                    {
                        //SEA vs US
                        $server1 = "USW";
                        $server2 = "USW";    
                        
                        $match_day1 .= ' 21:00:00';
                        $match_day2 .= ' 23:00:00';
                        
                    }
                    else if ( ($team2->region == "USE" || $team2->region == "USW") &&
                            $team1->region == "SEA")
                    {
                        //SEA vs US
                        $server1 = "USW";
                        $server2 = "USW";                                        

                        $match_day1 .= ' 21:00:00';
                        $match_day2 .= ' 23:00:00';
                        
                    }
                    
                    $G1 = 'G'. $gameId++;
                    $G2 = 'G'. $gameId++;
                    
                    //echo($group->name . ' ' . $localWeek['id'] .' ' . $team1->name . ' vs ' . $team2->name . ' ' . $match_day1 . ' ' . $match_day2 . '<br/>');
                    
                    $this->Seasons_model->newMatch($seasonId, $localWeek['id'], $team1->id, $team2->id, $server1, $match_day1, $G1);
                    $this->Seasons_model->newMatch($seasonId, $localWeek['id'], $team2->id, $team1->id, $server2, $match_day2, $G2);                
                }
            }           
        }

        exit();
        //redirect('auth/season_edit/'.$seasonId,'refresh');
              
    }

    function create_season_week($seasonId)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_global_manager())
        {
            redirect('auth', 'refresh');
        }
        
        get_instance()->load->library('form_validation');
        $this->model('Seasons_model');

        $this->data['title'] = "Create Weekly Bracket";
        $this->data['seasonId'] = $seasonId;
        
        //validate form input
        $this->form_validation->set_rules('tag', 'Tag', 'required|xss_clean');
        $this->form_validation->set_rules('start', 'Start of Week', 'required|xss_clean');
        $this->form_validation->set_rules('end', 'End of Week', 'required|xss_clean');
        
        if ($this->form_validation->run() == true)
        {
            $tag = $this->input->post('tag');
            $start    = $this->input->post('start');
            $end = $this->input->post('end');
            $additional_data = array();
            
            $this->Seasons_model->addWeek($seasonId,$tag,$start,$end);
            redirect('auth/season_edit/' . $seasonId, 'refresh');           
        }        
      
        {
            $season = $this->Seasons_model->get($seasonId);
            $this->data['season'] = $season;

            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : $this->session->flashdata('message'));

           
            $this->data['tag'] = array(
                'name'  => 'tag',
                'id'    => 'tag',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('tag'),
            );
            $this->data['start'] = array(
                'name'  => 'start',
                'id'    => 'start',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('start'),
            );
            $this->data['end'] = array(
                'name'  => 'end',
                'id'    => 'end',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('end'),
            );

            $this->_render_page('createweek', $this->data, false);
        }
        
        
    }
    
    function create_season()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }
        
        get_instance()->load->library('form_validation');
        $this->model('Seasons_model');

        $this->data['title'] = "Create Season";
        
        //validate form input
        $this->form_validation->set_rules('name', 'Name', 'required|xss_clean');
        $this->form_validation->set_rules('tag', 'Tag', 'required|xss_clean');
        $this->form_validation->set_rules('start', 'Open Registration', 'required|xss_clean');
        $this->form_validation->set_rules('end', 'Close Registration', 'required|xss_clean');

        
        if ($this->form_validation->run() == true)
        {
            $name = $this->input->post('name');
            $tag = $this->input->post('tag');
            $start    = $this->input->post('start');
            $end = $this->input->post('end');
            $active = $this->input->post('active');
            $additional_data = array();
            
            $this->Seasons_model->add($name,$tag,$start,$end);
            redirect('auth', 'refresh');
           
        }
        
        {
            //display the create user form
            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : $this->session->flashdata('message'));

           
            $this->data['name'] = array(
                'name'  => 'name',
                'id'    => 'name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('name'),
            );
            $this->data['tag'] = array(
                'name'  => 'tag',
                'id'    => 'tag',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('tag'),
            );
            $this->data['start'] = array(
                'name'  => 'start',
                'id'    => 'start',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('start'),
            );
            $this->data['end'] = array(
                'name'  => 'end',
                'id'    => 'end',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('end'),
            );

            $this->_render_page('createseason', $this->data, false);
        }
    }

    // create a new group
    function create_group()
    {
        $this->data['title'] = $this->lang->line('create_group_title');

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }

        //validate form input
        $this->form_validation->set_rules('group_name', $this->lang->line('create_group_validation_name_label'), 'required|alpha_dash|xss_clean');
        $this->form_validation->set_rules('description', $this->lang->line('create_group_validation_desc_label'), 'xss_clean');

        if ($this->form_validation->run() == TRUE)
        {
            $new_group_id = $this->ion_auth->create_group($this->input->post('group_name'), $this->input->post('description'));
            if($new_group_id)
            {
                // check to see if we are creating the group
                // redirect them back to the admin page
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                redirect("auth", 'refresh');
            }
        }
        else
        {
            //display the create group form
            //set the flash data error message if there is one
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            $this->data['group_name'] = array(
                'name'  => 'group_name',
                'id'    => 'group_name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('group_name'),
            );
            $this->data['description'] = array(
                'name'  => 'description',
                'id'    => 'description',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('description'),
            );

            $this->_render_page('auth/create_group', $this->data);
        }
    }

    //edit a group
    function edit_group($id)
    {
        // bail if no group id given
        if(!$id || empty($id))
        {
            redirect('auth', 'refresh');
        }

        $this->data['title'] = $this->lang->line('edit_group_title');

        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
        {
            redirect('auth', 'refresh');
        }

        $group = $this->ion_auth->group($id)->row();

        //validate form input
        $this->form_validation->set_rules('group_name', $this->lang->line('edit_group_validation_name_label'), 'required|alpha_dash|xss_clean');
        $this->form_validation->set_rules('group_description', $this->lang->line('edit_group_validation_desc_label'), 'xss_clean');

        if (isset($_POST) && !empty($_POST))
        {
            if ($this->form_validation->run() === TRUE)
            {
                $group_update = $this->ion_auth->update_group($id, $_POST['group_name'], $_POST['group_description']);

                if($group_update)
                {
                    $this->session->set_flashdata('message', $this->lang->line('edit_group_saved'));
                }
                else
                {
                    $this->session->set_flashdata('message', $this->ion_auth->errors());
                }
                redirect("auth", 'refresh');
            }
        }

        //set the flash data error message if there is one
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        //pass the user to the view
        $this->data['group'] = $group;

        $this->data['group_name'] = array(
            'name'  => 'group_name',
            'id'    => 'group_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('group_name', $group->name),
        );
        $this->data['group_description'] = array(
            'name'  => 'group_description',
            'id'    => 'group_description',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('group_description', $group->description),
        );

        $this->_render_page('auth/edit_group', $this->data);
    }


    function _get_csrf_nonce()
    {
        $this->load->helper('string');
        $key   = random_string('alnum', 8);
        $value = random_string('alnum', 20);
        $this->session->set_flashdata('csrfkey', $key);
        $this->session->set_flashdata('csrfvalue', $value);

        return array($key => $value);
    }

    function _valid_csrf_nonce()
    {
        if ($this->input->post($this->session->flashdata('csrfkey')) !== FALSE &&
            $this->input->post($this->session->flashdata('csrfkey')) == $this->session->flashdata('csrfvalue'))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    function _render_page($view, $data=null, $render=false)
    {
        $data = (empty($data)) ? $this->data : $data;

        if ( ! empty($data['title']))
        {
            $this->twiggy->title($data['title']);
        }
        $this->twiggy->set('data', $data);

        if ( ! $render)
        {
            $this->twiggy->template($view)->display();
        }
        else
        {
            $this->twiggy->layout('dialog')->template($view)->display();
        }
    }

}
