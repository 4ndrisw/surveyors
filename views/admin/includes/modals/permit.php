<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade modal-permit permit-modal-<?php echo $name . '-' . $id; ?>" tabindex="-1" role="dialog"
    aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open('admin/surveyors/add_permit/' . $id . '/' . $name, ['id' => 'form-permit-' . $name]); ?>
            <div class="modal-header">
                <button type="button" class="close close-permit-modal" data-rel-id="<?php echo $id; ?>"
                    data-rel-type="<?php echo $name; ?>" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><i class="fa-regular fa-circle-question" data-toggle="tooltip"
                        title="<?php echo _l('set_permit_tooltip'); ?>" data-placement="bottom"></i>
                    <?php echo _l('surveyor_set_permit_title'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php $this->load->view('admin/includes/permit_fields'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default close-permit-modal" data-rel-id="<?php echo $id; ?>"
                    data-rel-type="<?php echo $name; ?>"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
