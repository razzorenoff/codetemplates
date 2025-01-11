<?php
/*
Plugin Name: URL Builder with Dynamic Headers
Description: Automatically updates the browser URL and dynamically adjusts page headers based on selected filters on the Bildungsangebot page.
Author: @maincoder_ru
Author URI: https://github.com/razzorenoff
Version: 1.0
*/

if (!defined('ABSPATH')) {
    exit;
}

// Load core functionality.
require_once plugin_dir_path(__FILE__) . 'includes/class-url-builder.php';

// Initialize the plugin.
add_action('plugins_loaded', ['URL_Builder', 'init']);
