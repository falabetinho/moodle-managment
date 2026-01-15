<?php
/**
 * Main Plugin Class
 *
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_Management {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Admin tabs handler
     */
    private $admin_tabs;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Create database tables on activation
        add_action('plugins_loaded', array($this, 'create_tables'));

        // Load admin handlers early so AJAX hooks are registered
        if (is_admin()) {
            $this->load_admin_tabs();
        }
    }

    /**
     * Load admin tabs class and register AJAX hooks
     */
    private function load_admin_tabs() {
        if ($this->admin_tabs) {
            return;
        }

        require_once MOODLE_MANAGEMENT_PATH . 'admin/class-admin-tabs.php';
        $this->admin_tabs = new Moodle_Admin_Tabs();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Moodle Management', 'moodle-management'),
            __('Moodle Management', 'moodle-management'),
            'manage_options',
            'moodle-management',
            array($this, 'render_admin_page'),
            'dashicons-cloud',
            25
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_moodle-management' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'moodle-management-admin',
            MOODLE_MANAGEMENT_URL . 'assets/css/admin.css',
            array(),
            MOODLE_MANAGEMENT_VERSION
        );

        wp_enqueue_script(
            'moodle-management-admin',
            MOODLE_MANAGEMENT_URL . 'assets/js/admin.js',
            array('jquery'),
            MOODLE_MANAGEMENT_VERSION,
            true
        );

        wp_localize_script(
            'moodle-management-admin',
            'moodleManagement',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('moodle_management_nonce')
            )
        );
    }

    /**
     * Render admin page with tabs
     */
    public function render_admin_page() {
        $this->load_admin_tabs();
        $this->admin_tabs->render();
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Table for storing connection settings
        $table_settings = $wpdb->prefix . 'moodle_settings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_settings'") !== $table_settings) {
            $sql = "CREATE TABLE $table_settings (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                setting_key varchar(100) NOT NULL,
                setting_value longtext NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY setting_key (setting_key)
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Table for storing categories
        $table_categories = $wpdb->prefix . 'moodle_categories';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_categories'") !== $table_categories) {
            $sql = "CREATE TABLE $table_categories (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                moodle_id int(11) NOT NULL,
                name varchar(255) NOT NULL,
                description longtext,
                parent_id int(11) DEFAULT 0,
                path varchar(500) DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY moodle_id (moodle_id),
                KEY parent_id (parent_id),
                KEY path (path(191))
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        } else {
            // dbDelta adds new columns/keys if schema changes
            $sql = "CREATE TABLE $table_categories (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                moodle_id int(11) NOT NULL,
                name varchar(255) NOT NULL,
                description longtext,
                parent_id int(11) DEFAULT 0,
                path varchar(500) DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY moodle_id (moodle_id),
                KEY parent_id (parent_id),
                KEY path (path(191))
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Table for storing courses
        $table_courses = $wpdb->prefix . 'moodle_courses';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_courses'") !== $table_courses) {
            $sql = "CREATE TABLE $table_courses (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                moodle_id int(11) NOT NULL,
                name varchar(255) NOT NULL,
                shortname varchar(100),
                description longtext,
                category_id int(11),
                imported int(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY moodle_id (moodle_id)
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }

        // Table for storing enrollments
        $table_enrollments = $wpdb->prefix . 'moodle_enrollments';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_enrollments'") !== $table_enrollments) {
            $sql = "CREATE TABLE $table_enrollments (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                moodle_user_id int(11) NOT NULL,
                moodle_course_id int(11) NOT NULL,
                role varchar(50),
                enrol_method varchar(50),
                imported int(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY moodle_enrollment (moodle_user_id, moodle_course_id)
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Create tables
        self::get_instance()->create_tables();
        
        // Set default options
        add_option('moodle_management_version', MOODLE_MANAGEMENT_VERSION);
        
        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
}
