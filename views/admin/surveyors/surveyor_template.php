<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s accounting-template surveyor">
   <div class="panel-body">
      <?php if(isset($surveyor)){ ?>
      <?php echo format_surveyor_state($surveyor->active); ?>
      <hr class="hr-panel-heading" />
      <?php } ?>
      <div class="row">
          <?php if (isset($surveyor_request_id) && $surveyor_request_id != '') {
              echo form_hidden('surveyor_request_id',$surveyor_request_id);
          }
          ?>
         <div class="col-md-6">
            <div class="f_client_id">
             <div class="form-group name-placeholder">
               <div class="row">
                 <div class="col-md-12">
                    <?php $value = (isset($surveyor) ? $surveyor->company : ''); ?>
                    <?php $attrs = (isset($surveyor) ? array() : array('autofocus' => true)); ?>
                    <?php echo render_input('company', 'pjk3', $value, 'text', $attrs); ?>
                    <div id="company_exists_info" class="hide"></div>
                  </div>
                 </div>
              </div>
            </div>

            <?php
               $next_surveyor_number = get_option('next_surveyor_number');
               $format = get_option('surveyor_number_format');

                if(isset($surveyor)){
                  $format = $surveyor->number_format;
                }

               $prefix = get_option('surveyor_prefix');

               if ($format == 1) {
                 $__number = $next_surveyor_number;
                 if(isset($surveyor)){
                   $__number = $surveyor->number;
                   $prefix = '<span id="prefix">' . $surveyor->prefix . '</span>';
                 }
               } else if($format == 2) {
                 if(isset($surveyor)){
                   $__number = $surveyor->number;
                   $prefix = $surveyor->prefix;
                   $prefix = '<span id="prefix">'. $prefix . '</span><span id="prefix_year">' . date('Y',strtotime($surveyor->dateactivated)).'</span>/';
                 } else {
                   $__number = $next_surveyor_number;
                   $prefix = $prefix.'<span id="prefix_year">'.date('Y').'</span>/';
                 }
               } else if($format == 3) {
                  if(isset($surveyor)){
                   $yy = date('y',strtotime($surveyor->dateactivated));
                   $__number = $surveyor->number;
                   $prefix = '<span id="prefix">'. $surveyor->prefix . '</span>';
                 } else {
                  $yy = date('y');
                  $__number = $next_surveyor_number;
                }
               } else if($format == 4) {
                  if(isset($surveyor)){
                   $yyyy = date('Y',strtotime($surveyor->dateactivated));
                   $mm = date('m',strtotime($surveyor->dateactivated));
                   $__number = $surveyor->number;
                   $prefix = '<span id="prefix">'. $surveyor->prefix . '</span>';
                 } else {
                  $yyyy = date('Y');
                  $mm = date('m');
                  $__number = $next_surveyor_number;
                }
               }

               $_surveyor_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
               $isedit = isset($surveyor) ? 'true' : 'false';
               $data_original_number = isset($surveyor) ? $surveyor->number : 'false';
               ?>
            <div class="form-group">
               <label for="number"><?php echo _l('surveyor_add_edit_number'); ?></label>
               <div class="input-group">
                  <span class="input-group-addon">
                  <?php if(isset($surveyor)){ ?>
                  <a href="#" onclick="return false;" data-toggle="popover" data-container='._transaction_form' data-html="true" data-content="<label class='control-label'><?php echo _l('settings_sales_surveyor_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?php echo $surveyor->prefix; ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?php echo admin_url('surveyors/update_number_settings/'.$surveyor->userid); ?>' class='btn btn-info btn-block mtop15'><?php echo _l('submit'); ?></button>"><i class="fa fa-cog"></i></a>
                   <?php }
                    echo $prefix;
                  ?>
                 </span>
                  <input type="text" name="number" class="form-control" value="<?php echo $_surveyor_number; ?>" data-isedit="<?php echo $isedit; ?>" data-original-number="<?php echo $data_original_number; ?>">
                  <?php if($format == 3) { ?>
                  <span class="input-group-addon">
                     <span id="prefix_year" class="format-n-yy"><?php echo $yy; ?></span>
                  </span>
                  <?php } else if($format == 4) { ?>
                   <span class="input-group-addon">
                     <span id="prefix_month" class="format-mm-yyyy"><?php echo $mm; ?></span>
                     /
                     <span id="prefix_year" class="format-mm-yyyy"><?php echo $yyyy; ?></span>
                  </span>
                  <?php } ?>
               </div>
            </div>

            <div class="row">
               <div class="col-md-6">
                 <?php $value = (isset($surveyor) ? $surveyor->siup : ''); ?>
                 <?php echo render_input('siup','siup',$value); ?>
               </div>
               <div class="col-md-6">
                 <?php $value = (isset($surveyor) ? $surveyor->vat : ''); ?>
                 <?php echo render_input('vat','vat',$value); ?>
               </div>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <?php if (get_option('pjk3_use_bpjs_kesehatan_field') == 1) {
                     $value = (isset($surveyor) ? $surveyor->bpjs_kesehatan : '');
                     echo render_input('bpjs_kesehatan', 'bpjs_kesehatan', $value);
                  } ?>
               </div>
               <div class="col-md-6">
                  <?php if (get_option('pjk3_use_bpjs_ketenagakerjaan_field') == 1) {
                     $value = (isset($surveyor) ? $surveyor->bpjs_ketenagakerjaan : '');
                     echo render_input('bpjs_ketenagakerjaan', 'bpjs_ketenagakerjaan', $value);
                  } ?>
               </div>
            </div>

            <div class="row">
               <div class="col-md-6">
                  <?php $value = (isset($surveyor) ? $surveyor->phone : ''); ?>
                  <?php echo render_input('phone', 'client_phone', $value); ?>
               </div>
               <div class="col-md-6">
                  <?php if (get_option('disable_language') == 0) { ?>
                     <div class="form-group select-placeholder">
                        <label for="default_language" class="control-label"><?php echo _l('localization_default_language'); ?>
                        </label>
                        <select name="default_language" id="default_language" class="form-control selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                           <option value=""><?php echo _l('system_default_string'); ?></option>
                           <?php foreach ($this->app->get_available_languages() as $availableLanguage) {
                                 $selected = '';
                                 if (isset($surveyor)) {
                                    if ($surveyor->default_language == $availableLanguage) {
                                       $selected = 'selected';
                                    }
                                 }
                                 ?>
                              <option value="<?php echo $availableLanguage; ?>" <?php echo $selected; ?>><?php echo ucfirst($availableLanguage); ?></option>
                           <?php } ?>
                        </select>
                     </div>
                  <?php } ?>
               </div>
             </div>

            <div class="row">
              <div class="col-md-6">
                 <div class="form-group select-placeholder">
                    <?php if ((isset($surveyor) && empty($surveyor->website)) || !isset($surveyor)) {
                     $value = (isset($surveyor) ? $surveyor->website : '');
                     echo render_input('website', 'client_website', $value);
                    } else { ?>
                     <div class="form-group">
                        <label for="website"><?php echo _l('client_website'); ?></label>
                        <div class="input-group">
                           <input type="text" name="website" id="website" value="<?php echo $surveyor->website; ?>" class="form-control">
                           <div class="input-group-addon">
                              <span><a href="<?php echo maybe_add_http($surveyor->website); ?>" target="_blank" tabindex="-1"><i class="fa fa-globe"></i></a></span>
                           </div>
                        </div>
                     </div>
                  <?php }?>
                 </div>
              </div>

              <div class="col-md-6">
                 <div class="form-group select-placeholder">
                    <label class="control-label"><?php echo _l('surveyor_state'); ?></label>
                    <select class="selectpicker display-block mbot15" name="state" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                       <?php foreach($surveyor_states as $state){ ?>
                       <option value="<?php echo $state; ?>" <?php if(isset($surveyor) && $surveyor->state == $state){echo 'selected';} ?>><?php echo format_surveyor_state($state,'',false); ?></option>
                       <?php } ?>
                    </select>
                 </div>
              </div>

            </div>
            <div class="clearfix mbot15"></div>
            <?php $rel_id = (isset($surveyor) ? $surveyor->userid : false); ?>
            <?php
                  if(isset($custom_fields_rel_transfer)) {
                      $rel_id = $custom_fields_rel_transfer;
                  }
             ?>
            <?php //echo render_custom_fields('surveyor',$rel_id); ?>
         </div>
         <div class="col-md-6">
            <div class="no-shadow">
              <div class="row">
                 <div class="col-md-12">
                    <?php $value = (isset($surveyor) ? $surveyor->address : ''); ?>
                    <?php echo render_textarea('address', 'client_address', $value); ?>
                    <?php $value = (isset($surveyor) ? $surveyor->city : ''); ?>
                    <?php echo render_input('city', 'client_city', $value); ?>
                    <?php $value = (isset($surveyor) ? $surveyor->state : ''); ?>
                    <?php echo render_input('state', 'client_state', $value); ?>
                    <?php $value = (isset($surveyor) ? $surveyor->zip : ''); ?>
                    <?php echo render_input('zip', 'client_postal_code', $value); ?>
                    <?php $countries = get_all_countries();
                    $customer_default_country = get_option('customer_default_country');
                    $selected = (isset($surveyor) ? $surveyor->country : $customer_default_country);
                    echo render_select('country', $countries, array('country_id', array('short_name')), 'clients_country', $selected, array('data-none-selected-text' => _l('dropdown_non_selected_tex')));
                    ?>

                 </div>
              </div>
            </div>
         </div>
      </div>
   </div>
   <div class="row">
    <div class="col-md-12 mtop5">
      <div class="panel-body bottom-transaction">
        <div class="btn-bottom-toolbar text-right">
          <div class="btn-group dropup">
            <button type="button" class="btn-tr btn btn-info surveyor-form-submit transaction-submit">
              <?php echo _l('submit'); ?>
            </button>
          <button type="button"
            class="btn btn-info dropdown-toggle"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-right width200">
            <li>
              <a href="#" class="surveyor-form-submit save-and-send transaction-submit">
                <?php echo _l('save_and_send'); ?>
              </a>
            </li>
            <?php if(!isset($surveyor)) { ?>
              <li>
                <a href="#" class="surveyor-form-submit save-and-send-later transaction-submit">
                  <?php echo _l('save_and_send_later'); ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        </div>
      </div>
    </div>
    <div class="btn-bottom-pusher"></div>
  </div>
</div>
</div>
