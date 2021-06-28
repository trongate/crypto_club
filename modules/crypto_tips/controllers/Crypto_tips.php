<?php
class Crypto_tips extends Trongate {

    private $default_limit = 20;
    private $per_page_options = array(10, 20, 50, 100); 

    function index() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed('members area');

        $data['tips'] = $this->model->get('id desc');
        $data['view_file'] = 'tips_home';
        $this->template('members_area', $data);
    }  

    function display($update_id) {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed('members area');
        
        settype($update_id, 'integer');
        
        $data['tip'] = $this->model->get_where($update_id);

        if ($data['tip'] == false) {
            redirect('crypto_tips');
        }

        $data['view_file'] = 'display_tip';
        $this->template('members_area', $data);
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
            $data['headline'] = 'Update Crypto Tip Record';
            $data['cancel_url'] = BASE_URL.'crypto_tips/show/'.$update_id;
        } else {
            $data['headline'] = 'Create New Crypto Tip Record';
            $data['cancel_url'] = BASE_URL.'crypto_tips/manage';
        }

        $data['form_location'] = BASE_URL.'crypto_tips/submit/'.$update_id;
        $data['view_file'] = 'create';
        $this->template('admin', $data);
    }

    function manage() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (segment(4) !== '') {
            $data['headline'] = 'Search Results';
            $searchphrase = trim($_GET['searchphrase']);
            $params['article_headline'] = '%'.$searchphrase.'%';
            $sql = 'select * from crypto_tips
            WHERE article_headline LIKE :article_headline
            ORDER BY id desc';
            $all_rows = $this->model->query_bind($sql, $params, 'object');
        } else {
            $data['headline'] = 'Manage Crypto Tips';
            $all_rows = $this->model->get('id desc');
        }

        $pagination_data['total_rows'] = count($all_rows);
        $pagination_data['page_num_segment'] = 3;
        $pagination_data['limit'] = $this->_get_limit();
        $pagination_data['pagination_root'] = 'crypto_tips/manage';
        $pagination_data['record_name_plural'] = 'crypto tips';
        $pagination_data['include_showing_statement'] = true;
        $data['pagination_data'] = $pagination_data;

        $data['rows'] = $this->_reduce_rows($all_rows);
        $data['selected_per_page'] = $this->_get_selected_per_page();
        $data['per_page_options'] = $this->per_page_options;
        $data['view_module'] = 'crypto_tips';
        $data['view_file'] = 'manage';
        $this->template('admin', $data);
    }

    function show() {
        $this->module('trongate_security');
        $token = $this->trongate_security->_make_sure_allowed();
        $update_id = segment(3);

        if ((!is_numeric($update_id)) && ($update_id != '')) {
            redirect('crypto_tips/manage');
        }

        $data = $this->_get_data_from_db($update_id);
        $data['token'] = $token;

        if ($data == false) {
            redirect('crypto_tips/manage');
        } else {
            $data['update_id'] = $update_id;
            $data['headline'] = 'Crypto Tip Information';
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

            $this->validation_helper->set_rules('article_headline', 'Article Headline', 'required|min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('article_body', 'Article Body', 'required|min_length[2]');

            $result = $this->validation_helper->run();

            if ($result == true) {

                $update_id = segment(3);
                $data = $this->_get_data_from_post();

                if (is_numeric($update_id)) {
                    //update an existing record
                    $this->model->update($update_id, $data, 'crypto_tips');
                    $flash_msg = 'The record was successfully updated';
                } else {
                    //insert the new record
                    $update_id = $this->model->insert($data, 'crypto_tips');
                    $flash_msg = 'The record was successfully created';
                }

                set_flashdata($flash_msg);
                redirect('crypto_tips/show/'.$update_id);

            } else {
                //form submission error
                $this->create();
            }

        }

    }

    function submit_delete() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit');
        $params['update_id'] = segment(3);

        if (($submit == 'Yes - Delete Now') && (is_numeric($params['update_id']))) {
            //delete all of the comments associated with this record
            $sql = 'delete from trongate_comments where target_table = :module and update_id = :update_id';
            $params['module'] = 'crypto_tips';
            $this->model->query_bind($sql, $params);

            //delete the record
            $this->model->delete($params['update_id'], 'crypto_tips');

            //set the flashdata
            $flash_msg = 'The record was successfully deleted';
            set_flashdata($flash_msg);

            //redirect to the manage page
            redirect('crypto_tips/manage');
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
        redirect('crypto_tips/manage');
    }

    function _get_data_from_db($update_id) {
        $record_obj = $this->model->get_where($update_id, 'crypto_tips');

        if ($record_obj == false) {
            $this->template('error_404');
            die();
        } else {
            $data = (array) $record_obj;
            return $data;        
        }
    }

    function _get_data_from_post() {
        $data['article_headline'] = post('article_headline', true);
        $data['article_body'] = post('article_body', true);        
        return $data;
    }

}