<?php

// Set headers to allow cross-domain requests
function set_headers()
{
    // Set headers to allow cross-domain requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

    // Set content type
    header('Content-Type: application/json');
}
