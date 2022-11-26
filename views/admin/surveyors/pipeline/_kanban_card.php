<?php defined('BASEPATH') or exit('No direct script access allowed');
   if ($surveyor['state'] == $state) { ?>
<li data-surveyor-id="<?php echo $surveyor['id']; ?>" class="<?php if($surveyor['invoiceid'] != NULL){echo 'not-sortable';} ?>">
   <div class="panel-body">
      <div class="row">
         <div class="col-md-12">
            <h4 class="bold pipeline-heading"><a href="<?php echo admin_url('surveyors/list_surveyors/'.$surveyor['id']); ?>" onclick="surveyor_pipeline_open(<?php echo $surveyor['id']; ?>); return false;"><?php echo format_surveyor_number($surveyor['id']); ?></a>
               <?php if(has_permission('surveyors','','edit')){ ?>
               <a href="<?php echo admin_url('surveyors/surveyor/'.$surveyor['id']); ?>" target="_blank" class="pull-right"><small><i class="fa fa-pencil-square-o" aria-hidden="true"></i></small></a>
               <?php } ?>
            </h4>
            <span class="inline-block full-width mbot10">
            <a href="<?php echo admin_url('clients/client/'.$surveyor['clientid']); ?>" target="_blank">
            <?php echo $surveyor['company']; ?>
            </a>
            </span>
         </div>
         <div class="col-md-12">
            <div class="row">
               <div class="col-md-8">
                  <span class="bold">
                  <?php echo _l('surveyor_total') . ':' . app_format_money($surveyor['total'], $surveyor['currency_name']); ?>
                  </span>
                  <br />
                  <?php echo _l('surveyor_data_date') . ': ' . _d($surveyor['date']); ?>
                  <?php if(is_date($surveyor['expirydate']) || !empty($surveyor['expirydate'])){
                     echo '<br />';
                     echo _l('surveyor_data_expiry_date') . ': ' . _d($surveyor['expirydate']);
                     } ?>
               </div>
               <div class="col-md-4 text-right">
                  <small><i class="fa fa-paperclip"></i> <?php echo _l('surveyor_notes'); ?>: <?php echo total_rows(db_prefix().'notes', array(
                     'rel_id' => $surveyor['id'],
                     'rel_type' => 'surveyor',
                     )); ?></small>
               </div>
               <?php $tags = get_tags_in($surveyor['id'],'surveyor');
                  if(count($tags) > 0){ ?>
               <div class="col-md-12">
                  <div class="mtop5 kanban-tags">
                     <?php echo render_tags($tags); ?>
                  </div>
               </div>
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</li>
<?php } ?>
