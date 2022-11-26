<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    $CI = &get_instance();
    $CI->load->model('surveyors/surveyors_model');
    $surveyors = $CI->surveyors_model->get_surveyors_this_week(get_staff_user_id());

?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('surveyor_this_week'); ?>">
    <?php if(staff_can('view', 'surveyors') || staff_can('view_own', 'surveyors')) { ?>
    <div class="panel_s surveyors-expiring">
        <div class="panel-body padding-10">
            <p class="padding-5"><?php echo _l('surveyor_this_week'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($surveyors)) { ?>
                <div class="table-vertical-scroll">
                    <a href="<?php echo admin_url('surveyors'); ?>" class="mbot20 inline-block full-width"><?php echo _l('home_widget_view_all'); ?></a>
                    <table id="widget-<?php echo create_widget_id(); ?>" class="table dt-table dt-inline dataTable no-footer" data-order-col="3" data-order-type="desc">
                        <thead>
                            <tr>
                                <th><?php echo _l('surveyor_number'); ?> #</th>
                                <th class="<?php echo (isset($client) ? 'not_visible' : ''); ?>"><?php echo _l('surveyor_list_client'); ?></th>
                                <th><?php echo _l('surveyor_list_program'); ?></th>
                                <th><?php echo _l('surveyor_list_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($surveyors as $surveyor) { ?>
                                <tr class="<?= 'surveyor_state_' . $surveyor['state']?>">
                                    <td>
                                        <?php echo '<a href="' . admin_url("surveyors/surveyor/" . $surveyor["id"]) . '">' . format_surveyor_number($surveyor["id"]) . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo '<a href="' . admin_url("clients/client/" . $surveyor["userid"]) . '">' . $surveyor["company"] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo '<a href="' . admin_url("programs/view/" . $surveyor["programs_id"]) . '">' . $surveyor['name'] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo _d($surveyor['date']); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="text-center padding-5">
                    <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                    <h4><?php echo _l('no_surveyor_this_week',["7"]) ; ?> </h4>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
