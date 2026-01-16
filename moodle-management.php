<?php
/**
 * Plugin Name: Moodle Management
 * Plugin URI: https://falabetinho.com.br
 * Description: Gerencia a integração do WordPress com Moodle
 * Version: 1.0.0
 * Author: Falabetinho
 * Author URI: https://falabetinho.com.br
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: moodle-management
 * Domain Path: /languages
 */

// Security: Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MOODLE_MANAGEMENT_VERSION', '1.0.0');
define('MOODLE_MANAGEMENT_PATH', plugin_dir_path(__FILE__));
define('MOODLE_MANAGEMENT_URL', plugin_dir_url(__FILE__));

// Include required files
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-management.php';
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-api.php';
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-courses.php';
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-settings.php';

// Initialize the plugin
function moodle_management_init() {
    Moodle_Management::get_instance();
    new Moodle_Courses();
    new Moodle_Settings();
}
add_action('plugins_loaded', 'moodle_management_init');

// Activation hook
register_activation_hook(__FILE__, array('Moodle_Management', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('Moodle_Management', 'deactivate'));
