<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Gallery extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $this->load->helper('file');
        $this->load->library('unzip');
        define('LANG', $this->Csz_admin_model->getLang());
        $this->lang->load('admin', LANG);
        $this->lang->load('plugin/gallery', LANG);
        $this->template->set_template('admin');
        $this->load->model('plugin/Gallery_model');
        $this->_init();
        admin_helper::plugin_not_active($this->uri->segment(3));
    }

    public function _init() {
        $row = $this->Csz_admin_model->load_config();
        $pageURL = $this->Csz_admin_model->getCurPages();
        $this->template->set('core_css', $this->Csz_admin_model->coreCss());
        $this->template->set('core_js', $this->Csz_admin_model->coreJs());
        $this->template->set('title', 'Backend System | ' . $row->site_name);
        $this->template->set('meta_tags', $this->Csz_admin_model->coreMetatags('Backend System for CSZ Content Management'));
        $this->template->set('cur_page', $pageURL);
    }

    public function index() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        $this->csz_referrer->setIndex('gallery'); /* Set index page when redirect after save */
        $search_arr = ' 1=1 ';
        if ($this->input->get('search') || $this->input->get('lang')) {
            if ($this->input->get('search')) {
                $search_arr.= " AND album_name LIKE '%" . $this->input->get('search', TRUE) . "%' OR short_desc LIKE '%" . $this->input->get('search', TRUE) . "%'";
            }
            if ($this->input->get('lang')) {
                $search_arr.= " AND lang_iso = '" . $this->input->get('lang', TRUE) . "'";
            }
        }
        $this->load->helper('form');
        $this->load->library('pagination');
        // Pages variable
        $result_per_page = 20;
        $total_row = $this->Csz_model->countData('gallery_db', $search_arr);
        $num_link = 10;
        $base_url = BASE_URL . '/admin/plugin/gallery/';

        // Pageination config
        $this->Csz_admin_model->pageSetting($base_url, $total_row, $result_per_page, $num_link, 4);
        ($this->uri->segment(4)) ? $pagination = $this->uri->segment(4) : $pagination = 0;

        //Get users from database
        $this->template->setSub('gallery', $this->Csz_admin_model->getIndexData('gallery_db', $result_per_page, $pagination, 'timestamp_create', 'desc', $search_arr));
        $this->template->setSub('total_row', $total_row);
        $this->template->setSub('lang', $this->Csz_model->loadAllLang());

        //Load the view
        $this->template->loadSub('admin/plugin/gallery_index');
    }

    public function add() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        //Load the form helper
        $this->load->helper('form');
        $this->template->setSub('lang', $this->Csz_model->loadAllLang());
        //Load the view
        $this->template->loadSub('admin/plugin/gallery_add');
    }

    public function addSave() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::chkVisitor($this->session->userdata('user_admin_id'));
        //Load the form validation library
        $this->load->library('form_validation');
        //Set validation rules
        $this->form_validation->set_rules('album_name', 'Album Name', 'required');
        $this->form_validation->set_rules('short_desc', 'Short Description', 'required');
        if ($this->form_validation->run() == FALSE) {
            //Validation failed
            $this->add();
        } else {
            //Validation passed
            //Add the user
            $this->Gallery_model->insert();
            redirect($this->csz_referrer->getIndex('gallery'), 'refresh');
        }
    }

    public function edit() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        //Load the form helper
        $this->load->helper('form');
        $this->csz_referrer->setIndex('gallery_edit'); /* Set index page when redirect after save */
        if ($this->uri->segment(5)) {
            $this->template->setSub('album', $this->Csz_model->getValue('*', 'gallery_db', 'gallery_db_id', $this->uri->segment(5), 1));
            $this->template->setSub('lang', $this->Csz_model->loadAllLang());
            $this->load->library('pagination');
            
            $search_arr = "gallery_db_id = '".$this->uri->segment(5)."'";
            // Pages variable
            $result_per_page = 10;
            $total_row = $this->Csz_model->countData('gallery_picture', $search_arr);
            $num_link = 10;
            $base_url = BASE_URL . '/admin/plugin/gallery/edit/'.$this->uri->segment(5).'/';

            // Pageination config
            $this->Csz_admin_model->pageSetting($base_url, $total_row, $result_per_page, $num_link, 6);
            ($this->uri->segment(6)) ? $pagination = ($this->uri->segment(6)) : $pagination = 0;

            $this->template->setSub('showfile', $this->Csz_admin_model->getIndexData('gallery_picture', $result_per_page, $pagination, 'timestamp_create', 'desc', $search_arr));
            $this->template->setSub('total_row', $total_row);
            //Load the view
            $this->template->loadSub('admin/plugin/gallery_edit');
        } else {
            redirect($this->csz_referrer->getIndex('gallery'), 'refresh');
        }
    }

    public function editSave() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::chkVisitor($this->session->userdata('user_admin_id'));
        if ($this->uri->segment(5)) {
            //Load the form validation library
            $this->load->library('form_validation');
            //Set validation rules
            $this->form_validation->set_rules('album_name', 'Album Name', 'required');
            $this->form_validation->set_rules('short_desc', 'Short Description', 'required');
            if ($this->form_validation->run() == FALSE) {
                //Validation failed
                $this->edit();
            } else {
                //Validation passed
                //Add the user
                $this->Gallery_model->update($this->uri->segment(5));
                redirect($this->csz_referrer->getIndex('gallery'), 'refresh');
            }
        } else {
            redirect($this->csz_referrer->getIndex('gallery'), 'refresh');
        }
    }

    public function htmlUpload() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::chkVisitor($this->session->userdata('user_admin_id'));
        if ($this->uri->segment(5)) {
            $path = FCPATH . "/photo/plugin/gallery/";
            $files = $_FILES;
            $cpt = count($_FILES['files']['name']);
            for ($i = 0; $i < $cpt; $i++) {
                if ($files['files']['name'][$i]) {
                    $file_id = time() . "_" . rand(1111, 9999);
                    $photo_name = $files['files']['name'][$i];
                    $photo = $files['files']['tmp_name'][$i];
                    $file_id1 = $this->Csz_admin_model->file_upload($photo, $photo_name, '', $path, $file_id, '');
                    if ($file_id1) {
                        $this->Gallery_model->insertFileUpload($this->uri->segment(5), $file_id1);
                    }
                }
            }
            $this->session->set_flashdata('error_message', '<div class="alert alert-success" role="alert">' . $this->lang->line('success_message_alert') . '</div>');
            redirect($this->csz_referrer->getIndex('gallery_edit'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', '<div class="alert alert-danger" role="alert">' . $this->lang->line('error_message_alert') . '</div>');
            redirect($this->csz_referrer->getIndex('gallery_edit'), 'refresh');
        }
    }

    public function uploadIndexSave() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::chkVisitor($this->session->userdata('user_admin_id'));
        $path = FCPATH . "/photo/plugin/gallery/";
        $filedel = $this->input->post('filedel', TRUE);
        $caption = $this->input->post('caption', TRUE);
        if (isset($filedel)) {
            foreach ($filedel as $value) {
                if ($value) {
                    $filename = $this->Csz_model->getValue('file_upload', 'gallery_picture', 'gallery_picture_id', $value, 1);
                    if ($filename->file_upload) {
                        @unlink($path . $filename->file_upload);
                    }
                    $this->Csz_admin_model->removeData('gallery_picture', 'gallery_picture_id', $value);
                }
            }
        }
        if (isset($caption)) {
            foreach ($caption as $key => $value) {
                if ($value && $key) {
                    $this->db->set('caption', $value, TRUE);
                    $this->db->set('timestamp_update', 'NOW()', FALSE);
                    $this->db->where('gallery_picture_id', $key);
                    $this->db->update('gallery_picture');
                }
            }
        }
        $this->session->set_flashdata('error_message', '<div class="alert alert-success" role="alert">' . $this->lang->line('success_message_alert') . '</div>');
        redirect($this->csz_referrer->getIndex('gallery_edit'), 'refresh');
    }

    public function delete() {
        admin_helper::is_logged_in($this->session->userdata('admin_email'));
        admin_helper::chkVisitor($this->session->userdata('user_admin_id'));
        if ($this->uri->segment(5)) {
            $path = FCPATH . "/photo/plugin/gallery/";
            //Delete the data
            $filedel = $this->Csz_model->getValue('*', 'gallery_picture', 'gallery_db_id', $this->uri->segment(5));
            if (!empty($filedel)) {
                foreach ($filedel as $value) {
                    if ($value) {
                        if ($value->file_upload) {
                            @unlink($path . $value->file_upload);
                        }
                        $this->Csz_admin_model->removeData('gallery_picture', 'gallery_picture_id', $value->gallery_picture_id);
                    }
                }
            }
            $this->Gallery_model->delete($this->uri->segment(5));
            $this->session->set_flashdata('error_message', '<div class="alert alert-success" role="alert">' . $this->lang->line('success_message_alert') . '</div>');
        }
        redirect($this->csz_referrer->getIndex('gallery'), 'refresh');
    }

}