<?php

use app\services\utilities\Arr;
use app\services\AbstractKanban;
use app\services\surveyors\SurveyorsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

//class Surveyors_model extends App_Model
class Surveyors_model extends Clients_Model
{
    private $states;
    private $contact_columns;

    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->states = hooks()->apply_filters('before_set_surveyor_states', [
            1,
            2,
            5,
            3,
            4,
        ]);

        $this->load->model('clients_model');
        $this->contact_columns = hooks()->apply_filters('contact_columns', ['firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction', 'invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'program_emails', 'ticket_emails', 'is_primary']);

        $this->load->model(['client_vault_entries_model', 'client_groups_model', 'statement_model']);
    }

    private function check_zero_columns($data)
    {
        if (!isset($data['show_primary_contact'])) {
            $data['show_primary_contact'] = 0;
        }

        if (isset($data['default_currency']) && $data['default_currency'] == '' || !isset($data['default_currency'])) {
            $data['default_currency'] = 0;
        }

        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }

        if (isset($data['billing_country']) && $data['billing_country'] == '' || !isset($data['billing_country'])) {
            $data['billing_country'] = 0;
        }

        if (isset($data['shipping_country']) && $data['shipping_country'] == '' || !isset($data['shipping_country'])) {
            $data['shipping_country'] = 0;
        }

        return $data;
    }

    /**
     * Get unique sale agent for surveyors / Used for filters
     * @return array
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, ' ', lastname) as full_name FROM " . db_prefix() . 'surveyors JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'surveyors.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,'. db_prefix() . 'clients.userid as userid,');

        $this->db->join(db_prefix() . 'countries', '' . db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country', 'left');
        $this->db->join(db_prefix() . 'contacts', '' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {

            $this->db->where(db_prefix() . 'clients.userid', $id);
            $client = $this->db->get(db_prefix() . 'clients')->row();

            if ($client && get_option('company_requires_vat_number_field') == 0) {
                $client->vat = null;
            }

            $this->load->model('email_schedule_model');
            $client->scheduled_email = $this->email_schedule_model->get($id, 'surveyor');

            $GLOBALS['client'] = $client;

            return $client;
        }

        $this->db->order_by('company', 'asc');
        $result = $this->db->get(db_prefix() . 'clients')->result_array();
        return $result;
    }

    /**
     * Get surveyor states
     * @return array
     */
    public function get_states()
    {
        return $this->states;
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $surveyor = $this->db->get(db_prefix() . 'surveyors')->row();

        if ($surveyor) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'surveyors', ['signature' => null]);

            if (!empty($surveyor->signature)) {
                unlink(get_upload_path_by_type('surveyor') . $id . '/' . $surveyor->signature);
            }

            return true;
        }

        return false;
    }

    /**
     * Copy surveyor
     * @param mixed $id surveyor id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_surveyor                       = $this->get($id);
        $new_surveyor_data               = [];
        $new_surveyor_data['clientid']   = $_surveyor->clientid;
        $new_surveyor_data['program_id'] = $_surveyor->program_id;
        $new_surveyor_data['number']     = get_option('next_surveyor_number');
        $new_surveyor_data['date']       = _d(date('Y-m-d'));
        $new_surveyor_data['expirydate'] = null;

        if ($_surveyor->expirydate && get_option('surveyor_due_after') != 0) {
            $new_surveyor_data['expirydate'] = _d(date('Y-m-d', strtotime('+' . get_option('surveyor_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_surveyor_data['show_quantity_as'] = $_surveyor->show_quantity_as;
        $new_surveyor_data['currency']         = $_surveyor->currency;
        $new_surveyor_data['subtotal']         = $_surveyor->subtotal;
        $new_surveyor_data['total']            = $_surveyor->total;
        $new_surveyor_data['adminnote']        = $_surveyor->adminnote;
        $new_surveyor_data['adjustment']       = $_surveyor->adjustment;
        $new_surveyor_data['discount_percent'] = $_surveyor->discount_percent;
        $new_surveyor_data['discount_total']   = $_surveyor->discount_total;
        $new_surveyor_data['discount_type']    = $_surveyor->discount_type;
        $new_surveyor_data['terms']            = $_surveyor->terms;
        $new_surveyor_data['sale_agent']       = $_surveyor->sale_agent;
        $new_surveyor_data['reference_no']     = $_surveyor->reference_no;
        // Since version 1.0.6
        $new_surveyor_data['billing_street']   = clear_textarea_breaks($_surveyor->billing_street);
        $new_surveyor_data['billing_city']     = $_surveyor->billing_city;
        $new_surveyor_data['billing_state']    = $_surveyor->billing_state;
        $new_surveyor_data['billing_zip']      = $_surveyor->billing_zip;
        $new_surveyor_data['billing_country']  = $_surveyor->billing_country;
        $new_surveyor_data['shipping_street']  = clear_textarea_breaks($_surveyor->shipping_street);
        $new_surveyor_data['shipping_city']    = $_surveyor->shipping_city;
        $new_surveyor_data['shipping_state']   = $_surveyor->shipping_state;
        $new_surveyor_data['shipping_zip']     = $_surveyor->shipping_zip;
        $new_surveyor_data['shipping_country'] = $_surveyor->shipping_country;
        if ($_surveyor->include_shipping == 1) {
            $new_surveyor_data['include_shipping'] = $_surveyor->include_shipping;
        }
        $new_surveyor_data['show_shipping_on_surveyor'] = $_surveyor->show_shipping_on_surveyor;
        // Set to unpaid state automatically
        $new_surveyor_data['state']     = 1;
        $new_surveyor_data['clientnote'] = $_surveyor->clientnote;
        $new_surveyor_data['adminnote']  = '';
        $new_surveyor_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;
        foreach ($_surveyor->items as $item) {
            $new_surveyor_data['newitems'][$key]['description']      = $item['description'];
            $new_surveyor_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_surveyor_data['newitems'][$key]['qty']              = $item['qty'];
            $new_surveyor_data['newitems'][$key]['unit']             = $item['unit'];
            $new_surveyor_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_surveyor_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_surveyor_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_surveyor_data['newitems'][$key]['rate']  = $item['rate'];
            $new_surveyor_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_surveyor_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_surveyor_data);
        if ($id) {
            $custom_fields = get_custom_fields('surveyor');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_surveyor->id, $field['id'], 'surveyor', false);
                if ($value == '') {
                    continue;
                }

                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'surveyor',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_surveyor->id, 'surveyor');
            handle_tags_save($tags, $id, 'surveyor');

            log_activity('Copied surveyor ' . format_surveyor_number($_surveyor->id));

            return $id;
        }

        return false;
    }

    /**
     * Performs surveyors totals state
     * @param array $data
     * @return array
     */
    public function get_surveyors_total($data)
    {
        $states            = $this->get_states();
        $has_permission_view = has_permission('surveyors', '', 'view');
        $this->load->model('currencies_model');

        $sql = 'SELECT';
        foreach ($states as $surveyor_state) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'surveyors WHERE state=' . $surveyor_state;
            //$sql .= ' AND currency =' . $this->db->escape_str($currencyid);
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $surveyor_state . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $state => $total) {
                $_result[$i]['total']         = $total;
                $_result[$i]['symbol']        = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['state']        = $state;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * @param array $_POST data
     * @param client_request is this request from the customer area
     * @return integer Insert ID
     * Add new client to database
     */
    public function add($data, $client_or_lead_convert_request = false)
    {
        $contact_data = [];

        foreach ($this->contact_columns as $field) {
            if (isset($data[$field])) {
                $contact_data[$field] = $data[$field];
                // Phonenumber is also used for the company profile
                if ($field != 'phonenumber') {
                    unset($data[$field]);
                }
            }
        }

        if (isset($data['groups_in'])) {
            $groups_in = $data['groups_in'];
            unset($data['groups_in']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['hash'] = app_generate_hash();

        if (is_staff_logged_in()) {
            $data['addedfrom'] = get_staff_user_id();
        }

        // New filter action
        $data = hooks()->apply_filters('before_surveyor_added', $data);

        //trigger exception in a "try" block
        try {
            $company_name_exist = $this->check_surveyor_name_exist($data['company']);
            if($company_name_exist){
                return;
            }
            $this->db->insert(db_prefix() . 'clients', $data);
        }

        //catch exception
        catch(Exception $e) {
          echo 'Message: ' .$e->getMessage();
        }


        $userid = $this->db->insert_id();
        if ($userid) {
            // Update next surveyor number in settings
            $this->db->where('name', 'next_surveyor_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            $log = 'ID: ' . $userid;

            if ($log == '' && isset($contact_id)) {
                $log = get_contact_full_name($contact_id);
            }

            $isStaff = null;
            if (!is_client_logged_in() && is_staff_logged_in()) {
                $log .= ', From Staff: ' . get_staff_user_id();
                $isStaff = get_staff_user_id();
            }
            $surveyor = $this->get($userid);
            if ($surveyor->assigned != 0) {
                if ($surveyor->assigned != get_staff_user_id()) {
                    $notified = add_notification([
                        'description'     => 'not_surveyor_already_created',
                        'touserid'        => get_staff_user_id(),
                        'fromuserid'      => get_staff_user_id(),
                        'link'            => 'surveyor/list_surveyor/' . $insert_id .'#' . $insert_id,
                        'additional_data' => serialize([
                            $surveyor->subject,
                        ]),
                    ]);
                    if ($notified) {
                        pusher_trigger_notification([get_staff_user_id()]);
                    }
                }
            }
            hooks()->do_action('after_surveyor_added', $userid);

            log_activity('New surveyor Created [' . $log . ']', $isStaff);
        }

        return $userid;
    }

    /**
     * Get surveyor surveyors id
     * @param mixed $id item id
     * @return object
     */

    public function get_surveyor_surveyors($id ='')
    {
        if($id){
            $this->db->where('surveyor_id', $id);
        }

        return $this->db->get(db_prefix() . 'surveyor_surveyors')->row();
    }

    public function get_surveyor_companies($id ='')
    {
        if($id){
            $this->db->where('company_id', $id);
        }

        return $this->db->get(db_prefix() . 'surveyor_companies')->row();
    }

    public function check_surveyor_name_exist($company){
        $this->db->select('company');
        $this->db->where('company', $company);
        $result = $this->db->get(db_prefix(). 'clients')->num_rows();
        if($result>0){
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param  array $_POST data
     * @param  integer ID
     * @return boolean
     * Update client informations
     */

    public function update($data, $id, $client_request = false)
    {
        $updated = false;
        $data    = $this->check_zero_columns($data);
        $origin = $this->get($id);

        $data = hooks()->apply_filters('before_client_updated', $data, $id);

        $groups_in                     = Arr::pull($data, 'groups_in') ?? false;

        //trigger exception in a "try" block
        try {
            $company_name_exist = $this->check_surveyor_name_exist($data['company']);
            if($company_name_exist && ($origin->company!=$data['company'])){
                return;
            }
            $this->db->where('userid', $id);
            $this->db->update(db_prefix() . 'clients', $data);
        }

        //catch exception
        catch(Exception $e) {
          echo 'Message: ' .$e->getMessage();
        }


        if ($this->db->affected_rows() > 0) {
            $updated = true;
            $surveyor = $this->get($id);

            $fields = array('company', 'vat','siup', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'phonenumber');
            $custom_data = '';
            foreach ($fields as $field) {
                if ($origin->$field != $surveyor->$field) {
                    $custom_data .= str_replace('_', ' ', $field) .' '. $origin->$field . ' to ' .$surveyor->$field .'<br />';
                }
            }
            $this->log_surveyor_activity($origin->userid, 'surveyor_activity_changed', false, serialize([
                '<custom_data>'. $custom_data .'</custom_data>',
            ]));
        }

        if ($this->client_groups_model->sync_customer_groups($id, $groups_in)) {
            $updated = true;
        }

        hooks()->do_action('client_updated', [
            'id'                            => $id,
            'data'                          => $data,
            'update_all_other_transactions' => $update_all_other_transactions,
            'groups_in'                     => $groups_in,
            'updated'                       => &$updated,
        ]);

        if ($updated) {
            log_activity('Customer Info Updated [ID: ' . $id . ']');
        }

        return $surveyor;
    }

    public function mark_action_state($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'surveyors', [
            'state' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $surveyor = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $surveyor->addedfrom);
                $this->db->or_where('staffid', $surveyor->sale_agent);
                $staff_surveyor = $this->db->get(db_prefix() . 'staff')->result_array();

                $invoiceid = false;
                $invoiced  = false;

                $contact_id = !is_client_logged_in()
                    ? get_primary_contact_user_id($surveyor->clientid)
                    : get_contact_user_id();

                if ($action == 4) {
                    if (get_option('surveyor_auto_convert_to_invoice_on_client_accept') == 1) {
                        $invoiceid = $this->convert_to_invoice($id, true);
                        $this->load->model('invoices_model');
                        if ($invoiceid) {
                            $invoiced = true;
                            $invoice  = $this->invoices_model->get($invoiceid);
                            $this->log_surveyor_activity($id, 'surveyor_activity_client_accepted_and_converted', true, serialize([
                                '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>',
                            ]));
                        }
                    } else {
                        $this->log_surveyor_activity($id, 'surveyor_activity_client_accepted', true);
                    }

                    // Send thank you email to all contacts with permission surveyors
                    $contacts = $this->clients_model->get_contacts($surveyor->clientid, ['active' => 1, 'surveyor_emails' => 1]);

                    foreach ($contacts as $contact) {
                        send_mail_template('surveyor_accepted_to_customer', $surveyor, $contact);
                    }

                    foreach ($staff_surveyor as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_surveyor_customer_accepted',
                            'link'            => 'surveyors/list_surveyors/' . $id,
                            'additional_data' => serialize([
                                format_surveyor_number($surveyor->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }

                        send_mail_template('surveyor_accepted_to_staff', $surveyor, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    hooks()->do_action('surveyor_accepted', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                } elseif ($action == 3) {
                    foreach ($staff_surveyor as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_surveyor_customer_declined',
                            'link'            => 'surveyors/list_surveyors/' . $id,
                            'additional_data' => serialize([
                                format_surveyor_number($surveyor->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer declined surveyor
                        send_mail_template('surveyor_declined_to_staff', $surveyor, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    $this->log_surveyor_activity($id, 'surveyor_activity_client_declined', true);
                    hooks()->do_action('surveyor_declined', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'surveyors', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
                // Admin marked surveyor
                $this->log_surveyor_activity($id, 'surveyor_activity_marked', false, serialize([
                    '<state>' . $action . '</state>',
                ]));

                return true;
            }
        }

        return false;
    }

    /**
     * Get surveyor attachments
     * @param mixed $surveyor_id
     * @param string $id attachment id
     * @return mixed
     */
    public function get_attachments($surveyor_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $surveyor_id);
        }
        $this->db->where('rel_type', 'surveyor');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete surveyor attachment
     * @param mixed $id attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('surveyor') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('surveyor Attachment Deleted [surveyorID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('surveyor') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('surveyor') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('surveyor') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete surveyor items and all connections
     * @param mixed $id surveyorid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_surveyor') == 1 && $simpleDelete == false) {
            if (!is_last_surveyor($id)) {
                return false;
            }
        }
        $surveyor = $this->get($id);
        if (!is_null($surveyor->invoiceid) && $simpleDelete == false) {
            return [
                'is_invoiced_surveyor_delete_error' => true,
            ];
        }
        hooks()->do_action('before_surveyor_deleted', $id);

        $number = format_surveyor_number($id);

        $this->clear_signature($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'surveyors');

        if ($this->db->affected_rows() > 0) {
            if (!is_null($surveyor->short_link)) {
                app_archive_short_link($surveyor->short_link);
            }

            if (get_option('surveyor_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_surveyor_number = get_option('next_surveyor_number');
                if ($current_next_surveyor_number > 1) {
                    // Decrement next surveyor number to
                    $this->db->where('name', 'next_surveyor_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'proposals', [
                    'surveyor_id' => $id,
                ]) > 0) {
                $this->db->where('surveyor_id', $id);
                $surveyor = $this->db->get(db_prefix() . 'proposals')->row();
                $this->db->where('id', $surveyor->id);
                $this->db->update(db_prefix() . 'proposals', [
                    'surveyor_id'    => null,
                    'date_converted' => null,
                ]);
            }

            delete_tracked_emails($id, 'surveyor');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="surveyor" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'surveyor');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'surveyor');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'surveyor');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'surveyor');
            $this->db->delete(db_prefix() . 'surveyor_activity');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'surveyor');
            $this->db->delete('scheduled_emails');

            // Get related tasks
            $this->db->where('rel_type', 'surveyor');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('surveyors Deleted [Number: ' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set surveyor to sent when email is successfuly sended to client
     * @param mixed $id surveyorid
     */
    public function set_surveyor_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'surveyors', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);

        $this->log_surveyor_activity($id, 'invoice_surveyor_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));

        // Update surveyor state to sent
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'surveyors', [
            'state' => 2,
        ]);

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'surveyor');
        $this->db->delete('scheduled_emails');
    }

    /**
     * Send expiration reminder to customer
     * @param mixed $id surveyor id
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $surveyor        = $this->get($id);
        $surveyor_number = format_surveyor_number($surveyor->id);
        set_mailing_constant();
        $pdf              = surveyor_pdf($surveyor);
        $attach           = $pdf->Output($surveyor_number . '.pdf', 'S');
        $emails_sent      = [];
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'surveyors', [
            'is_expiry_notified' => 1,
        ]);

        $contacts = $this->clients_model->get_contacts($surveyor->clientid, ['active' => 1, 'surveyor_emails' => 1]);

        foreach ($contacts as $contact) {
            $template = mail_template('surveyor_expiration_reminder', $surveyor, $contact);

            $merge_fields = $template->get_merge_fields();

            $template->add_attachment([
                'attachment' => $attach,
                'filename'   => str_replace('/', '-', $surveyor_number . '.pdf'),
                'type'       => 'application/pdf',
            ]);

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
            }

            if (can_send_sms_based_on_creation_date($surveyor->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_ESTIMATE_EXP_REMINDER, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if (count($emails_sent) > 0 || $sms_sent) {
            if (count($emails_sent) > 0) {
                $this->log_surveyor_activity($id, 'not_expiry_reminder_sent', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]));
            }

            if ($sms_sent) {
                $this->log_surveyor_activity($id, 'sms_reminder_sent_to', false, serialize([
                    implode(', ', $sms_reminder_log),
                ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Send surveyor to client
     * @param mixed $id surveyorid
     * @param string $template email template to sent
     * @param boolean $attachpdf attach surveyor pdf or not
     * @return boolean
     */
    public function send_surveyor_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $surveyor = $this->get($id);

        if ($template_name == '') {
            $template_name = $surveyor->sent == 0 ?
                'surveyor_send_to_customer' :
                'surveyor_send_to_customer_already_sent';
        }

        $surveyor_number = format_surveyor_number($surveyor->id);

        $emails_sent = [];
        $send_to     = [];

        // Manually is used when sending the surveyor via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $send_to = $this->input->post('sent_to');
        } elseif (isset($GLOBALS['scheduled_email_contacts'])) {
            $send_to = $GLOBALS['scheduled_email_contacts'];
        } else {
            $contacts = $this->clients_model->get_contacts(
                $surveyor->clientid,
                ['active' => 1, 'surveyor_emails' => 1]
            );

            foreach ($contacts as $contact) {
                array_push($send_to, $contact['id']);
            }
        }

        $state_auto_updated = false;
        $state_now          = $surveyor->state;

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            // Auto update state to sent in case when user sends the surveyor is with state draft
            if ($state_now == 1) {
                $this->db->where('id', $surveyor->id);
                $this->db->update(db_prefix() . 'surveyors', [
                    'state' => 2,
                ]);
                $state_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_surveyor = $this->get($surveyor->id);
                set_mailing_constant();
                $pdf = surveyor_pdf($_pdf_surveyor);

                $attach = $pdf->Output($surveyor_number . '.pdf', 'S');
            }

            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    if (!$contact) {
                        continue;
                    }

                    $template = mail_template($template_name, $surveyor, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_surveyor_to_customer_file_name', [
                            'file_name' => str_replace('/', '-', $surveyor_number . '.pdf'),
                            'surveyor'  => $_pdf_surveyor,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            $this->set_surveyor_sent($id, $emails_sent);
            hooks()->do_action('surveyor_sent', $id);

            return true;
        }

        if ($state_auto_updated) {
            // surveyor not send to customer but the state was previously updated to sent now we need to revert back to draft
            $this->db->where('id', $surveyor->id);
            $this->db->update(db_prefix() . 'surveyors', [
                'state' => 1,
            ]);
        }

        return false;
    }

    /**
     * All surveyor activity
     * @param mixed $id surveyorid
     * @return array
     */
    public function get_surveyor_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'surveyor');
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'surveyor_activity')->result_array();
    }

    /**
     * Log surveyor activity to database
     * @param mixed $id surveyorid
     * @param string $description activity description
     */
    public function log_surveyor_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'surveyor_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'surveyor',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     * @param mixe $data $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_state($data['state'], $data['surveyorid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'surveyors', $data['state']);
    }

    /**
     * Get surveyor unique year for filtering
     * @return array
     */
    public function get_surveyors_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'surveyors ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_surveyor'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_surveyor']) && ($data['show_shipping_on_surveyor'] == 1 || $data['show_shipping_on_surveyor'] == 'on')) {
                $data['show_shipping_on_surveyor'] = 1;
            } else {
                $data['show_shipping_on_surveyor'] = 0;
            }
        }

        return $data;
    }

    public function do_kanban_query($state, $search = '', $page = 1, $sort = [], $count = false)
    {
        _deprecated_function('surveyors_model::do_kanban_query', '2.9.2', 'surveyorsPipeline class');

        $kanBan = (new surveyorsPipeline($state))
            ->search($search)
            ->page($page)
            ->sortBy($sort['sort'] ?? null, $sort['sort_by'] ?? null);

        if ($count) {
            return $kanBan->countAll();
        }

        return $kanBan->get();
    }


    /**
     * Add permit
     * @since  Version 1.0.2
     * @param mixed $data All $_POST data for the permit
     * @param mixed $id   relid id
     * @return boolean
     */
    public function add_permit($data, $id)
    {
        if (isset($data['notify_by_email'])) {
            $data['notify_by_email'] = 1;
        } //isset($data['notify_by_email'])
        else {
            $data['notify_by_email'] = 0;
        }
        //$data['date']        = to_sql_date($data['date'], true);
        $data['description'] = nl2br($data['description']);
        $data['creator']     = get_staff_user_id();
        $this->db->insert(db_prefix() . 'permits', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New permit Added [' . ucfirst($data['rel_type']) . 'ID: ' . $data['rel_id'] . ' Description: ' . $data['description'] . ']');
            return true;
        } //$insert_id
        return false;
    }

    public function edit_permit($data, $id)
    {
        if (isset($data['notify_by_email'])) {
            $data['notify_by_email'] = 1;
        } else {
            $data['notify_by_email'] = 0;
        }

        $data['date_issued']        = _d($data['date_issued'], true);
        $data['date_expired']        = _d($data['date_expired'], true);
        $category = get_kelompok_alat($data['category_id']);
        $data['category']           =  $category[0]['name'];
        $data['description'] = nl2br($data['description']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'permits', $data);

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }
    
    
    /**
     * Get all permits or 1 permit if id is passed
     * @since Version 1.0.2
     * @param  mixed $id permit id OPTIONAL
     * @return array or object
     */
    public function get_permits($id = '')
    {
        $this->db->join(db_prefix() . 'staff', '' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'permits.staff', 'left');
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'permits.id', $id);

            return $this->db->get(db_prefix() . 'permits')->row();
        } //is_numeric($id)
        $this->db->order_by('date_expired', 'desc');

        return $this->db->get(db_prefix() . 'permits')->result_array();
    }

    /**
     * Remove client permit from database
     * @since Version 1.0.2
     * @param  mixed $id permit id
     * @return boolean
     */
    public function delete_permit($id)
    {
        $permit = $this->get_permits($id);
        if ($permit->creator == get_staff_user_id() || is_admin()) {
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'permits');
            if ($this->db->affected_rows() > 0) {
                log_activity('permit Deleted [' . ucfirst($permit->rel_type) . 'ID: ' . $permit->id . ' Description: ' . $permit->description . ']');

                return true;
            } //$this->db->affected_rows() > 0
            return false;
        } //$permit->creator == get_staff_user_id() || is_admin()
        return false;
    }

    /**
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Update permit status Active/Inactive
     */
    public function change_permit_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'permits', [
            'is_active' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('permit_status_changed', [
                'id'     => $id,
                'status' => $status,
            ]);

            log_activity('Permit Status Changed [ID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            // Admin marked surveyor
            $this->db->reset_query();
            $this->db->where('id', $id);
            $permit = $this->db->get(db_prefix() . 'permits')->row();
            $status_text = 'In active';
            if($status){
                $status_text = 'Active';
            }
            $this->log_surveyor_activity($permit->rel_id, 'surveyor_permit_status_changed', false, serialize([
                '<custom_data>'. 
                _l('permit') . ' = '. $permit->permit_number .'<br />'. 
                'Staff  = '. get_staff_full_name($permit->staff) .'<br />'. 
                'Status = '. $status_text .'<br />'. 
                _l('description') . ' = '. $permit->description . 
                '</custom_data>',
            ]));

            return true;
        }

        return false;
    }
    
}
