<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['photo'] = array(
    // Defines which files (based on their names) are accepted for upload:
    'accept_file_types' => '/(\.|\/)(gif|jpe?g|png|k2r)$/i',

    // The php.ini settings upload_max_filesize and post_max_size
    // take precedence over the following max_file_size setting:
    'max_file_size' => NULL,
    'min_file_size' => 1,

    // Image resolution restrictions:
    'max_width' => 800,
    'max_height' => 800,
    'min_width' => 50,
    'min_height' => 50,

    'image_versions' => array(
        'logo' => array(
            'max_width' => 210,
            'max_height' => 210,
            'png_quality' => 9
        )   
    )
);

/* End of file upload.php */
/* Location: ./application/config/upload.php */