<?php
class Members extends Trongate {

    private $default_limit = 20;
    private $per_page_options = array(10, 20, 50, 100);

    function logout() {
        $this->module('trongate_tokens');
        $this->trongate_tokens->_destroy();
        redirect(BASE_URL);
    } 

    function _make_sure_allowed() {
        $this->module('trongate_tokens');
        $token = $this->trongate_tokens->_attempt_get_valid_token(2);

        if ($token == false) {
            redirect('members/login');
        } else {
            //user has valid token
            return $token;
        }
    }

    function submit_login() {
        
        $submit = post('submit');

        if ($submit == 'Submit') {
            $this->validation_helper->set_rules('username', 'username', 'required|callback_username_check');
            $this->validation_helper->set_rules('password', 'password', 'required|min_length[5]');

            $result = $this->validation_helper->run(); //true or false

            if ($result == false) {
                $this->login();
            } else {
                $this->_in_you_go();
            }

        }

    }

    function _in_you_go() {
        //log the user in 
        $username = post('username');
        $remember = post('remember');
        $member_obj = $this->model->get_one_where('username', $username, 'members');
        $trongate_user_id = $member_obj->trongate_user_id;

        if ($remember == 1) {
            //log this user in for one month!
            $nowtime = time();
            $one_month = 86400*30;
            $data['expiry_date'] = $nowtime+$one_month;
            $data['set_cookie'] = true;
        }

        $data['user_id'] = $trongate_user_id;
        $this->module('trongate_tokens');
        $this->trongate_tokens->_generate_token($data);

        redirect('crypto_tips');
    }

    function login() {
        $data['view_file'] = 'login';
        $this->template('public', $data);
    }   

    function create() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $update_id = segment(3);
        $submit = post('submit');

        if (($submit == '') && (is_numeric($update_id))) {
            $data = $this->_get_data_from_db($update_id);
        } else {
            $data = $this->_get_data_from_post();
        }

        if (is_numeric($update_id)) {
            $data['headline'] = 'Update Member Record';
            $data['cancel_url'] = BASE_URL.'members/show/'.$update_id;
        } else {
            $data['headline'] = 'Create New Member Record';
            $data['cancel_url'] = BASE_URL.'members/manage';
        }

        $data['form_location'] = BASE_URL.'members/submit/'.$update_id;
        $data['view_file'] = 'create';
        $this->template('admin', $data);
    }

    function manage() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (segment(4) !== '') {
            $data['headline'] = 'Search Results';
            $searchphrase = trim($_GET['searchphrase']);
            $params['username'] = '%'.$searchphrase.'%';
            $params['first_name'] = '%'.$searchphrase.'%';
            $params['last_name'] = '%'.$searchphrase.'%';
            $params['email_address'] = '%'.$searchphrase.'%';
            $sql = 'select * from members
            WHERE username LIKE :username
            OR first_name LIKE :first_name
            OR last_name LIKE :last_name
            OR email_address LIKE :email_address
            ORDER BY id';
            $all_rows = $this->model->query_bind($sql, $params, 'object');
        } else {
            $data['headline'] = 'Manage Members';
            $all_rows = $this->model->get('id');
        }

        $pagination_data['total_rows'] = count($all_rows);
        $pagination_data['page_num_segment'] = 3;
        $pagination_data['limit'] = $this->_get_limit();
        $pagination_data['pagination_root'] = 'members/manage';
        $pagination_data['record_name_plural'] = 'members';
        $pagination_data['include_showing_statement'] = true;
        $data['pagination_data'] = $pagination_data;

        $data['rows'] = $this->_reduce_rows($all_rows);
        $data['selected_per_page'] = $this->_get_selected_per_page();
        $data['per_page_options'] = $this->per_page_options;
        $data['view_module'] = 'members';
        $data['view_file'] = 'manage';
        $this->template('admin', $data);
    }

    function show() {
        $this->module('trongate_security');
        $token = $this->trongate_security->_make_sure_allowed();
        $update_id = segment(3);

        if ((!is_numeric($update_id)) && ($update_id != '')) {
            redirect('members/manage');
        }

        $data = $this->_get_data_from_db($update_id);
        $data['token'] = $token;

        if ($data == false) {
            redirect('members/manage');
        } else {
            $data['update_id'] = $update_id;
            $data['headline'] = 'Member Information';
            $data['view_file'] = 'show';
            $this->template('admin', $data);
        }
    }
    
    function _reduce_rows($all_rows) {
        $rows = [];
        $start_index = $this->_get_offset();
        $limit = $this->_get_limit();
        $end_index = $start_index + $limit;

        $count = -1;
        foreach ($all_rows as $row) {
            $count++;
            if (($count>=$start_index) && ($count<$end_index)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    function submit() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit', true);

        if ($submit == 'Submit') {

            $this->validation_helper->set_rules('username', 'Username', 'required|min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('first_name', 'First Name', 'required|min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('last_name', 'Last Name', 'required|min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('email_address', 'Email Address', 'required|min_length[7]|max_length[255]|valid_email_address|valid_email');

            $result = $this->validation_helper->run();

            if ($result == true) {

                $update_id = segment(3);
                $data = $this->_get_data_from_post();

                if (is_numeric($update_id)) {
                    //update an existing record
                    $this->model->update($update_id, $data, 'members');
                    $flash_msg = 'The record was successfully updated';
                } else {
                    //insert the new record
                    $data['password'] = '';
                    $data['trongate_user_id'] = $this->_create_trongate_user();
                    $update_id = $this->model->insert($data, 'members');
                    $flash_msg = 'The record was successfully created';
                }

                set_flashdata($flash_msg);
                redirect('members/show/'.$update_id);

            } else {
                //form submission error
                $this->create();
            }

        }

    }

    function _create_trongate_user() {
        $user_data['code'] = make_rand_str(32);
        $user_data['user_level_id'] = 2; //member 
        $trongate_user_id = $this->model->insert($user_data, 'trongate_users');
        return $trongate_user_id;
    }

    function submit_delete() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit');
        $params['update_id'] = segment(3);

        if (($submit == 'Yes - Delete Now') && (is_numeric($params['update_id']))) {
            //delete all of the comments associated with this record
            $sql = 'delete from trongate_comments where target_table = :module and update_id = :update_id';
            $params['module'] = 'members';
            $this->model->query_bind($sql, $params);

            //delete the record
            $this->model->delete($params['update_id'], 'members');

            //set the flashdata
            $flash_msg = 'The record was successfully deleted';
            set_flashdata($flash_msg);

            //redirect to the manage page
            redirect('members/manage');
        }
    }

    function _get_limit() {
        if (isset($_SESSION['selected_per_page'])) {
            $limit = $this->per_page_options[$_SESSION['selected_per_page']];
        } else {
            $limit = $this->default_limit;
        }

        return $limit;
    }

    function _get_offset() {
        $page_num = segment(3);

        if (!is_numeric($page_num)) {
            $page_num = 0;
        }

        if ($page_num>1) {
            $offset = ($page_num-1)*$this->_get_limit();
        } else {
            $offset = 0;
        }

        return $offset;
    }

    function _get_selected_per_page() {
        if (!isset($_SESSION['selected_per_page'])) {
            $selected_per_page = $this->per_page_options[1];
        } else {
            $selected_per_page = $_SESSION['selected_per_page'];
        }

        return $selected_per_page;
    }

    function set_per_page($selected_index) {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (!is_numeric($selected_index)) {
            $selected_index = $this->per_page_options[1];
        }

        $_SESSION['selected_per_page'] = $selected_index;
        redirect('members/manage');
    }

    function _get_data_from_db($update_id) {
        $record_obj = $this->model->get_where($update_id, 'members');

        if ($record_obj == false) {
            $this->template('error_404');
            die();
        } else {
            $data = (array) $record_obj;
            return $data;        
        }
    }

    function _get_data_from_post() {
        $data['username'] = post('username', true);
        $data['first_name'] = post('first_name', true);
        $data['last_name'] = post('last_name', true);
        $data['email_address'] = post('email_address', true);        
        return $data;
    }

    function _hash_string($str) {
        $hashed_string = password_hash($str, PASSWORD_BCRYPT, array(
            'cost' => 11
        ));
        return $hashed_string;
    }

    function _verify_hash($plain_text_str, $hashed_string) {
        $result = password_verify($plain_text_str, $hashed_string);
        return $result; //TRUE or FALSE
    }

    function username_check($username) {
        //create a default error message
        $error_msg = 'You did not enter a correct username and/or password.';

        $member_obj = $this->model->get_one_where('username', $username, 'members');

        if ($member_obj == false) {
            return $error_msg;
        } else {

            //check to see if the password was valid 
            $submitted_password = post('password');
            $stored_password = $member_obj->password; //hashed string 
            $valid_pass = $this->_verify_hash($submitted_password, $stored_password);

            if ($valid_pass == true) {
                return true; //validation success
            } else {
                return $error_msg;
            }

        }
    }











}