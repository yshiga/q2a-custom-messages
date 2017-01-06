<?php
/*
    Plugin Name: Custom Messge List Plugin
    Plugin URI:
    Plugin Description: Create original message list like facebook messanger
    Plugin Version: 1.0
    Plugin Date: 2016-12-15
    Plugin Author: 38qa.net
    Plugin Author URI: http://38qa.net/
    Plugin License: GPLv2
    Plugin Minimum Question2Answer Version: 1.7
    Plugin Update Check URI:
*/
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

//Define global constants
@define( 'CML_DIR', dirname( __FILE__ ) );
@define( 'CML_FOLDER', basename( dirname( __FILE__ ) ) );
@define( 'CML_TARGET_THEME_NAME', 'q2a-material-lite');
@define( 'CML_RELATIVE_PATH', '../qa-plugin/'.CML_FOLDER.'/');

qa_register_plugin_layer('qa-custom-messages-layer.php','Custom Message List Layer');
