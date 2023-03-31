<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once('install/surveyors.php');
require_once('install/surveyor_activity.php');
require_once('install/surveyor_items.php');
require_once('install/surveyor_members.php');



$CI->db->query("
INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('surveyor', 'surveyor-send-to-client', 'english', 'Send surveyor to Customer', 'surveyor # {surveyor_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached surveyor <strong># {surveyor_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>surveyor state:</strong> {surveyor_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-already-send', 'english', 'surveyor Already Sent to Customer', 'surveyor # {surveyor_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your surveyor request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-declined-to-staff', 'english', 'surveyor Declined (Sent to Staff)', 'Customer Declined surveyor', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined surveyor with number <strong># {surveyor_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-accepted-to-staff', 'english', 'surveyor Accepted (Sent to Staff)', 'Customer Accepted surveyor', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted surveyor with number <strong># {surveyor_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-thank-you-to-customer', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting surveyor', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank for for accepting the surveyor.</span><br /> <br /><span style=\"font-size: 12pt;\">We look forward to doing business with you.</span><br /> <br /><span style=\"font-size: 12pt;\">We will contact you as soon as possible.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-expiry-reminder', 'english', 'surveyor Expiration Reminder', 'surveyor Expiration Reminder', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">The surveyor with <strong># {surveyor_number}</strong> will expire on <strong>{surveyor_expirydate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-send-to-client', 'english', 'Send surveyor to Customer', 'surveyor # {surveyor_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached surveyor <strong># {surveyor_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>surveyor state:</strong> {surveyor_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-already-send', 'english', 'surveyor Already Sent to Customer', 'surveyor # {surveyor_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your surveyor request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-declined-to-staff', 'english', 'surveyor Declined (Sent to Staff)', 'Customer Declined surveyor', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined surveyor with number <strong># {surveyor_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-accepted-to-staff', 'english', 'surveyor Accepted (Sent to Staff)', 'Customer Accepted surveyor', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted surveyor with number <strong># {surveyor_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'staff-added-as-program-member', 'english', 'Staff Added as Program Member', 'New program assigned to you', '<p>Hi <br /><br />New surveyor has been assigned to you.<br /><br />You can view the surveyor on the following link <a href=\"{surveyor_link}\">surveyor__number</a><br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
('surveyor', 'surveyor-accepted-to-staff', 'english', 'surveyor Accepted (Sent to Staff)', 'Customer Accepted surveyor', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted surveyor with number <strong># {surveyor_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the surveyor on the following link: <a href=\"{surveyor_link}\">{surveyor_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0);
");
/*
 *
 */

// Add options for surveyors
add_option('delete_only_on_last_surveyor', 1);
add_option('surveyor_prefix', 'PJK3-');
add_option('next_surveyor_number', 1);
add_option('default_surveyor_assigned', 9);
add_option('surveyor_number_decrement_on_delete', 0);
add_option('surveyor_number_format', 4);
add_option('surveyor_year', date('Y'));
add_option('exclude_surveyor_from_client_area_with_draft_state', 1);
add_option('surveyor_due_after', 1);
add_option('allow_staff_view_surveyors_assigned', 1);
add_option('show_assigned_on_surveyors', 1);
add_option('require_client_logged_in_to_view_surveyor', 0);

add_option('show_program_on_surveyor', 1);
add_option('surveyors_pipeline_limit', 1);
add_option('default_surveyors_pipeline_sort', 1);
add_option('surveyor_accept_identity_confirmation', 1);
add_option('surveyor_qrcode_size', '160');
add_option('surveyor_send_telegram_message', 0);


/*

DROP TABLE `tblsurveyors`;
DROP TABLE `tblsurveyor_activity`, `tblsurveyor_items`, `tblsurveyor_members`;
delete FROM `tbloptions` WHERE `name` LIKE '%surveyor%';
DELETE FROM `tblemailtemplates` WHERE `type` LIKE 'surveyor';



*/