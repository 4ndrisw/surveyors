<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Surveyor_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $surveyor;

    protected $contact;

    public $slug = 'surveyor-send-to-client';

    public $rel_type = 'surveyor';

    public function __construct($surveyor, $contact, $cc = '')
    {
        parent::__construct();

        $this->surveyor = $surveyor;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->surveyors_model->get_attachments($this->surveyor->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('surveyor') . $this->surveyor->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->surveyor->id)
        ->set_merge_fields('client_merge_fields', $this->surveyor->clientid, $this->contact->id)
        ->set_merge_fields('surveyor_merge_fields', $this->surveyor->id);
    }
}
