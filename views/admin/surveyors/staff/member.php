<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php if (isset($member)) { ?>
        <?php $this->load->view('admin/surveyors/staff/stats'); ?>
        <div class="member">
            <?php echo form_hidden('isedit'); ?>
            <?php echo form_hidden('addedfrom', get_staff_user_id()); ?>
            <?php echo form_hidden('memberid', $member->staffid); ?>
        </div>
        <?php } ?>
        <div class="row">
            <?php if (isset($member)) { ?>
            <div class="col-md-12">
                <?php if (total_rows(db_prefix() . 'departments', ['email' => $member->email]) > 0) { ?>
                <div class="alert alert-danger">
                    The staff member email exists also as support department email, according to the docs, the support
                    department email must be unique email in the system, you must change the staff email or the support
                    department email in order all the features to work properly.
                </div>
                <?php } ?>
                <div class="tw-flex tw-justify-between">
                    <h4 class="tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                        <?php echo $member->firstname . ' ' . $member->lastname; ?>
                        <?php if ($member->last_activity && $member->staffid != get_staff_user_id()) { ?>
                        <small> - <?php echo _l('last_active'); ?>:
                            <span class="text-has-action" data-toggle="tooltip"
                                data-title="<?php echo _dt($member->last_activity); ?>">
                                <?php echo time_ago($member->last_activity); ?>
                            </span>
                        </small>
                        <?php } ?>
                    </h4>
                    <a href="#" onclick="small_table_full_view(); return false;" data-placement="left"
                        data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>"
                        class="toggle_view tw-mt-3 tw-shrink-0 tw-inline-flex tw-items-center tw-justify-center hover:tw-text-neutral-800 active:tw-text-neutral-800 hover:tw-bg-neutral-300 tw-h-10 tw-w-10 tw-rounded-full tw-bg-neutral-200 tw-text-neutral-500">
                        <i class="fa fa-expand"></i></a>
                </div>
            </div>
            <?php } ?>
            <?php echo form_open_multipart($this->uri->uri_string(), ['class' => 'staff-form', 'autocomplete' => 'off']); ?>
            <div class="col-md-<?php if (!isset($member)) {
                    echo '8 col-md-offset-2';
                } else {
                    echo '5';
                } ?>" id="small-table">
                <div class="panel_s">
                    <div class="panel-body ">
                        <div class="horizontal-scrollable-tabs panel-full-width-tabs">
                            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                            <div class="horizontal-tabs">
                                <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                                    <li role="presentation" class="active">
                                        <a href="#tab_staff_profile" aria-controls="tab_staff_profile" role="tab"
                                            data-toggle="tab">
                                            <?php echo _l('staff_profile_string'); ?>
                                        </a>
                                    </li>
                                    <li role="presentation">
                                        <a href="#staff_permissions" aria-controls="staff_permissions" role="tab"
                                            data-toggle="tab">
                                            <?php echo _l('staff_add_edit_permissions'); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="tab-content tw-mt-5">
                            <div role="tabpanel" class="tab-pane active" id="tab_staff_profile">
                                <div class="is-not-staff<?php if (isset($member) && $member->admin == 1) {
                                            echo ' hide';
                                        }?>">
                                    <div class="checkbox checkbox-primary">
                                        <?php
                                          //$checked = '';
                                          $checked = 'checked';
                                          $disabled = 'disabled';
                                          if (isset($member)) {
                                              if ($member->is_not_staff == 1) {
                                                  $checked = 'checked';
                                              }
                                          }
                                          ?>
                                        <input type="checkbox" value="1" name="is_not_staff" id="is_not_staff"
                                            <?php echo $checked .' '. $disabled; ?>>
                                        <label for="is_not_staff"><?php echo _l('is_not_staff_member'); ?></label>
                                    </div>
                                    <hr />
                                </div>
                                <?php if ((isset($member) && $member->profile_image == null) || !isset($member)) { ?>
                                <div class="form-group">
                                    <label for="profile_image"
                                        class="profile-image"><?php echo _l('staff_edit_profile_image'); ?></label>
                                    <input type="file" name="profile_image" class="form-control" id="profile_image">
                                </div>
                                <?php } ?>
                                <?php if (isset($member) && $member->profile_image != null) { ?>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-9">
                                            <?php echo staff_profile_image($member->staffid, ['img', 'img-responsive', 'staff-profile-image-thumb'], 'thumb'); ?>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            <a
                                                href="<?php echo admin_url('staff/remove_staff_profile_image/' . $member->staffid); ?>"><i
                                                    class="fa fa-remove"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <?php $value = (isset($member) ? $member->firstname : ''); ?>
                                <?php $attrs = (isset($member) ? [] : ['autofocus' => true]); ?>
                                <?php echo render_input('firstname', 'staff_add_edit_firstname', $value, 'text', $attrs); ?>
                                <?php $value = (isset($member) ? $member->lastname : ''); ?>
                                <?php echo render_input('lastname', 'staff_add_edit_lastname', $value); ?>
                                <?php $value = (isset($member) ? $member->email : ''); ?>
                                <?php echo render_input('email', 'staff_add_edit_email', $value, 'email', ['autocomplete' => 'off']); ?>
                                <?php $value = (isset($member) ? $member->phonenumber : ''); ?>
                                <?php echo render_input('phonenumber', 'staff_add_edit_phonenumber', $value); ?>
                                <?php
                                    $selected = '';
                                    foreach($kelompok_pegawai as $kelompok){
                                     if(isset($member)){
                                       if($member->kelompok_pegawai_id == $kelompok['id']) {
                                         $selected = $kelompok['id'];
                                       }
                                     }
                                    }
                                    echo render_select('kelompok_pegawai_id',$kelompok_pegawai,array('id',array('name')),'kelompok_pegawai_string',$selected);
                                ?>
                                <?php //$value = (isset($member) ? $member->skp_number : ''); ?>
                                <?php //echo render_input('skp_number', 'staff_add_edit_skp_number', $value); ?>

                                <?php //$value = (isset($member) ? _d($member->skp_datestart) : _d(date('Y-m-d'))); ?>
                                <?php //echo render_date_input('skp_datestart','staff_add_edit_skp_datestart',$value); ?>

                                <?php //$value = (isset($member) ? _d($member->skp_dateend) : _d(date('Y-m-d'))); ?>
                                <?php //echo render_date_input('skp_dateend','staff_add_edit_skp_dateend',$value); ?>

                                <?php if (!is_language_disabled()) { ?>
                                <div class="form-group select-placeholder">
                                    <label for="default_language"
                                        class="control-label"><?php echo _l('localization_default_language'); ?></label>
                                    <select name="default_language" data-live-search="true" id="default_language"
                                        class="form-control selectpicker"
                                        data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""><?php echo _l('system_default_string'); ?></option>
                                        <?php foreach ($this->app->get_available_languages() as $availableLanguage) {
                                              $selected = '';
                                              if (isset($member)) {
                                                      if ($member->default_language == $availableLanguage) {
                                                          $selected = 'selected';
                                                      }
                                              } ?>
                                            <option value="<?php echo $availableLanguage; ?>" <?php echo $selected; ?>>
                                                <?php echo ucfirst($availableLanguage); ?></option>
                                            <?php
                                      } ?>
                                    </select>
                                </div>
                                <?php } ?>
                                <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1"
                                    data-toggle="tooltip"
                                    data-title="<?php echo _l('staff_email_signature_help'); ?>"></i>
                                <?php $value = (isset($member) ? $member->email_signature : ''); ?>
                                <?php echo render_textarea('email_signature', 'settings_email_signature', $value, ['data-entities-encode' => 'true']); ?>

                                <div class="row">
                                    <div class="col-md-12">
                                        <hr class="hr-10" />
                                        <?php if (is_admin()) { ?>
                                        <div class="checkbox checkbox-primary">
                                            <?php
                                                 $isadmin = '';
                                                 $disabled = '';
                                                 $disabled = 'disabled';
                                                 if (isset($member) && ($member->staffid == get_staff_user_id() || is_admin($member->staffid))) {
                                                     $isadmin = ' checked';
                                                 }
                                              ?>
                                            <input type="checkbox" name="administrator" id="administrator"
                                                <?php echo $isadmin .' '. $disabled; ?>>
                                            <label
                                                for="administrator"><?php echo _l('staff_add_edit_administrator'); ?></label>
                                        </div>
                                        <?php } ?>

                                        <?php 
                                            $checked = 'checked';
                                            $checked = '';
                                            $disabled = '';
                                            $disabled = 'disabled';
                                        ?>
                                        <?php if (!isset($member) && is_email_template_active('new-staff-created')) { ?>
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" name="send_welcome_email" id="send_welcome_email"
                                                <?php echo $checked .' '. $disabled ;?>>
                                            <label
                                                for="send_welcome_email"><?php echo _l('staff_send_welcome_email'); ?></label>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <?php if (!isset($member) || is_admin() || !is_admin() && $member->admin == 0) { ?>
                                <!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
                                <input type="text" class="fake-autofill-field" name="fakeusernameremembered" value=''
                                    tabindex="-1" />
                                <input type="password" class="fake-autofill-field" name="fakepasswordremembered"
                                    value='' tabindex="-1" />
                                <div class="clearfix form-group"></div>
                                <label for="password"
                                    class="control-label"><?php echo _l('staff_add_edit_password'); ?></label>
                                <div class="input-group">
                                    <input type="password" class="form-control password" name="password"
                                        autocomplete="off">
                                    <span class="input-group-addon tw-border-l-0">
                                        <a href="#password" class="show_password"
                                            onclick="showPassword('password'); return false;"><i
                                                class="fa fa-eye"></i></a>
                                    </span>
                                    <span class="input-group-addon">
                                        <a href="#" class="generate_password"
                                            onclick="generatePassword(this);return false;"><i
                                                class="fa fa-refresh"></i></a>
                                    </span>
                                </div>
                                <?php if (isset($member)) { ?>
                                <p class="text-muted"><?php echo _l('staff_add_edit_password_note'); ?></p>
                                <?php if ($member->last_password_change != null) { ?>
                                <?php echo _l('staff_add_edit_password_last_changed'); ?>:
                                <span class="text-has-action" data-toggle="tooltip"
                                    data-title="<?php echo _dt($member->last_password_change); ?>">
                                    <?php echo time_ago($member->last_password_change); ?>
                                </span>
                                <?php } } ?>
                                <?php } ?>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="staff_permissions">
                                <?php
                        hooks()->do_action('surveyor_render_permissions');
                        
                        foreach ($roles as $key => $role) {
                            if (get_option('default_surveyor_role') !== $role['roleid']) {
                                unset($roles[$key]);
                            }
                        }

                        $selected = '';
                        foreach ($roles as $role) {
                            if (isset($member)) {
                                if ($member->role == $role['roleid']) {
                                    $selected = $role['roleid'];
                                }
                            } else {
                                $default_surveyor_role = get_option('default_surveyor_role');
                                if ($default_surveyor_role == $role['roleid']) {
                                    $selected = $role['roleid'];
                                }
                            }
                        }
                        ?>
                                <?php echo render_select('role', $roles, ['roleid', 'name'], 'staff_add_edit_role', $selected); ?>
                                <hr />
                                <h4 class="font-medium mbot15 bold"><?php echo _l('staff_add_edit_permissions'); ?></h4>
                                <?php
                                
                                     $permissionsData = [ 'funcData' => ['staff_id' => isset($member) ? $member->staffid : null ] ];
                                     if (isset($member)) {
                                         $permissionsData['member'] = $member;
                                     }
                                     $this->load->view('admin/surveyors/staff/permissions', $permissionsData);
                                 ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn-bottom-toolbar text-right">
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
            <?php if (isset($member)) { ?>
            <div class="col-md-7 small-table-right-col">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('staff_add_edit_notes'); ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">

                        <a href="#" class="btn btn-success"
                            onclick="slideToggle('.usernote'); return false;"><?php echo _l('new_note'); ?></a>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-separator" />
                        <div class="mbot15 usernote hide inline-block full-width">
                            <?php echo form_open(admin_url('misc/add_note/' . $member->staffid . '/staff')); ?>
                            <?php echo render_textarea('description', 'staff_add_edit_note_description', '', ['rows' => 5]); ?>
                            <button class="btn btn-primary pull-right mbot15"><?php echo _l('submit'); ?></button>
                            <?php echo form_close(); ?>
                        </div>
                        <div class="clearfix"></div>
                        <div class="mtop15">
                            <table class="table dt-table" data-order-col="2" data-order-type="desc">
                                <thead>
                                    <tr>
                                        <th width="50%"><?php echo _l('staff_notes_table_description_heading'); ?></th>
                                        <th><?php echo _l('staff_notes_table_addedfrom_heading'); ?></th>
                                        <th><?php echo _l('staff_notes_table_dateadded_heading'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_notes as $note) { ?>
                                    <tr>
                                        <td width="50%">
                                            <div data-note-description="<?php echo $note['id']; ?>">
                                                <?php echo check_for_links($note['description']); ?>
                                            </div>
                                            <div data-note-edit-textarea="<?php echo $note['id']; ?>"
                                                class="hide inline-block full-width">
                                                <textarea name="description" class="form-control"
                                                    rows="4"><?php echo clear_textarea_breaks($note['description']); ?></textarea>
                                                <div class="text-right mtop15">
                                                    <button type="button" class="btn btn-default"
                                                        onclick="toggle_edit_note(<?php echo $note['id']; ?>);return false;"><?php echo _l('cancel'); ?></button>
                                                    <button type="button" class="btn btn-primary"
                                                        onclick="edit_note(<?php echo $note['id']; ?>);"><?php echo _l('update_note'); ?></button>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $note['firstname'] . ' ' . $note['lastname']; ?></td>
                                        <td data-order="<?php echo $note['dateadded']; ?>">
                                            <?php echo _dt($note['dateadded']); ?></td>
                                        <td>
                                            <div class="tw-flex tw-items-center tw-space-x-3">
                                                <?php if ($note['addedfrom'] == get_staff_user_id() || has_permission('staff', '', 'delete')) { ?>
                                                <a href="#"
                                                    onclick="toggle_edit_note(<?php echo $note['id']; ?>);return false;"
                                                    class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                                </a>
                                                <a href="<?php echo admin_url('misc/delete_note/' . $note['id']); ?>"
                                                    class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                                    <i class="fa-regular fa-trash-can fa-lg"></i>
                                                </a>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('programs'); ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_filters _hidden_inputs hidden staff_programs_filter">
                            <?php echo form_hidden('staff_id', $member->staffid); ?>
                        </div>
                        <?php render_datatable([
                  _l('program_name'),
                  _l('program_start_date'),
                  _l('program_deadline'),
                  _l('program_state'),
                  ], 'staff-programs'); ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
        <div class="btn-bottom-pusher"></div>
    </div>
    <?php init_tail(); ?>
    <script>
    $(function() {

        $('select[name="role"]').on('change', function() {
            var roleid = $(this).val();
            init_roles_permissions(roleid, true);
        });

        $('input[name="administrator"]').on('change', function() {
            var checked = $(this).prop('checked');
            var isNotStaffMember = $('.is-not-staff');
            if (checked == true) {
                isNotStaffMember.addClass('hide');
                $('.roles').find('input').prop('disabled', true).prop('checked', false);
            } else {
                isNotStaffMember.removeClass('hide');
                isNotStaffMember.find('input').prop('checked', false);
                $('.roles').find('.capability').not('[data-not-applicable="true"]').prop('disabled',
                    false)
            }
        });

        $('#is_not_staff').on('change', function() {
            var checked = $(this).prop('checked');
            var row_permission_leads = $('tr[data-name="leads"]');
            if (checked == true) {
                row_permission_leads.addClass('hide');
                row_permission_leads.find('input').prop('checked', false);
            } else {
                row_permission_leads.removeClass('hide');
            }
        });

        init_roles_permissions();

        appValidateForm($('.staff-form'), {
            firstname: 'required',
            lastname: 'required',
            username: 'required',
            kelompok_pegawai_id: 'required',
            skp_number: 'required',
            skp_datestart: 'required',
            skp_dateend: 'required',
            password: {
                required: {
                    depends: function(element) {
                        return ($('input[name="isedit"]').length == 0) ? true : false
                    }
                }
            },
            email: {
                required: true,
                email: true,
                remote: {
                    url: admin_url + "misc/staff_email_exists",
                    type: 'post',
                    data: {
                        email: function() {
                            return $('input[name="email"]').val();
                        },
                        memberid: function() {
                            return $('input[name="memberid"]').val();
                        }
                    }
                }
            }
        });
    });
    </script>
    </body>

    </html>