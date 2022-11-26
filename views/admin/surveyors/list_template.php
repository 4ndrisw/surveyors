<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">
  <div class="panel_s mbot10">
   <div class="panel-body _buttons">
    <?php $this->load->view('admin/surveyors/surveyors_top_stats');
    ?>
    <?php if(has_permission('surveyors','','create')){ ?>
     <a href="<?php echo admin_url('surveyors/surveyor'); ?>" class="btn btn-info pull-left new new-surveyor-btn"><?php echo _l('create_new_surveyor'); ?></a>
   <?php } ?>
   <a href="<?php echo admin_url('surveyors/pipeline/'.$switch_pipeline); ?>" class="btn btn-default mleft5 pull-left switch-pipeline hidden-xs"><?php echo _l('switch_to_pipeline'); ?></a>
   <div class="display-block text-right">
     <div class="btn-group pull-right mleft4 btn-with-tooltip-group _filter_data" data-toggle="tooltip" data-title="<?php echo _l('filter_by'); ?>">
      <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-filter" aria-hidden="true"></i>
      </button>
      <ul class="dropdown-menu width300">
       <li>
        <a href="#" data-cview="all" onclick="dt_custom_view('','.table-surveyors',''); return false;">
          <?php echo _l('surveyors_list_all'); ?>
        </a>
      </li>
      <li class="divider"></li>
      <li class="<?php if($this->input->get('filter') == 'not_sent'){echo 'active'; } ?>">
        <a href="#" data-cview="not_sent" onclick="dt_custom_view('not_sent','.table-surveyors','not_sent'); return false;">
          <?php echo _l('not_sent_indicator'); ?>
        </a>
      </li>
      <li class="divider"></li>
      <?php foreach($surveyor_states as $state){ ?>
        <li class="<?php if($this->input->get('state') == $state){echo 'active';} ?>">
          <a href="#" data-cview="surveyors_<?php echo $state; ?>" onclick="dt_custom_view('surveyors_<?php echo $state; ?>','.table-surveyors','surveyors_<?php echo $state; ?>'); return false;">
            <?php echo format_surveyor_state($state,'',false); ?>
          </a>
        </li>
      <?php } ?>
      <div class="clearfix"></div>

      <?php if(count($surveyors_sale_agents) > 0){ ?>
        <div class="clearfix"></div>
        <li class="divider"></li>
        <li class="dropdown-submenu pull-left">
          <a href="#" tabindex="-1"><?php echo _l('sale_agent_string'); ?></a>
          <ul class="dropdown-menu dropdown-menu-left">
           <?php foreach($surveyors_sale_agents as $agent){ ?>
             <li>
              <a href="#" data-cview="sale_agent_<?php echo $agent['sale_agent']; ?>" onclick="dt_custom_view(<?php echo $agent['sale_agent']; ?>,'.table-surveyors','sale_agent_<?php echo $agent['sale_agent']; ?>'); return false;"><?php echo $agent['full_name']; ?>
            </a>
          </li>
        <?php } ?>
      </ul>
    </li>
  <?php } ?>
  <div class="clearfix"></div>
  <?php if(count($surveyors_years) > 0){ ?>
    <li class="divider"></li>
    <?php foreach($surveyors_years as $year){ ?>
      <li class="active">
        <a href="#" data-cview="year_<?php echo $year['year']; ?>" onclick="dt_custom_view(<?php echo $year['year']; ?>,'.table-surveyors','year_<?php echo $year['year']; ?>'); return false;"><?php echo $year['year']; ?>
      </a>
    </li>
  <?php } ?>
<?php } ?>
</ul>
</div>
<a href="#" class="btn btn-default btn-with-tooltip toggle-small-view hidden-xs" onclick="toggle_small_view('.table-surveyors','#surveyor'); return false;" data-toggle="tooltip" title="<?php echo _l('surveyors_toggle_table_tooltip'); ?>"><i class="fa fa-angle-double-left"></i></a>
<a href="#" class="btn btn-default btn-with-tooltip surveyors-total" onclick="slideToggle('#stats-top'); init_surveyor_total(true); return false;" data-toggle="tooltip" title="<?php echo _l('view_stats_tooltip'); ?>"><i class="fa fa-bar-chart"></i></a>
</div>
</div>
</div>
<div class="row">
  <div class="col-md-12" id="small-table">
    <div class="panel_s">
      <div class="panel-body">
        <!-- if surveyorid found in url -->
        <?php echo form_hidden('surveyorid',$surveyorid); ?>
        <?php $this->load->view('admin/surveyors/table_html'); ?>
      </div>
    </div>
  </div>
  <div class="col-md-7 small-table-right-col">
    <div id="surveyor" class="hide">
    </div>
  </div>
</div>
</div>
