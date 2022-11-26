<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Surveyor_pdf extends App_pdf
{
    protected $surveyor;

    private $surveyor_number;

    public function __construct($surveyor, $tag = '')
    {
        $this->load_language($surveyor->clientid);

        $surveyor                = hooks()->apply_filters('surveyor_html_pdf_data', $surveyor);
        $GLOBALS['surveyor_pdf'] = $surveyor;

        parent::__construct();

        $this->tag             = $tag;
        $this->surveyor        = $surveyor;
        $this->surveyor_number = format_surveyor_number($this->surveyor->id);

        $this->SetTitle($this->surveyor_number);
    }

    public function prepare()
    {

        $this->set_view_vars([
            'state'          => $this->surveyor->state,
            'surveyor_number' => $this->surveyor_number,
            'surveyor'        => $this->surveyor,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'surveyor';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_surveyorpdf.php';
        $actualPath = module_views_path('surveyors','themes/' . active_clients_theme() . '/views/surveyors/surveyorpdf.php');

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
