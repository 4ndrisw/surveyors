<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
   <div class="row">
      <div class="col-md-3">
         <div class="mbot30">
            <div class="surveyor-html-logo">
               <?php echo get_dark_company_logo(); ?>
            </div>
         </div>
      </div>
      <div class="clearfix"></div>
   </div>
   <div class="top" data-sticky data-sticky-class="preview-sticky-header">
      <div class="container preview-sticky-container">
         <div class="row">
            <div class="col-md-12">
               <div class="col-md-3">
                  <h3 class="bold no-mtop surveyor-html-number no-mbot">
                     <span class="sticky-visible hide">
                     <?php echo format_surveyor_number($surveyor->id); ?>
                     </span>
                  </h3>
                  <h4 class="surveyor-html-state mtop7">
                     <?php echo format_surveyor_state($surveyor->state,'',true); ?>
                  </h4>
               </div>
               <div class="col-md-9">
                  <?php echo form_open(site_url('surveyors/office_pdf/'.$surveyor->id), array('class'=>'pull-right action-button')); ?>
                  <button type="submit" name="surveyorpdf" class="btn btn-default action-button download mright5 mtop7" value="surveyorpdf">
                  <i class="fa fa-file-pdf-o"></i>
                  <?php echo _l('clients_invoice_html_btn_download'); ?>
                  </button>
                  <?php echo form_close(); ?>
                  <?php if(is_client_logged_in() || is_staff_member()){ ?>
                  <a href="<?php echo site_url('clients/surveyors/'); ?>" class="btn btn-default pull-right mright5 mtop7 action-button go-to-portal">
                  <?php echo _l('client_go_to_dashboard'); ?>
                  </a>
                  <?php } ?>
               </div>
            </div>
            <div class="clearfix"></div>
         </div>
      </div>
   </div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
   <div class="panel-body">
      <div class="col-md-10 col-md-offset-1">
         <div class="row mtop20">
            <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
               <h4 class="bold surveyor-html-number"><?php echo format_surveyor_number($surveyor->id); ?></h4>
               <address class="surveyor-html-company-info">
                  <?php echo format_organization_info(); ?>
               </address>
            </div>
            <div class="col-sm-6 text-right transaction-html-info-col-right">
               <span class="bold surveyor_to"><?php echo _l('surveyor_office_to'); ?>:</span>
               <address class="surveyor-html-customer-billing-info">
                  <?php echo format_office_info($surveyor->office, 'office', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($surveyor->include_shipping == 1 && $surveyor->show_shipping_on_surveyor == 1){ ?>
               <span class="bold surveyor_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="surveyor-html-customer-shipping-info">
                  <?php echo format_office_info($surveyor->office, 'office', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>
         </div>
         <div class="row">

            <div class="col-sm-12 text-left transaction-html-info-col-left">
               <p class="surveyor_to"><?php echo _l('surveyor_opening'); ?>:</p>
               <span class="surveyor_to"><?php echo _l('surveyor_client'); ?>:</span>
               <address class="surveyor-html-customer-billing-info">
                  <?php echo format_customer_info($surveyor, 'surveyor', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($surveyor->include_shipping == 1 && $surveyor->show_shipping_on_surveyor == 1){ ?>
               <span class="bold surveyor_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="surveyor-html-customer-shipping-info">
                  <?php echo format_customer_info($surveyor, 'surveyor', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>



            <div class="col-md-6">
               <div class="container-fluid">
                  <?php if(!empty($surveyor_members)){ ?>
                     <strong><?= _l('surveyor_members') ?></strong>
                     <ul class="surveyor_members">
                     <?php 
                        foreach($surveyor_members as $member){
                          echo ('<li style="list-style:auto" class="member">' . $member['firstname'] .' '. $member['lastname'] .'</li>');
                         }
                     ?>
                     </ul>
                  <?php } ?>
               </div>
            </div>
            <div class="col-md-6 text-right">
               <p class="no-mbot surveyor-html-date">
                  <span class="bold">
                  <?php echo _l('surveyor_data_date'); ?>:
                  </span>
                  <?php echo _d($surveyor->date); ?>
               </p>
               <?php if(!empty($surveyor->expirydate)){ ?>
               <p class="no-mbot surveyor-html-expiry-date">
                  <span class="bold"><?php echo _l('surveyor_data_expiry_date'); ?></span>:
                  <?php echo _d($surveyor->expirydate); ?>
               </p>
               <?php } ?>
               <?php if(!empty($surveyor->reference_no)){ ?>
               <p class="no-mbot surveyor-html-reference-no">
                  <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                  <?php echo $surveyor->reference_no; ?>
               </p>
               <?php } ?>
               <?php if($surveyor->program_id != 0 && get_option('show_program_on_surveyor') == 1){ ?>
               <p class="no-mbot surveyor-html-program">
                  <span class="bold"><?php echo _l('program'); ?>:</span>
                  <?php echo get_program_name_by_id($surveyor->program_id); ?>
               </p>
               <?php } ?>
               <?php $pdf_custom_fields = get_custom_fields('surveyor',array('show_on_pdf'=>1,'show_on_client_portal'=>1));
                  foreach($pdf_custom_fields as $field){
                    $value = get_custom_field_value($surveyor->id,$field['id'],'surveyor');
                    if($value == ''){continue;} ?>
               <p class="no-mbot">
                  <span class="bold"><?php echo $field['name']; ?>: </span>
                  <?php echo $value; ?>
               </p>
               <?php } ?>
            </div>
         </div>
         <div class="row">
            <div class="col-md-12">
               <div class="table-responsive">
                  <?php
                     $items = get_surveyor_items_table_data($surveyor, 'surveyor');
                     echo $items->table();
                  ?>
               </div>
            </div>


            <div class="row mtop25">
               <div class="col-md-12">
                  <div class="col-md-6 text-center">
                     <div class="bold"><?php echo get_option('invoice_company_name'); ?></div>
                     <div class="qrcode text-center">
                        <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_surveyor_upload_path('surveyor').$surveyor->id.'/assigned-'.$surveyor_number.'.png')); ?>" class="img-responsive center-block surveyor-assigned" alt="surveyor-<?= $surveyor->id ?>">
                     </div>
                     <div class="assigned">
                     <?php if($surveyor->assigned != 0 && get_option('show_assigned_on_surveyors') == 1){ ?>
                        <?php echo get_staff_full_name($surveyor->assigned); ?>
                     <?php } ?>

                     </div>
                  </div>
                     <div class="col-md-6 text-center">
                       <div class="bold"><?php echo $client_company; ?></div>
                       <?php if(!empty($surveyor->signature)) { ?>
                           <div class="bold">
                              <p class="no-mbot"><?php echo _l('surveyor_signed_by') . ": {$surveyor->acceptance_firstname} {$surveyor->acceptance_lastname}"?></p>
                              <p class="no-mbot"><?php echo _l('surveyor_signed_date') . ': ' . _dt($surveyor->acceptance_date) ?></p>
                              <p class="no-mbot"><?php echo _l('surveyor_signed_ip') . ": {$surveyor->acceptance_ip}"?></p>
                           </div>
                           <p class="bold"><?php echo _l('document_customer_signature_text'); ?>
                           <?php if($surveyor->signed == 1 && has_permission('surveyors','','delete')){ ?>
                              <a href="<?php echo admin_url('surveyors/clear_signature/'.$surveyor->id); ?>" data-toggle="tooltip" title="<?php echo _l('clear_signature'); ?>" class="_delete text-danger">
                                 <i class="fa fa-remove"></i>
                              </a>
                           <?php } ?>
                           </p>
                           <div class="customer_signature text-center">
                              <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_surveyor_upload_path('surveyor').$surveyor->id.'/'.$surveyor->signature)); ?>" class="img-responsive center-block surveyor-signature" alt="surveyor-<?= $surveyor->id ?>">
                           </div>
                       <?php } ?>
                     </div>
               </div>
            </div>

         </div>
      </div>
   </div>
</div>

