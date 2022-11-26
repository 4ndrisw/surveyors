<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-surveyors" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th><?php echo _l('surveyor_number'); ?> #</th>
            <th><?php echo _l('surveyor_list_program'); ?></th>
            <th><?php echo _l('surveyor_list_date'); ?></th>
            <th><?php echo _l('surveyor_list_state'); ?></th>

        </tr>
    </thead>
    <tbody>
        <?php foreach($surveyors as $surveyor){ ?>
            <tr>
                <td><?php echo '<a href="' . site_url("surveyors/show/" . $surveyor["id"] . '/' . $surveyor["hash"]) . '">' . format_surveyor_number($surveyor["id"]) . '</a>'; ?></td>
                <td><?php echo $surveyor['name']; ?></td>
                <td><?php echo _d($surveyor['date']); ?></td>
                <td><?php echo format_surveyor_state($surveyor['state']); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
