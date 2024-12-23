<?php
    /**
    * Plugin name: Inclusive AI Descriptions
    * Plugin URI: https://fotografiadiscapacidad.usal.es/
    * Description: An AI-powered implementation of ChatGPT for generating image descriptions to enhance accessibility.
    * Author: Atenea Vadillo
    * Version: 1.0
    * Author URI: https://github.com/AteneaV0204
    */
    
    //Load the main class
    require_once plugin_dir_path(__FILE__) . 'includes/InclusiveAiDescriptions.php';

    //Init
    add_action('plugins_loaded', array('InclusiveAiDescriptions', 'get_instance'));
