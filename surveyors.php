<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Surveyors
Description: Default module for defining surveyors
Version: 1.0.1
Requires at least: 2.3.*
*/

define('SURVEYORS_MODULE_NAME', 'surveyors');
define('SURVEYOR_ATTACHMENTS_FOLDER', 'uploads/surveyors/');

hooks()->add_filter('before_surveyor_updated', '_format_data_surveyor_feature');
hooks()->add_filter('before_surveyor_added', '_format_data_surveyor_feature');

hooks()->add_action('after_cron_run', 'surveyors_notification');
hooks()->add_action('admin_init', 'surveyors_module_init_menu_items');
hooks()->add_action('admin_init', 'surveyors_permissions');
hooks()->add_action('admin_init', 'surveyors_settings_tab');
hooks()->add_action('clients_init', 'surveyors_clients_area_menu_items');

hooks()->add_action('staff_member_deleted', 'surveyors_staff_member_deleted');

hooks()->add_filter('migration_tables_to_replace_old_links', 'surveyors_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'surveyors_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'surveyors_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'surveyors_add_dashboard_widget');
hooks()->add_filter('module_surveyors_action_links', 'module_surveyors_action_links');


function surveyors_add_dashboard_widget($widgets)
{
    /*
    $widgets[] = [
        'path'      => 'surveyors/widgets/surveyor_this_week',
        'container' => 'left-8',
    ];
    $widgets[] = [
        'path'      => 'surveyors/widgets/program_not_scheduled',
        'container' => 'left-8',
    ];
    */

    return $widgets;
}


function surveyors_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'surveyors', [
            'staff_id' => $data['transfer_data_to'],
        ]);
}

function surveyors_global_search_result_output($output, $data)
{
    if ($data['type'] == 'surveyors') {
        $output = '<a href="' . admin_url('surveyors/surveyor/' . $data['result']['id']) . '">' . format_surveyor_number($data['result']['id']) . '</a>';
    }

    return $output;
}

function surveyors_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('surveyors', '', 'view')) {

        // surveyors
        $CI->db->select()
           ->from(db_prefix() . 'surveyors')
           ->like(db_prefix() . 'surveyors.formatted_number', $q)->limit($limit);
        
        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'surveyors',
                'search_heading' => _l('surveyors'),
            ];
        
        if(isset($result[0]['result'][0]['id'])){
            return $result;
        }

        // surveyors
        $CI->db->select()->from(db_prefix() . 'surveyors')->like(db_prefix() . 'clients.company', $q)->or_like(db_prefix() . 'surveyors.formatted_number', $q)->limit($limit);
        $CI->db->join(db_prefix() . 'clients',db_prefix() . 'surveyors.clientid='.db_prefix() .'clients.userid', 'left');
        $CI->db->order_by(db_prefix() . 'clients.company', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'surveyors',
                'search_heading' => _l('surveyors'),
            ];
    }

    return $result;
}

function surveyors_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'surveyors',
                'field' => 'description',
            ];

    return $tables;
}

function surveyors_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'edit_own'   => _l('permission_edit_own'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('surveyors', $capabilities, _l('surveyors'));
}


/**
* Register activation module hook
*/
register_activation_hook(SURVEYORS_MODULE_NAME, 'surveyors_module_activation_hook');

function surveyors_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register deactivation module hook
*/
register_deactivation_hook(SURVEYORS_MODULE_NAME, 'surveyors_module_deactivation_hook');

function surveyors_module_deactivation_hook()
{

     log_activity( 'Hello, world! . surveyors_module_deactivation_hook ' );
}

//hooks()->add_action('deactivate_' . $module . '_module', $function);

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(SURVEYORS_MODULE_NAME, [SURVEYORS_MODULE_NAME]);

/**
 * Init surveyors module menu items in setup in admin_init hook
 * @return null
 */
function surveyors_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('surveyor'),
            'url'        => 'surveyors',
            'permission' => 'surveyors',
            'position'   => 57,
            ]);

    if (has_permission('surveyors', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('surveyors', [
                'slug'     => 'surveyors-tracking',
                'name'     => _l('surveyors'),
                'icon'     => 'fa-solid fa-building-shield',
                'href'     => admin_url('surveyors'),
                'position' => 12,
        ]);
    }
}

function surveyors_contact_permission($permissions){
        $item = array(
            'id'         => 8,
            'name'       => _l('surveyors'),
            'short_name' => 'surveyors',
        );
        $permissions[] = $item;
      return $permissions;
}

function module_surveyors_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=surveyors') . '">' . _l('settings') . '</a>';

    return $actions;
}

function surveyors_clients_area_menu_items()
{   
    // Show menu item only if client is logged in
    if (is_client_logged_in() && has_contact_permission('surveyors')) {
        add_theme_menu_item('surveyors', [
                    'name'     => _l('surveyors'),
                    'href'     => site_url('surveyors/list'),
                    'position' => 15,
                    'icon'     => 'fa-solid fa-building-shield',
        ]);
    }
}

/**
 * [perfex_dark_theme_settings_tab net menu item in setup->settings]
 * @return void
 */
function surveyors_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('surveyors', [
        'name'     => _l('settings_group_surveyors'),
        //'view'     => module_views_path(SURVEYORS_MODULE_NAME, 'admin/settings/includes/surveyors'),
        'view'     => 'surveyors/surveyors_settings',
        'position' => 51,
        'icon'     => 'fa-solid fa-building-shield',
    ]);
}

$CI = &get_instance();
$CI->load->helper(SURVEYORS_MODULE_NAME . '/surveyors');
if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='surveyors') || $CI->uri->segment(1)=='surveyors'){
    $CI->app_css->add(SURVEYORS_MODULE_NAME.'-css', base_url('modules/'.SURVEYORS_MODULE_NAME.'/assets/css/'.SURVEYORS_MODULE_NAME.'.css'));
    $CI->app_scripts->add(SURVEYORS_MODULE_NAME.'-js', base_url('modules/'.SURVEYORS_MODULE_NAME.'/assets/js/'.SURVEYORS_MODULE_NAME.'.js'));
}

if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='staff') && $CI->uri->segment(3)=='edit_provile'){
    $CI->app_css->add(SURVEYORS_MODULE_NAME.'-css', base_url('modules/'.SURVEYORS_MODULE_NAME.'/assets/css/'.SURVEYORS_MODULE_NAME.'.css'));
}

