<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['surveyors/surveyor/(:num)/(:any)'] = 'surveyor/index/$1/$2';

/**
 * @since 2.0.0
 */
$route['surveyors/list'] = 'mysurveyor/list';
$route['surveyors/show/(:num)/(:any)'] = 'mysurveyor/show/$1/$2';
$route['surveyors/office/(:num)/(:any)'] = 'mysurveyor/office/$1/$2';
$route['surveyors/pdf/(:num)'] = 'mysurveyor/pdf/$1';
$route['surveyors/office_pdf/(:num)'] = 'mysurveyor/office_pdf/$1';
