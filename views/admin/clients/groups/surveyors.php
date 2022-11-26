<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($client)){ ?>
	<h4 class="customer-profile-group-heading"><?php echo _l('surveyors'); ?></h4>
	<?php if(has_permission('surveyors','','create')){ ?>
		<a href="<?php echo admin_url('surveyors/surveyor?customer_id='.$client->userid); ?>" class="btn btn-info mbot15<?php if($client->active == 0){echo ' disabled';} ?>"><?php echo _l('create_new_surveyor'); ?></a>
	<?php } ?>
	<?php if(has_permission('surveyors','','view') || has_permission('surveyors','','view_own') || get_option('allow_staff_view_surveyors_assigned') == '1'){ ?>
		<a href="#" class="btn btn-info mbot15" data-toggle="modal" data-target="#client_zip_surveyors"><?php echo _l('zip_surveyors'); ?></a>
	<?php } ?>
	<div id="surveyors_total"></div>
	<?php
	$this->load->view('admin/surveyors/table_html', array('class'=>'surveyors-single-client'));
	//$this->load->view('admin/clients/modals/zip_surveyors');
	?>
<?php } ?>
