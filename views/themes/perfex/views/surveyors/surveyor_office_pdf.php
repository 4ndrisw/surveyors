<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('surveyor_office_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . str_replace("SCH","SCH-UPT",$surveyor_number) . '</b>';

if (get_option('show_state_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . surveyor_state_color_pdf($state) . ');text-transform:uppercase;">' . format_surveyor_state($state, '', false) . '</span>';
}

// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(8);

$organization_info = '<div style="color:#424242;">';
    $organization_info .= format_organization_info();
$organization_info .= '</div>';

// Surveyor to
$surveyor_info = '<b>' . _l('surveyor_office_to') . '</b>';
$surveyor_info .= '<div style="color:#424242;">';
$surveyor_info .= format_office_info($surveyor->office, 'surveyor', 'billing');
$surveyor_info .= '</div>';

$left_info  = $swap == '1' ? $surveyor_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $surveyor_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// Surveyor to
$left_info ='<p>' . _l('surveyor_opening') . ',</p>';
$left_info .= _l('surveyor_client');
$left_info .= '<div style="color:#424242;">';
$left_info .= format_customer_info($surveyor, 'surveyor', 'billing');
$left_info .= '</div>';

$right_info = '';

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 1) - $dimensions['lm']);

$organization_info = '<strong>'. _l('surveyor_members') . ': </strong><br />';

$CI = &get_instance();
$CI->load->model('surveyors_model');
$surveyor_members = $CI->surveyors_model->get_surveyor_members($surveyor->id,true);
$i=1;
foreach($surveyor_members as $member){
  $organization_info .=  $i.'. ' .$member['firstname'] .' '. $member['lastname']. '<br />';
  $i++;
}

$surveyor_info = '<br />' . _l('surveyor_data_date') . ': ' . _d($surveyor->date) . '<br />';


if ($surveyor->program_id != 0 && get_option('show_program_on_surveyor') == 1) {
    $surveyor_info .= _l('program') . ': ' . get_program_name_by_id($surveyor->program_id) . '<br />';
}


$left_info  = $swap == '1' ? $surveyor_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $surveyor_info;

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_surveyor_items_table_data($surveyor, 'surveyor', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->SetFont($font_name, '', $font_size);

$assigned_path = <<<EOF
        <img width="150" height="150" src="$surveyor->assigned_path">
    EOF;    
$assigned_info = '<div style="text-align:center;">';
    $assigned_info .= get_option('invoice_company_name') . '<br />';
    $assigned_info .= $assigned_path . '<br />';

if ($surveyor->assigned != 0 && get_option('show_assigned_on_surveyors') == 1) {
    $assigned_info .= get_staff_full_name($surveyor->assigned);
}
$assigned_info .= '</div>';

$acceptance_path = <<<EOF
    <img src="$surveyor->acceptance_path">
EOF;
$client_info = '<div style="text-align:center;">';
    $client_info .= $surveyor->client_company .'<br />';

if ($surveyor->signed != 0) {
    $client_info .= _l('surveyor_signed_by') . ": {$surveyor->acceptance_firstname} {$surveyor->acceptance_lastname}" . '<br />';
    $client_info .= _l('surveyor_signed_date') . ': ' . _dt($surveyor->acceptance_date_string) . '<br />';
    $client_info .= _l('surveyor_signed_ip') . ": {$surveyor->acceptance_ip}" . '<br />';

    $client_info .= $acceptance_path;
    $client_info .= '<br />';
}
$client_info .= '</div>';


$left_info  = $swap == '1' ? $client_info : $assigned_info;
$right_info = $swap == '1' ? $assigned_info : $client_info;
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(2);   
$companyname = get_option('companyname');
$pdf->writeHTMLCell('', '', '', '', _l('surveyor_crm_info', $companyname), 0, 1, false, true, 'L', true);
