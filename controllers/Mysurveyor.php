<?php defined('BASEPATH') or exit('No direct script access allowed');

class Mysurveyor extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('surveyors_model');
        $this->load->model('clients_model');
    }

    /* Get all surveyors in case user go on index page */
    public function list($id = '')
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/table'));
        }
        $contact_id = get_contact_user_id();
        $user_id = get_user_id_by_contact_id($contact_id);
        $client = $this->clients_model->get($user_id);
        $data['surveyors'] = $this->surveyors_model->get_client_surveyors($client);
        $data['surveyorid']            = $id;
        $data['title']                 = _l('surveyors_tracking');

        $data['bodyclass'] = 'surveyors';
        $this->data($data);
        $this->view('themes/'. active_clients_theme() .'/views/surveyors/surveyors');
        $this->layout();
    }

    public function show($id, $hash)
    {
        check_surveyor_restrictions($id, $hash);
        $surveyor = $this->surveyors_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($surveyor->clientid);
        }

        $identity_confirmation_enabled = get_option('surveyor_accept_identity_confirmation');

        if ($this->input->post('surveyor_action')) {
            $action = $this->input->post('surveyor_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->surveyors_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_surveyor_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_surveyor_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_surveyor_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), SCHEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'surveyors', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Surveyor PDF generator

        $surveyor_number = format_surveyor_number($surveyor->id);
        /*
        if ($this->input->post('surveyorpdf')) {
            try {
                $pdf = surveyor_pdf($surveyor);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$surveyor_number = format_surveyor_number($surveyor->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $surveyor_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_surveyor_filename', mb_strtoupper(slug_it($surveyor_number), 'UTF-8') . '.pdf', $surveyor);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $surveyor_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['surveyor_number']              = $surveyor_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['surveyor']                     = hooks()->apply_filters('surveyor_html_pdf_data', $surveyor);
        $data['bodyclass']                     = 'viewsurveyor';
        $data['client_company']                = $this->clients_model->get($surveyor->clientid)->company;
        $setSize = get_option('surveyor_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['surveyor_members']  = $this->surveyors_model->get_surveyor_members($surveyor->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('surveyor_number') . ' : ' . $surveyor_number ."\r\n";
        $qrcode_data .= _l('surveyor_date') . ' : ' . $surveyor->date ."\r\n";
        $qrcode_data .= _l('surveyor_datesend') . ' : ' . $surveyor->datesend ."\r\n";
        //$qrcode_data .= _l('surveyor_assigned_string') . ' : ' . get_staff_full_name($surveyor->assigned) ."\r\n";
        //$qrcode_data .= _l('surveyor_url') . ' : ' . site_url('surveyors/show/'. $surveyor->id .'/'.$surveyor->hash) ."\r\n";


        $surveyor_path = get_upload_path_by_type('surveyors') . $surveyor->id . '/';
        _maybe_create_upload_path('uploads/surveyors');
        _maybe_create_upload_path('uploads/surveyors/'.$surveyor_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $surveyor_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/surveyors/'.$surveyor_path .'assigned-'.$surveyor_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/surveyors/surveyorhtml');
        add_views_tracking('surveyor', $id);
        hooks()->do_action('surveyor_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }


    public function office($id, $hash)
    {
        check_surveyor_restrictions($id, $hash);
        $surveyor = $this->surveyors_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($surveyor->clientid);
        }

        $identity_confirmation_enabled = get_option('surveyor_accept_identity_confirmation');

        if ($this->input->post('surveyor_action')) {
            $action = $this->input->post('surveyor_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->surveyors_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_surveyor_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_surveyor_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_surveyor_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), SCHEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'surveyors', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Surveyor PDF generator

        $surveyor_number = format_surveyor_number($surveyor->id);
        /*
        if ($this->input->post('surveyorpdf')) {
            try {
                $pdf = surveyor_pdf($surveyor);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$surveyor_number = format_surveyor_number($surveyor->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $surveyor_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_surveyor_filename', mb_strtoupper(slug_it($surveyor_number), 'UTF-8') . '.pdf', $surveyor);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $surveyor_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['surveyor_number']              = $surveyor_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['surveyor']                     = hooks()->apply_filters('surveyor_html_pdf_data', $surveyor);
        $data['bodyclass']                     = 'viewsurveyor';
        $data['client_company']                = $this->clients_model->get($surveyor->clientid)->company;
        $setSize = get_option('surveyor_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['surveyor_members']  = $this->surveyors_model->get_surveyor_members($surveyor->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('surveyor_number') . ' : ' . $surveyor_number ."\r\n";
        $qrcode_data .= _l('surveyor_date') . ' : ' . $surveyor->date ."\r\n";
        $qrcode_data .= _l('surveyor_datesend') . ' : ' . $surveyor->datesend ."\r\n";
        //$qrcode_data .= _l('surveyor_assigned_string') . ' : ' . get_staff_full_name($surveyor->assigned) ."\r\n";
        //$qrcode_data .= _l('surveyor_url') . ' : ' . site_url('surveyors/show/'. $surveyor->id .'/'.$surveyor->hash) ."\r\n";


        $surveyor_path = get_upload_path_by_type('surveyors') . $surveyor->id . '/';
        _maybe_create_upload_path('uploads/surveyors');
        _maybe_create_upload_path('uploads/surveyors/'.$surveyor_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $surveyor_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/surveyors/'.$surveyor_path .'assigned-'.$surveyor_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/surveyors/surveyor_office_html');
        add_views_tracking('surveyor', $id);
        hooks()->do_action('surveyor_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }
    
    /* Generates surveyor PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_surveyor($id);
        if (!$canView) {
            access_denied('Surveyors');
        } else {
            if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && $canView == false) {
                access_denied('Surveyors');
            }
        }
        if (!$id) {
            redirect(admin_url('surveyors'));
        }
        $surveyor        = $this->surveyors_model->get($id);
        $surveyor_number = format_surveyor_number($surveyor->id);
        
        $surveyor->assigned_path = FCPATH . get_surveyor_upload_path('surveyor').$surveyor->id.'/assigned-'.$surveyor_number.'.png';
        $surveyor->acceptance_path = FCPATH . get_surveyor_upload_path('surveyor').$surveyor->id .'/'.$surveyor->signature;
        
        $surveyor->client_company = $this->clients_model->get($surveyor->clientid)->company;
        $surveyor->acceptance_date_string = _dt($surveyor->acceptance_date);


        try {
            $pdf = surveyor_pdf($surveyor);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('surveyor_file_name_admin_area', [
                            'file_name' => mb_strtoupper(slug_it($surveyor_number)) . '.pdf',
                            'surveyor'  => $surveyor,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    /* Generates surveyor PDF and senting to email  */
    public function office_pdf($id)
    {
        $canView = user_can_view_surveyor($id);
        if (!$canView) {
            access_denied('Surveyors');
        } else {
            if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && $canView == false) {
                access_denied('Surveyors');
            }
        }
        if (!$id) {
            redirect(admin_url('surveyors'));
        }
        $surveyor        = $this->surveyors_model->get($id);
        $surveyor_number = format_surveyor_number($surveyor->id);
        
        $surveyor->assigned_path = FCPATH . get_surveyor_upload_path('surveyor').$surveyor->id.'/assigned-'.$surveyor_number.'.png';
        $surveyor->acceptance_path = FCPATH . get_surveyor_upload_path('surveyor').$surveyor->id .'/'.$surveyor->signature;
        
        $surveyor->client_company = $this->clients_model->get($surveyor->clientid)->company;
        $surveyor->acceptance_date_string = _dt($surveyor->acceptance_date);


        try {
            $pdf = surveyor_office_pdf($surveyor);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('surveyor_file_name_admin_area', [
                            'file_name' => str_replace("SCH", "SCH-UPT", mb_strtoupper(slug_it($surveyor_number)) . '.pdf'),
                            'surveyor'  => $surveyor,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }
}
