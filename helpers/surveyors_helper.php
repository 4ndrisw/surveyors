<?php
defined('BASEPATH') or exit('No direct script access allowed');

function get_surveyor_name_by_id($surveyor_id){
    $CI = &get_instance();
        $CI->db->select(['company']);
        $CI->db->where('userid', $surveyor_id);
    return $CI->db->get(db_prefix() . 'clients')->row('company');

}


function is_surveyor_staff($staff_id){
    $CI = &get_instance();
    $CI->db->select(['staffid']);
    $CI->db->where('client_type', 'surveyor');
    $CI->db->where('is_not_staff', 0);
    $CI->db->where('staffid', $staff_id);
    return (bool)$CI->db->get(db_prefix() . 'staff')->result();
}

function get_surveyor_id_by_staff_id($staff_id){
    $CI = &get_instance();
        $CI->db->select(['client_id']);
        $CI->db->where('staffid', $staff_id);
    return $CI->db->get(db_prefix() . 'staff')->row('client_id');
}

function surveyors_notification()
{
    $CI = &get_instance();
    $CI->load->model('surveyors/surveyors_model');
    $surveyors = $CI->surveyors_model->get('', true);
    /*
    foreach ($surveyors as $goal) {
        $achievement = $CI->surveyors_model->calculate_goal_achievement($goal['id']);

        if ($achievement['percent'] >= 100) {
            if (date('Y-m-d') >= $goal['end_date']) {
                if ($goal['notify_when_achieve'] == 1) {
                    $CI->surveyors_model->notify_staff_members($goal['id'], 'success', $achievement);
                } else {
                    $CI->surveyors_model->mark_as_notified($goal['id']);
                }
            }
        } else {
            // not yet achieved, check for end date
            if (date('Y-m-d') > $goal['end_date']) {
                if ($goal['notify_when_fail'] == 1) {
                    $CI->surveyors_model->notify_staff_members($goal['id'], 'failed', $achievement);
                } else {
                    $CI->surveyors_model->mark_as_notified($goal['id']);
                }
            }
        }
    }
    */
}


/**
 * Function that return surveyor item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_surveyor_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'surveyor');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

/**
 * Get Surveyor short_url
 * @since  Version 2.7.3
 * @param  object $surveyor
 * @return string Url
 */
function get_surveyor_shortlink($surveyor)
{
    $long_url = site_url("surveyor/{$surveyor->id}/{$surveyor->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if surveyor has short link, if yes return short link
    if (!empty($surveyor->short_link)) {
        return $surveyor->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url'  => $long_url,
        'title'     => format_surveyor_number($surveyor->id)
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $surveyor->id);
        $CI->db->update(db_prefix() . 'clients', [
            'short_link' => $short_link
        ]);
        return $short_link;
    }
    return $long_url;
}

/**
 * Check surveyor restrictions - hash, clientid
 * @param  mixed $id   surveyor id
 * @param  string $hash surveyor hash
 */
function check_surveyor_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('surveyors_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_surveyor_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $surveyor = $CI->surveyors_model->get($id);
    if (!$surveyor || ($surveyor->hash != $hash)) {
        show_404();
    }
    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_surveyor_only_logged_in') == 1) {
            if ($surveyor->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Check if surveyor email template for expiry reminders is enabled
 * @return boolean
 */
function is_surveyors_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'surveyor-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending surveyor expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_surveyors_expiry_reminders_enabled()
{
    return is_surveyors_email_expiry_reminder_enabled() || is_sms_trigger_active(SMS_TRIGGER_SCHEDULE_EXP_REMINDER);
}

/**
 * Return RGBa surveyor state color for PDF documents
 * @param  mixed $state_id current surveyor state
 * @return string
 */
function surveyor_state_color_pdf($state_id)
{
    if ($state_id == 1) {
        $stateColor = '119, 119, 119';
    } elseif ($state_id == 2) {
        // Sent
        $stateColor = '3, 169, 244';
    } elseif ($state_id == 3) {
        //Declines
        $stateColor = '252, 45, 66';
    } elseif ($state_id == 4) {
        //Accepted
        $stateColor = '0, 191, 54';
    } else {
        // Expired
        $stateColor = '255, 111, 0';
    }

    return hooks()->apply_filters('surveyor_state_pdf_color', $stateColor, $state_id);
}

/**
 * Format surveyor state
 * @param  integer  $state
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_surveyor_state($state, $classes = '', $label = true)
{
    $id          = $state;
    $label_class = surveyor_state_color_class($state);
    $state      = surveyor_state_by_id($state);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-state surveyor-state-' . $id . ' surveyor-state-' . $label_class . '">' . $state . '</span>';
    }

    return $state;
}

/**
 * Return surveyor state translated by passed state id
 * @param  mixed $id surveyor state id
 * @return string
 */
function surveyor_state_by_id($id)
{
    $state = '';
    if ($id == 1) {
        $state = _l('surveyor_state_draft');
    } elseif ($id == 2) {
        $state = _l('surveyor_state_sent');
    } elseif ($id == 3) {
        $state = _l('surveyor_state_declined');
    } elseif ($id == 4) {
        $state = _l('surveyor_state_accepted');
    } elseif ($id == 5) {
        // state 5
        $state = _l('surveyor_state_expired');
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $state = _l('not_sent_indicator');
            }
        }
    }

    return hooks()->apply_filters('surveyor_state_label', $state, $id);
}

/**
 * Return surveyor state color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function surveyor_state_color_class($id, $replace_default_by_muted = false)
{
    $class = '';
    if ($id == 1) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'danger';
    } elseif ($id == 4) {
        $class = 'success';
    } elseif ($id == 5) {
        // state 5
        $class = 'warning';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $class = 'default';
                if ($replace_default_by_muted == true) {
                    $class = 'muted';
                }
            }
        }
    }

    return hooks()->apply_filters('surveyor_state_color_class', $class, $id);
}

/**
 * Check if the surveyor id is last invoice
 * @param  mixed  $id surveyorid
 * @return boolean
 */
function is_last_surveyor($id)
{
    $CI = &get_instance();
    $CI->db->select('userid')->from(db_prefix() . 'clients')->order_by('userid', 'desc')->limit(1);
    $query            = $CI->db->get();
    $last_surveyor_id = $query->row()->userid;
    if ($last_surveyor_id == $id) {
        return true;
    }

    return false;
}

/**
 * Format surveyor number based on description
 * @param  mixed $id
 * @return string
 */
function format_surveyor_number($id)
{
    $CI = &get_instance();
    $CI->db->select('datecreated,number,prefix,number_format')->from(db_prefix() . 'clients')->where('userid', $id);
    $surveyor = $CI->db->get()->row();

    if (!$surveyor) {
        return '';
    }

    $number = surveyor_number_format($surveyor->number, $surveyor->number_format, $surveyor->prefix, $surveyor->datecreated);

    return hooks()->apply_filters('format_surveyor_number', $number, [
        'userid'       => $id,
        'surveyor' => $surveyor,
    ]);
}


function surveyor_number_format($number, $format, $applied_prefix, $date)
{
    $originalNumber = $number;
    $prefixPadding  = get_option('number_padding_prefixes');

    if ($format == 1) {
        // Number based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 2) {
        // Year based
        $number = $applied_prefix . date('Y', strtotime($date)) . '.' . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 3) {
        // Number-yy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '-' . date('y', strtotime($date));
    } elseif ($format == 4) {
        // Number-mm-yyyy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '.' . date('m', strtotime($date)) . '.' . date('Y', strtotime($date));
    }

    return hooks()->apply_filters('surveyor_number_format', $number, [
        'format'         => $format,
        'date'           => $date,
        'number'         => $originalNumber,
        'prefix_padding' => $prefixPadding,
    ]);
}

/**
 * Calculate surveyors percent by state
 * @param  mixed $state          surveyor state
 * @return array
 */
function get_surveyors_percent_by_state($state, $program_id = null)
{
    $has_permission_view = has_permission('surveyors', '', 'view');
    $where               = '';

    if (isset($program_id)) {
        $where .= 'program_id=' . get_instance()->db->escape_str($program_id) . ' AND ';
    }
    if (!$has_permission_view) {
        $where .= get_surveyors_where_sql_for_staff(get_staff_user_id());
    }

    $where = trim($where);

    if (endsWith($where, ' AND')) {
        $where = substr_replace($where, '', -3);
    }

    $total_surveyors = total_rows(db_prefix() . 'clients', $where);

    $data            = [];
    $total_by_state = 0;

    if (!is_numeric($state)) {
        if ($state == 'not_sent') {
            $total_by_state = total_rows(db_prefix() . 'clients', 'sent=0 AND state NOT IN(2,3,4)' . ($where != '' ? ' AND (' . $where . ')' : ''));
        }
    } else {
        $whereByStatus = 'state=' . $state;
        if ($where != '') {
            $whereByStatus .= ' AND (' . $where . ')';
        }
        $total_by_state = total_rows(db_prefix() . 'clients', $whereByStatus);
    }

    $percent                 = ($total_surveyors > 0 ? number_format(($total_by_state * 100) / $total_surveyors, 2) : 0);
    $data['total_by_state'] = $total_by_state;
    $data['percent']         = $percent;
    $data['total']           = $total_surveyors;

    return $data;
}

function get_surveyors_where_sql_for_staff($staff_id)
{
    $CI = &get_instance();
    $has_permission_view_own             = has_permission('surveyors', '', 'view_own');
    $allow_staff_view_surveyors_assigned = get_option('allow_staff_view_surveyors_assigned');
    $whereUser                           = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'surveyors.addedfrom=' . $CI->db->escape_str($staff_id) . ' AND ' . db_prefix() . 'surveyors.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "surveyors" AND capability="view_own"))';
        if ($allow_staff_view_surveyors_assigned == 1) {
            $whereUser .= ' OR assigned=' . $CI->db->escape_str($staff_id);
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'assigned=' . $CI->db->escape_str($staff_id);
    }

    return $whereUser;
}
/**
 * Check if staff member have assigned surveyors / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_surveyors($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-surveyors-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'clients', ['assigned' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-surveyors-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if staff member can view surveyor
 * @param  mixed $id surveyor id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_surveyor($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('surveyors', $staff_id, 'view')) {
        return true;
    }

    if(is_client_logged_in()){

        $CI = &get_instance();
        $CI->load->model('surveyors_model');
       
        $surveyor = $CI->surveyors_model->get($id);
        if (!$surveyor) {
            show_404();
        }
        // Do one more check
        if (get_option('view_surveyort_only_logged_in') == 1) {
            if ($surveyor->clientid != get_client_user_id()) {
                show_404();
            }
        }
    
        return true;
    }
    
    $CI->db->select('userid, addedfrom, assigned');
    $CI->db->from(db_prefix() . 'clients');
    $CI->db->where('userid', $id);
    $surveyor = $CI->db->get()->row();

    if ((has_permission('surveyors', $staff_id, 'view_own') && $surveyor->addedfrom == $staff_id)
        || ($surveyor->assigned == $staff_id && get_option('allow_staff_view_surveyors_assigned') == '1')
    ) {
        return true;
    }

    return false;
}


/**
 * Prepare general surveyor pdf
 * @since  Version 1.0.2
 * @param  object $surveyor surveyor as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function surveyor_pdf($surveyor, $tag = '')
{
    return app_pdf('surveyor',  module_libs_path(SCHEDULES_MODULE_NAME) . 'pdf/Surveyor_pdf', $surveyor, $tag);
}


/**
 * Prepare general surveyor pdf
 * @since  Version 1.0.2
 * @param  object $surveyor surveyor as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function surveyor_office_pdf($surveyor, $tag = '')
{
    return app_pdf('surveyor',  module_libs_path(SCHEDULES_MODULE_NAME) . 'pdf/Surveyor_office_pdf', $surveyor, $tag);
}



/**
 * Get items table for preview
 * @param  object  $transaction   e.q. invoice, surveyor from database result row
 * @param  string  $type          type, e.q. invoice, surveyor, proposal
 * @param  string  $for           where the items will be shown, html or pdf
 * @param  boolean $admin_preview is the preview for admin area
 * @return object
 */
function get_surveyor_items_table_data($transaction, $type, $for = 'html', $admin_preview = false)
{
    include_once(module_libs_path(SCHEDULES_MODULE_NAME) . 'Surveyor_items_table.php');

    $class = new Surveyor_items_table($transaction, $type, $for, $admin_preview);

    $class = hooks()->apply_filters('items_table_class', $class, $transaction, $type, $for, $admin_preview);

    if (!$class instanceof App_items_table_template) {
        show_error(get_class($class) . ' must be instance of "Surveyor_items_template"');
    }

    return $class;
}



/**
 * Add new item do database, used for proposals,surveyors,credit notes,invoices
 * This is repetitive action, that's why this function exists
 * @param array $item     item from $_POST
 * @param mixed $rel_id   relation id eq. invoice id
 * @param string $rel_type relation type eq invoice
 */
function add_new_surveyor_item_post($item, $rel_id, $rel_type)
{

    $CI = &get_instance();

    $CI->db->insert(db_prefix() . 'itemable', [
                    'description'      => $item['description'],
                    'long_description' => nl2br($item['long_description']),
                    'qty'              => $item['qty'],
                    'rel_id'           => $rel_id,
                    'rel_type'         => $rel_type,
                    'item_order'       => $item['order'],
                    'unit'             => isset($item['unit']) ? $item['unit'] : 'unit',
                ]);

    $id = $CI->db->insert_id();

    return $id;
}

/**
 * Update surveyor item from $_POST 
 * @param  mixed $item_id item id to update
 * @param  array $data    item $_POST data
 * @param  string $field   field is require to be passed for long_description,rate,item_order to do some additional checkings
 * @return boolean
 */
function update_surveyor_item_post($item_id, $data, $field = '')
{
    $update = [];
    if ($field !== '') {
        if ($field == 'long_description') {
            $update[$field] = nl2br($data[$field]);
        } elseif ($field == 'rate') {
            $update[$field] = number_format($data[$field], get_decimal_places(), '.', '');
        } elseif ($field == 'item_order') {
            $update[$field] = $data['order'];
        } else {
            $update[$field] = $data[$field];
        }
    } else {
        $update = [
            'item_order'       => $data['order'],
            'description'      => $data['description'],
            'long_description' => nl2br($data['long_description']),
            'qty'              => $data['qty'],
            'unit'             => $data['unit'],
        ];
    }

    $CI = &get_instance();
    $CI->db->where('id', $item_id);
    $CI->db->update(db_prefix() . 'itemable', $update);

    return $CI->db->affected_rows() > 0 ? true : false;
}


/**
 * Prepares email template preview $data for the view
 * @param  string $template    template class name
 * @param  mixed $customer_id_or_email customer ID to fetch the primary contact email or email
 * @return array
 */
function surveyor_mail_preview_data($template, $customer_id_or_email, $mailClassParams = [])
{
    $CI = &get_instance();

    if (is_numeric($customer_id_or_email)) {
        $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($customer_id_or_email));
        $email   = $contact ? $contact->email : '';
    } else {
        $email = $customer_id_or_email;
    }

    $CI->load->model('emails_model');

    $data['template'] = $CI->app_mail_template->prepare($email, $template);
    $slug             = $CI->app_mail_template->get_default_property_value('slug', $template, $mailClassParams);

    $data['template_name'] = $slug;

    $template_result = $CI->emails_model->get(['slug' => $slug, 'language' => 'english'], 'row');

    $data['template_system_name'] = $template_result->name;
    $data['template_id']          = $template_result->emailtemplateid;

    $data['template_disabled'] = $template_result->active == 0;

    return $data;
}


/**
 * Function that return full path for upload based on passed type
 * @param  string $type
 * @return string
 */
function get_surveyor_upload_path($type=NULL)
{
   $type = 'surveyor';
   $path = SCHEDULE_ATTACHMENTS_FOLDER;
   
    return hooks()->apply_filters('get_upload_path_by_type', $path, $type);
}

/**
 * Remove and format some common used data for the surveyor feature eq invoice,surveyors etc..
 * @param  array $data $_POST data
 * @return array
 */
function _format_data_surveyor_feature($data)
{
    foreach (_get_surveyor_feature_unused_names() as $u) {
        if (isset($data['data'][$u])) {
            unset($data['data'][$u]);
        }
    }

    if (isset($data['data']['date'])) {
        $data['data']['date'] = to_sql_date($data['data']['date']);
    }

    if (isset($data['data']['open_till'])) {
        $data['data']['open_till'] = to_sql_date($data['data']['open_till']);
    }

    if (isset($data['data']['expirydate'])) {
        $data['data']['expirydate'] = to_sql_date($data['data']['expirydate']);
    }

    if (isset($data['data']['duedate'])) {
        $data['data']['duedate'] = to_sql_date($data['data']['duedate']);
    }

    if (isset($data['data']['clientnote'])) {
        $data['data']['clientnote'] = nl2br_save_html($data['data']['clientnote']);
    }

    if (isset($data['data']['terms'])) {
        $data['data']['terms'] = nl2br_save_html($data['data']['terms']);
    }

    if (isset($data['data']['adminnote'])) {
        $data['data']['adminnote'] = nl2br($data['data']['adminnote']);
    }

    foreach (['country', 'billing_country', 'shipping_country', 'program_id', 'assigned'] as $should_be_zero) {
        if (isset($data['data'][$should_be_zero]) && $data['data'][$should_be_zero] == '') {
            $data['data'][$should_be_zero] = 0;
        }
    }

    return $data;
}


if (!function_exists('format_surveyor_info')) {
    /**
     * Format surveyor info format
     * @param  object $surveyor surveyor from database
     * @param  string $for      where this info will be used? Admin area, HTML preview?
     * @return string
     */
    function format_surveyor_info($surveyor, $for = '')
    {
        $format = get_option('company_info_format');
        $countryCode = '';
        $countryName = '';

        if ($country = get_country($surveyor->country)) {
            $countryCode = $country->iso2;
            $countryName = $country->short_name;
        }

        $surveyorTo = '<b>' . $surveyor->company . '</b>';

        if ($for == 'admin') {
            $hrefAttrs = '';
            $hrefAttrs = ' href="' . admin_url('clients/client/' . $surveyor->userid) . '" data-toggle="tooltip" data-title="' . _l('client') . '"';
            $surveyorTo = '<a' . $hrefAttrs . '>' . $surveyorTo . '</a>';
        }

        if ($for == 'html' || $for == 'admin') {
            $phone = '<a href="tel:' . $surveyor->phone . '">' . $surveyor->phone . '</a>';
            $email = '<a href="mailto:' . $surveyor->email . '">' . $surveyor->email . '</a>';
        }

        $format = _info_format_replace('company_name', $surveyorTo, $format);
        $format = _info_format_replace('address', $surveyor->address . ' ' . $surveyor->city, $format);

        $format = _info_format_replace('city', NULL, $format);
        $format = _info_format_replace('state', $surveyor->state . ' ' . $surveyor->zip, $format);

        $format = _info_format_replace('country_code', $countryCode, $format);
        $format = _info_format_replace('country_name', $countryName, $format);

        $format = _info_format_replace('zip_code', '', $format);
        $format = _info_format_replace('vat_number_with_label', '', $format);

        $whereCF = [];
        if (is_custom_fields_for_customers_portal()) {
            $whereCF['show_on_client_portal'] = 1;
        }
        $customFieldsProposals = get_custom_fields('surveyor', $whereCF);

        foreach ($customFieldsProposals as $field) {
            $value  = get_custom_field_value($surveyor->id, $field['id'], 'surveyor');
            $format = _info_format_custom_field($field['id'], $field['name'], $value, $format);
        }

        // If no custom fields found replace all custom fields merge fields to empty
        $format = _info_format_custom_fields_check($customFieldsProposals, $format);
        $format = _maybe_remove_first_and_last_br_tag($format);

        // Remove multiple white spaces
        $format = preg_replace('/\s+/', ' ', $format);
        $format = trim($format);

        return hooks()->apply_filters('surveyor_info_text', $format, ['surveyor' => $surveyor, 'for' => $for]);
    }
}

/**
 * Unsed $_POST request names, mostly they are used as helper inputs in the form
 * The top function will check all of them and unset from the $data
 * @return array
 */
function _get_surveyor_feature_unused_names()
{
    return [
        'taxname', 'description',
        'currency_symbol', 'price',
        'isedit', 'taxid',
        'long_description', 'unit',
        'rate', 'quantity',
        'item_select', 'tax',
        'billed_tasks', 'billed_expenses',
        'task_select', 'task_id',
        'expense_id', 'repeat_every_custom',
        'repeat_type_custom', 'bill_expenses',
        'save_and_send', 'merge_current_invoice',
        'cancel_merged_invoices', 'invoices_to_merge',
        'tags', 's_prefix', 'save_and_record_payment',
    ];
}

/**
 * When item is removed eq from invoice will be stored in removed_items in $_POST
 * With foreach loop this function will remove the item from database and it's taxes
 * @param  mixed $id       item id to remove
 * @param  string $rel_type item relation eq. invoice, surveyor
 * @return boolena
 */
function handle_removed_surveyor_item_post($id, $rel_type)
{
    $CI = &get_instance();

    $CI->db->where('id', $id);
    $CI->db->where('rel_type', $rel_type);
    $CI->db->delete(db_prefix() . 'itemable');
    if ($CI->db->affected_rows() > 0) {
        return true;
    }

    return false;
}

/**
 * Check if customer has program assigned
 * @param  mixed $customer_id customer id to check
 * @return boolean
 */
function program_has_surveyors($program_id)
{
    $totalProgramsSurveyord = total_rows(db_prefix() . 'clients', 'program_id=' . get_instance()->db->escape_str($program_id));

    return ($totalProgramsSurveyord > 0 ? true : false);
}


function is_staff_related_to_surveyor($client_id){
    $CI = &get_instance();
    $CI->db->where('staffid', get_staff_user_id());
    $CI->db->where('client_id', $client_id);
    $result = $CI->db->get(db_prefix() . 'staff')->result();
    if (count($result) > 0) {
        return true;
    }

    return false;    
}