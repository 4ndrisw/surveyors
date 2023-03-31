<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_231 extends App_module_migration {
    public function up() {
        // Perform database upgrade here
        ALTER TABLE `tblclients` ADD `siup` VARCHAR(20) NULL DEFAULT NULL;
        ALTER TABLE `tblclients` ADD `is_preffered` tinyint(1) NULL DEFAULT NULL;
        ALTER TABLE `tblclients` ADD `is_surveyor` tinyint(1) NULL DEFAULT NULL
    }
}