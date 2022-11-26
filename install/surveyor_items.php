<?php defined('BASEPATH') or exit('No direct script access allowed');


if (!$CI->db->table_exists(db_prefix() . 'surveyor_items')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "surveyor_items` (
  `id` int(11) NOT NULL,
  `surveyor_id` int(11) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `equipment_name` varchar(60) DEFAULT NULL,
  `nomor_suket` varchar(30) DEFAULT NULL,
  `expired` date DEFAULT NULL,
  `tanggal_suket` date DEFAULT NULL,
  `flag` tinyint(1) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'surveyor_items`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `surveyor_id_task_id` (`surveyor_id`,`task_id`) USING BTREE,
      ADD KEY `program_id` (`program_id`),
      ADD KEY `task_id` (`task_id`),
      ADD KEY `surveyor_id` (`surveyor_id`)
    ;
  ');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'surveyor_items`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
