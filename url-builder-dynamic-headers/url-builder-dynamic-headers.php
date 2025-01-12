<?php
/*
Plugin Name: URL Builder with Dynamic Headers
Description: Automatically updates the browser URL and dynamically adjusts page headers based on selected filters on the Bildungsangebot page.
Author: @maincoder_ru
Author URI: https://github.com/razzorenoff
Version: 1.3
*/

if (!defined('ABSPATH')) {
    exit;
}

// Load core functionality.
require_once plugin_dir_path(__FILE__) . 'includes/class-url-builder.php';

// Initialize the plugin.
add_action('plugins_loaded', ['URL_Builder', 'init']);

// Add "Description" link to plugin meta
add_filter('plugin_row_meta', 'url_builder_plugin_meta', 10, 2);

function url_builder_plugin_meta($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $description_link = '<a href="#" class="url-builder-description-link" data-plugin="url-builder">Description</a>';
        $links[] = $description_link;
    }
    return $links;
}

// Enqueue required scripts and styles
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('jquery-ui-dialog');
});

// Add modal markup and script to admin footer
add_action('admin_footer', 'url_builder_description_modal');

function url_builder_description_modal() {
    ?>
    <!-- Modal HTML -->
    <div id="url-builder-modal" style="display:none;">
        <button class="close-modal">&times;</button>
        <div class="modal-content">
            <h2>URL Builder with Dynamic Headers</h2>
            <p>
                This plugin dynamically updates the browser URL and page headers based on selected filters. 
                It is specifically designed for the <strong>Bildungsangebot</strong> page, offering seamless 
                integration with existing filter systems without modifying theme files directly.
            </p>
            <h3>Features:</h3>
            <ul>
                <li>Real-time URL updates in a readable format.</li>
                <li>Dynamic page header generation based on selected filters.</li>
                <li>Fully integrated with WordPress rewrite rules and query vars.</li>
                <li>No interference with theme files, ensuring compatibility and ease of use.</li>
            </ul>
            <h3>Purpose:</h3>
            <p>
                The main purpose of this plugin is to provide a clean and user-friendly way to handle filter-based navigation 
                without affecting the site's underlying structure or performance.
            </p>
        </div>
    </div>
    
    <!-- Modal Styles -->
    <style>
        #url-builder-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            max-height: none; /* Убираем ограничение по высоте */
        }

        #url-builder-modal .modal-content {
            word-wrap: break-word;
        }

        #url-builder-modal h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        #url-builder-modal h3 {
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        #url-builder-modal p,
        #url-builder-modal ul {
            font-size: 14px;
            line-height: 1.6;
        }

        #url-builder-modal ul {
            padding-left: 20px;
        }

        #url-builder-modal .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            background: #ddd;
            color: #333;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            line-height: 30px;
            text-align: center;
        }

        #url-builder-modal .close-modal:hover {
            background: #bbb;
        }

        /* Overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }
    </style>

    <!-- Modal Script -->
    <script>
        jQuery(document).ready(function ($) {
            // Open modal
            $(document).on('click', '.url-builder-description-link', function (e) {
                e.preventDefault();
                
                // Add overlay
                $('body').append('<div class="modal-overlay"></div>');
                
                // Show modal
                $('#url-builder-modal').fadeIn();
            });

            // Close modal on close button click
            $(document).on('click', '.close-modal', function () {
                $('#url-builder-modal').fadeOut();
                $('.modal-overlay').remove();
            });

            // Close modal on overlay click
            $(document).on('click', '.modal-overlay', function () {
                $('#url-builder-modal').fadeOut();
                $(this).remove();
            });
        });
    </script>
    <?php
}
