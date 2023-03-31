<?php

defined('BASEPATH') or exit('No direct script access allowed');
$is_surveyor=1;
$staff_id = get_staff_user_id();

$hasPermissionDelete = has_permission('customers', '', 'delete');

$custom_fields = get_table_custom_fields('customers');
$this->ci->db->query("SET sql_mode = ''");

$aColumns = [
    '1',
    'company',
    'company',
    'lastname',
    'email',
    db_prefix().'clients.phonenumber as phonenumber',
    db_prefix().'clients.active',
];

$sIndexColumn = 'userid';
$sTable       = db_prefix().'clients';
$where        = [];
// Add blank where all filter can be stored
$filter = [];

$join = [
    'LEFT JOIN '.db_prefix().'staff ON '.db_prefix().'staff.client_id='.db_prefix().'clients.userid AND '.db_prefix().'staff.is_primary=1',
];

$join = hooks()->apply_filters('customers_table_sql_join', $join);

// Filter by custom groups
$groups   = $this->ci->clients_model->get_groups();
$groupIds = [];
foreach ($groups as $group) {
    if ($this->ci->input->post('customer_group_' . $group['id'])) {
        array_push($groupIds, $group['id']);
    }
}

if (count($groupIds) > 0) {
    array_push($filter, 'AND '.db_prefix().'clients.userid IN (SELECT customer_id FROM '.db_prefix().'customer_groups WHERE groupid IN (' . implode(', ', $groupIds) . '))');
}

$countries  = $this->ci->clients_model->get_clients_distinct_countries();
$countryIds = [];
foreach ($countries as $country) {
    if ($this->ci->input->post('country_' . $country['country_id'])) {
        array_push($countryIds, $country['country_id']);
    }
}

if (count($countryIds) > 0) {
    array_push($filter, 'AND country IN (' . implode(',', $countryIds) . ')');
}


// Filter by programs
$programStatusIds = [];
$program_states = [];
if(function_exists('get_program_states')){
    $program_states = get_program_states();
}

foreach ($program_states as $state) {
    if ($this->ci->input->post('programs_' . $state['id'])) {
        array_push($programStatusIds, $state['id']);
    }
}
if (count($programStatusIds) > 0) {
    array_push($filter, 'AND '.db_prefix().'clients.userid IN (SELECT clientid FROM '.db_prefix().'programs WHERE state IN (' . implode(', ', $programStatusIds) . '))');
}


// Filter by proposals
$customAdminIds = [];
foreach ($this->ci->clients_model->get_customers_admin_unique_ids() as $cadmin) {
    if ($this->ci->input->post('responsible_admin_' . $cadmin['staff_id'])) {
        array_push($customAdminIds, $cadmin['staff_id']);
    }
}

if (count($customAdminIds) > 0) {
    array_push($filter, 'AND '.db_prefix().'clients.userid IN (SELECT customer_id FROM '.db_prefix().'customer_admins WHERE staff_id IN (' . implode(', ', $customAdminIds) . '))');
}

if ($this->ci->input->post('requires_registration_confirmation')) {
    array_push($filter, 'AND '.db_prefix().'clients.registration_confirmed=0');
}

if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}

if (!has_permission('surveyors', '', 'view')) {
    array_push($where, 'AND '.db_prefix().'clients.userid IN (SELECT customer_id FROM '.db_prefix().'customer_admins WHERE staff_id=' . get_staff_user_id() . ')');
}

if ($this->ci->input->post('exclude_inactive')) {
    array_push($where, 'AND ('.db_prefix().'clients.active = 1 OR '.db_prefix().'clients.active=0 AND registration_confirmed = 0)');
}

if ($this->ci->input->post('my_customers')) {
    array_push($where, 'AND '.db_prefix().'clients.userid IN (SELECT customer_id FROM '.db_prefix().'customer_admins WHERE staff_id=' . get_staff_user_id() . ') ');
}

array_push($where, 'AND ('.db_prefix().'clients.is_surveyor = '.$is_surveyor.') ');
$institution_id = get_institution_id_by_staff_id($staff_id);
if(is_surveyor_staff($staff_id) && get_option('allow_inspector_staff_only_view_surveyors_in_same_institution')){
    array_push($where, 'AND '.db_prefix().'clients.institution_id = '. $institution_id);    
}


// print_r($is_surveyor); exit;

$aColumns = hooks()->apply_filters('customers_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

// print_r($result);exit;

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix().'clients.userid as userid',
    'firstname',
    db_prefix().'clients.zip as zip',
    'registration_confirmed',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Bulk actions
    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['userid'] . '"><label></label></div>';
    // User id
    $row[] = $aRow['company'];

    // Company
    $primary_contact  = $aRow['company'];
    $isPerson = false;

    if ($primary_contact == '') {
        $primary_contact  = _l('no_company_view_profile');
        $isPerson = true;
    }

//    $row[] = '<a href="' . admin_url('perusahaan/list_perusahaan/' . $aRow[db_prefix() . 'perusahaan.id'] .'#/'. $aRow[db_prefix() . 'perusahaan.id']) . '" onclick="init_perusahaan(' . $aRow[db_prefix() . 'perusahaan.id'] . '); return false;">' . $aRow['subject'] . '</a>';

    $url = admin_url('surveyors/list_surveyors/#' . $aRow['userid']);

    if ($isPerson && $aRow['userid']) {
        $url .= '?contactid=' . $aRow['userid'];
    }

    $primary_contact = '<a href="' . admin_url('surveyors/list_surveyors/#' . $aRow['userid']) . '" onclick="init_inspector(' . $aRow['userid'] . '); return false;">' . $aRow['firstname'] .' '. $aRow['lastname'] .'</a>';
    $primary_contact .= '<div class="row-options">';
    $primary_contact .= '<a href="' . $url . '">' . _l('view') . '</a>';

    if ($aRow['registration_confirmed'] == 0 && is_admin()) {
        $primary_contact .= ' | <a href="' . admin_url('surveyors/confirm_registration/' . $aRow['userid']) . '" class="text-success bold">' . _l('confirm_registration') . '</a>';
    }
    if ($hasPermissionDelete) {
        $primary_contact .= ' | <a href="' . admin_url('surveyors/delete/' . $aRow['userid']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }

    $primary_contact .= '</div>';

    $row[] = $primary_contact;

    // Primary contact
    $row[] = ($aRow['userid'] ? '<a href="' . admin_url('surveyors/client/' . $aRow['userid'] . '?contactid=' . $aRow['userid']) . '" target="_blank">' . $aRow['firstname'] . ' ' . $aRow['lastname'] . '</a>' : '');

    // Primary contact email
    $row[] = ($aRow['email'] ? '<a href="mailto:' . $aRow['email'] . '">' . $aRow['email'] . '</a>' : '');

    // Primary contact phone
    $row[] = ($aRow['phonenumber'] ? '<a href="tel:' . $aRow['phonenumber'] . '">' . $aRow['phonenumber'] . '</a>' : '');

    // Toggle active/inactive customer
    $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('customer_active_inactive_help') . '">
    <input type="checkbox"' . ($aRow['registration_confirmed'] == 0 ? ' disabled' : '') . ' data-switch-url="' . admin_url() . 'surveyors/change_client_state" name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['userid'] . '" data-id="' . $aRow['userid'] . '" ' . ($aRow[db_prefix().'clients.active'] == 1 ? 'checked' : '') . '>
    <label class="onoffswitch-label" for="' . $aRow['userid'] . '"></label>
    </div>';

    // For exporting
    $toggleActive .= '<span class="hide">' . ($aRow[db_prefix().'clients.active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

    $row[] = $toggleActive;

    $row['DT_RowClass'] = 'has-row-options';

    if ($aRow['registration_confirmed'] == 0) {
        $row['DT_RowClass'] .= ' alert-info requires-confirmation';
        $row['Data_Title']  = _l('customer_requires_registration_confirmation');
        $row['Data_Toggle'] = 'tooltip';
    }

    $row = hooks()->apply_filters('customers_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}
