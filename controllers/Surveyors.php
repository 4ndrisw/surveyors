<?php

use app\services\surveyors\SurveyorsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Surveyors extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('surveyors_model');
        $this->load->model('clients_model');
        $this->load->model('staff_model');
    }

    /* Get all surveyors in case user go on index page */
    public function index($id = '')
    {
        $this->list_surveyors($id);
    }

    /* List all surveyors datatables */
    public function list_surveyors($id = '')
    {
        if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && get_option('allow_staff_view_surveyors_assigned') == '0') {
            access_denied('surveyors');
        }

        $isPipeline = $this->session->userdata('surveyor_pipeline') == 'true';

        $data['surveyor_states'] = $this->surveyors_model->get_states();
        if ($isPipeline && !$this->input->get('state') && !$this->input->get('filter')) {
            $data['title']           = _l('surveyors_pipeline');
            $data['bodyclass']       = 'surveyors-pipeline surveyors-total-manual';
            $data['switch_pipeline'] = false;

            if (is_numeric($id)) {
                $data['surveyorid'] = $id;
            } else {
                $data['surveyorid'] = $this->session->flashdata('surveyorid');
            }

            $this->load->view('admin/surveyors/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('state') || $this->input->get('filter') && $isPipeline) {
                $this->pipeline(0, true);
            }
            
            $data['surveyorid']            = $id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('surveyors');
            $data['bodyclass']             = 'surveyors-total-manual';
            $data['surveyors_years']       = $this->surveyors_model->get_surveyors_years();
            $data['surveyors_sale_agents'] = $this->surveyors_model->get_sale_agents();
            if($id){
                $this->load->view('admin/surveyors/manage_small_table', $data);

            }else{
                $this->load->view('admin/surveyors/manage_table', $data);

            }

        }
    }

    public function table($client_id = '')
    {
        if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && get_option('allow_staff_view_surveyors_assigned') == '0') {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/table',[
            'client_id' => $client_id,
        ]));
    }

    /* Add new surveyor or update existing */
    public function surveyor($id = '')
    {
        if ($this->input->post()) {
            $surveyor_data = $this->input->post();

            $save_and_send_later = false;
            if (isset($surveyor_data['save_and_send_later'])) {
                unset($surveyor_data['save_and_send_later']);
                $save_and_send_later = true;
            }

            if ($id == '') {
                if (!has_permission('surveyors', '', 'create')) {
                    access_denied('surveyors');
                }
                $surveyor_data['is_surveyor'] = '1';
                $next_surveyor_number = get_option('next_surveyor_number');
                $_format = get_option('surveyor_number_format');
                $_prefix = get_option('surveyor_prefix');
                
                $prefix  = isset($surveyor->prefix) ? $surveyor->prefix : $_prefix;
                $number_format  = isset($surveyor->number_format) ? $surveyor->number_format : $_format;
                $number  = isset($surveyor->number) ? $surveyor->number : $next_surveyor_number;

                $surveyor_data['prefix'] = $prefix;
                $surveyor_data['number_format'] = $number_format;
                $date = date('Y-m-d');
                
                //$surveyor_data['formatted_number'] = surveyor_number_format($number, $format, $prefix, $date);
                //var_dump($surveyor_data);
                //die();
                $id = $this->surveyors_model->add($surveyor_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('surveyor')));

                    $redUrl = admin_url('surveyors/list_surveyors/' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_surveyor_pipeline_autoload($id) ? $redUrl : admin_url('surveyors/list_surveyors/')
                    );
                }
            } else {
                if (has_permission('surveyors', '', 'edit') || 
                   (has_permission('surveyors', '', 'edit_own') && is_staff_related_to_surveyor($id))
                   ) {
                  
                    $success = $this->surveyors_model->update($surveyor_data, $id);
                    if ($success) {
                        set_alert('success', _l('updated_successfully', _l('surveyor')));
                    }
                    if ($this->set_surveyor_pipeline_autoload($id)) {
                        redirect(admin_url('surveyors/list_surveyors/'));
                    } else {
                        redirect(admin_url('surveyors/list_surveyors/' . $id));
                    }
                }else{
                    access_denied('surveyors');
                }
            }
        }
        if ($id == '') {
            $title = _l('create_new_surveyor');
        } else {
            $surveyor = $this->surveyors_model->get($id);

            if (!$surveyor || !user_can_view_surveyor($id)) {
                blank_page(_l('surveyor_not_found'));
            }
            $data['surveyor'] = $surveyor;
            $data['edit']     = true;
            $title            = _l('edit', _l('surveyor_lowercase'));
        }

        $data['surveyor_states'] = $this->surveyors_model->get_states();
        $data['title']             = $title;
        $this->load->view('admin/surveyors/surveyor', $data);
    }
    
    public function clear_signature($id)
    {
        if (has_permission('surveyors', '', 'delete')) {
            $this->surveyors_model->clear_signature($id);
        }

        redirect(admin_url('surveyors/list_surveyors/' . $id));
    }

    public function update_number_settings($id)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];
        if (has_permission('surveyors', '', 'edit')) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'surveyors', [
                'prefix' => $this->input->post('prefix'),
            ]);
            if ($this->db->affected_rows() > 0) {
                $response['success'] = true;
                $response['message'] = _l('updated_successfully', _l('surveyor'));
            }
        }

        echo json_encode($response);
        die;
    }

    public function validate_surveyor_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows(db_prefix() . 'surveyors', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function delete_attachment($id)
    {
        $file = $this->surveyors_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->surveyors_model->delete_attachment($id);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Get all surveyor data used when user click on surveyor number in a datatable left side*/
    public function get_surveyor_data_ajax($id, $to_return = false)
    {
        if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && get_option('allow_staff_view_surveyors_assigned') == '0') {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No surveyor found');
        }

        $surveyor = $this->surveyors_model->get($id);

        if (!$surveyor || !user_can_view_surveyor($id)) {
            echo _l('surveyor_not_found');
            die;
        }

        // $data = prepare_mail_preview_data($template_name, $surveyor->clientid);
        $data['title'] = 'Form add / Edit Staff';
        $data['activity']          = $this->surveyors_model->get_surveyor_activity($id);
        $data['surveyor']          = $surveyor;
        $data['categories']          = get_kelompok_alat();
        $data['members']           = $this->staff_model->get('', ['active' => 1, 'client_id'=>$id]);
        $data['surveyor_states'] = $this->surveyors_model->get_states();
        $data['totalNotes']        = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'surveyor']);

        $data['send_later'] = false;
        if ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        if ($to_return == false) {
            $this->load->view('admin/surveyors/surveyor_preview_template', $data);
        } else {
            return $this->load->view('admin/surveyors/surveyor_preview_template', $data, true);
        }
    }

    public function get_surveyors_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->surveyors_model->get_surveyors_total($this->input->post());

            $this->load->model('currencies_model');

            if (!$this->input->post('customer_id')) {
                $multiple_currencies = call_user_func('is_using_multiple_currencies', db_prefix() . 'surveyors');
            } else {
                $multiple_currencies = call_user_func('is_client_using_multiple_currencies', $this->input->post('customer_id'), db_prefix() . 'surveyors');
            }

            if ($multiple_currencies) {
                $data['currencies'] = $this->currencies_model->get();
            }

            $data['surveyors_years'] = $this->surveyors_model->get_surveyors_years();

            if (
                count($data['surveyors_years']) >= 1
                && !\app\services\utilities\Arr::inMultidimensional($data['surveyors_years'], 'year', date('Y'))
            ) {
                array_unshift($data['surveyors_years'], ['year' => date('Y')]);
            }

            $data['_currency'] = $data['totals']['currencyid'];
            unset($data['totals']['currencyid']);
            $this->load->view('admin/surveyors/surveyors_total_template', $data);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_surveyor($rel_id)) {
            $this->surveyors_model->add_note($this->input->post(), 'surveyor', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_surveyor($id)) {
            $data['notes'] = $this->surveyors_model->get_notes($id, 'surveyor');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function mark_action_state($state, $id)
    {
        if (!has_permission('surveyors', '', 'edit') || !has_permission('surveyors', '', 'edit_own')) {
            access_denied('surveyors');
        }
        $success = $this->surveyors_model->mark_action_state($state, $id);
        if ($success) {
            set_alert('success', _l('surveyor_state_changed_success'));
        } else {
            set_alert('danger', _l('surveyor_state_changed_fail'));
        }
        if ($this->set_surveyor_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('surveyors/list_surveyors/' . $id));
        }
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_surveyor($id);
        if (!$canView) {
            access_denied('Surveyors');
        } else {
            if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && $canView == false) {
                access_denied('Surveyors');
            }
        }

        $success = $this->surveyors_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_surveyor_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('surveyors/list_surveyors/' . $id));
        }
    }

    /* Send surveyor to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_surveyor($id);
        if (!$canView) {
            access_denied('surveyors');
        } else {
            if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && $canView == false) {
                access_denied('surveyors');
            }
        }

        try {
            $success = $this->surveyors_model->send_surveyor_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        // In case client use another language
        load_admin_language();
        if ($success) {
            set_alert('success', _l('surveyor_sent_to_client_success'));
        } else {
            set_alert('danger', _l('surveyor_sent_to_client_fail'));
        }
        if ($this->set_surveyor_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('surveyors/list_surveyors/' . $id));
        }
    }

    /* Convert surveyor to invoice */
    public function convert_to_invoice($id)
    {
        if (!has_permission('invoices', '', 'create')) {
            access_denied('invoices');
        }
        if (!$id) {
            die('No surveyor found');
        }
        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }
        $invoiceid = $this->surveyors_model->convert_to_invoice($id, false, $draft_invoice);
        if ($invoiceid) {
            set_alert('success', _l('surveyor_convert_to_invoice_successfully'));
            redirect(admin_url('invoices/list_invoices/' . $invoiceid));
        } else {
            if ($this->session->has_userdata('surveyor_pipeline') && $this->session->userdata('surveyor_pipeline') == 'true') {
                $this->session->set_flashdata('surveyorid', $id);
            }
            if ($this->set_surveyor_pipeline_autoload($id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('surveyors/list_surveyors/' . $id));
            }
        }
    }

    public function copy($id)
    {
        if (!has_permission('surveyors', '', 'create')) {
            access_denied('surveyors');
        }
        if (!$id) {
            die('No surveyor found');
        }
        $new_id = $this->surveyors_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('surveyor_copied_successfully'));
            if ($this->set_surveyor_pipeline_autoload($new_id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('surveyors/surveyor/' . $new_id));
            }
        }
        set_alert('danger', _l('surveyor_copied_fail'));
        if ($this->set_surveyor_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('surveyors/surveyor/' . $id));
        }
    }

    /* Delete surveyor */
    public function delete($id)
    {
        if (!has_permission('surveyors', '', 'delete')) {
            access_denied('surveyors');
        }
        if (!$id) {
            redirect(admin_url('surveyors/list_surveyors'));
        }
        $success = $this->surveyors_model->delete($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_invoiced_surveyor_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('surveyor')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('surveyor_lowercase')));
        }
        redirect(admin_url('surveyors/list_surveyors'));
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'surveyors', get_acceptance_info_array(true));
        }

        redirect(admin_url('surveyors/list_surveyors/' . $id));
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
            redirect(admin_url('surveyors/list_surveyors'));
        }
        $surveyor        = $this->surveyors_model->get($id);
        $surveyor_number = format_surveyor_number($surveyor->id);

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

    // Pipeline
    public function get_pipeline()
    {
        if (has_permission('surveyors', '', 'view') || has_permission('surveyors', '', 'view_own') || get_option('allow_staff_view_surveyors_assigned') == '1') {
            $data['surveyor_states'] = $this->surveyors_model->get_states();
            $this->load->view('admin/surveyors/pipeline/pipeline', $data);
        }
    }

    public function pipeline_open($id)
    {
        $canView = user_can_view_surveyor($id);
        if (!$canView) {
            access_denied('Surveyors');
        } else {
            if (!has_permission('surveyors', '', 'view') && !has_permission('surveyors', '', 'view_own') && $canView == false) {
                access_denied('Surveyors');
            }
        }

        $data['userid']       = $id;
        $data['surveyor'] = $this->get_surveyor_data_ajax($id, true);
        $this->load->view('admin/surveyors/pipeline/surveyor', $data);
    }

    public function update_pipeline()
    {
        if (has_permission('surveyors', '', 'edit') || has_permission('surveyors', '', 'edit_own')) {
            $this->surveyors_model->update_pipeline($this->input->post());
        }
    }

    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'surveyor_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('surveyors/list_surveyors'));
        }
    }

    public function pipeline_load_more()
    {
        $state = $this->input->get('state');
        $page   = $this->input->get('page');

        $surveyors = (new SurveyorsPipeline($state))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($surveyors as $surveyor) {
            $this->load->view('admin/surveyors/pipeline/_kanban_card', [
                'surveyor' => $surveyor,
                'state'   => $state,
            ]);
        }
    }

    public function set_surveyor_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('surveyor_pipeline')
                && $this->session->userdata('surveyor_pipeline') == 'true') {
            $this->session->set_flashdata('surveyorid', $id);

            return true;
        }

        return false;
    }

    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('surveyor_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('surveyor_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }
/*
    public function get_staff($userid='')
    {
        $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/staff'));
    }
*/
    public function table_staffs($client_id,$surveyor = true)
    {
        if (
            !has_permission('surveyors', '', 'view')
            && !has_permission('surveyors', '', 'view_own')
            && get_option('allow_staff_view_surveyors_assigned') == 0
        ) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/staff'), array('client_id'=>$client_id));
    }

    /* Since version 1.0.2 add client permit */
    public function add_permit($rel_id, $rel_type)
    {
        $message    = '';
        $alert_type = 'warning';
        if ($this->input->post()) {
            $success = $this->surveyors_model->add_permit($this->input->post(), $rel_id);
            if ($success) {
                $alert_type = 'success';
                $message    = _l('permit_added_successfully');
            }else{
                $alert_type = 'warning';
                $message    = _l('permit_failed_to_add');
            }
        }
        echo json_encode([
            'alert_type' => $alert_type,
            'message'    => $message,
        ]);
    }

    public function get_permits($id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/permits'), [
                'id'       => $id,
                'rel_type' => $rel_type,
            ]);
        }
    }

    public function get_staff_permits($id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/permits'), [
                'id'       => $id,
                'rel_type' => $rel_type,
            ]);
        }
    }

    public function my_permits()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/staff_permits'));
        }
    }

    public function permits()
    {
        $this->load->model('staff_model');
        $data['members']   = $this->staff_model->get('', ['active' => 1]);
        $data['title']     = _l('permits');
        $data['bodyclass'] = 'all-permits';
        $this->load->view('admin/utilities/all_permits', $data);
    }

    public function permits_table()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('surveyors', 'admin/tables/all_permits'));
        }
    }

    /* Since version 1.0.2 delete client permit */
    public function delete_permit($rel_id, $id, $rel_type)
    {
        if (!$id && !$rel_id) {
            die('No permit found');
        }
        $success    = $this->surveyors_model->delete_permit($id);
        $alert_type = 'warning';
        $message    = _l('permit_failed_to_delete');
        if ($success) {
            $alert_type = 'success';
            $message    = _l('permit_deleted');
        }
        echo json_encode([
            'alert_type' => $alert_type,
            'message'    => $message,
        ]);
    }

    public function get_permit($id)
    {
        $permit = $this->surveyors_model->get_permits($id);
        if ($permit) {
            if ($permit->creator == get_staff_user_id() || is_admin()) {
                $permit->date_issued        = _d($permit->date_issued);
                $permit->date_expired        = _d($permit->date_expired);
                //$permit->category        = $permit->category;
                $permit->description = clear_textarea_breaks($permit->description);
                echo json_encode($permit);
            }
        }
    }



    public function edit_permit($id)
    {
        $permit = $this->surveyors_model->get_permits($id);
        if ($permit && ($permit->creator == get_staff_user_id() || is_admin()) && $permit->isnotified == 0) {
            $success = $this->surveyors_model->edit_permit($this->input->post(), $id);
            echo json_encode([
                    'alert_type' => 'success',
                    'message'    => ($success ? _l('updated_successfully', _l('permit')) : ''),
                ]);
        }
    }

    /* Change client status / active / inactive */
    public function change_permit_status($id, $status)
    {
        if ($this->input->is_ajax_request()) {
            $this->surveyors_model->change_permit_status($id, $status);
        }
    }

}
