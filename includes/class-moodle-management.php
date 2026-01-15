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

        // Enqueue frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

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
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Assets não necessários, pois os filtros são tratados via forms padrão
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
                visibility int(1) DEFAULT 1,
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

        // Table for storing enrolment methods per course
        $table_enrol_methods = $wpdb->prefix . 'moodle_enrol_methods';
        $sql = "CREATE TABLE $table_enrol_methods (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            moodle_enrol_id int(11) NOT NULL,
            moodle_course_id int(11) NOT NULL,
            enrol_plugin varchar(100) NOT NULL,
            name varchar(255) DEFAULT '',
            status int(11) DEFAULT 0,
            roleid int(11) DEFAULT NULL,
            cost decimal(10,2) DEFAULT NULL,
            currency varchar(10) DEFAULT NULL,
            enrolstartdate int(11) DEFAULT NULL,
            enrolenddate int(11) DEFAULT NULL,
            enrolperiod int(11) DEFAULT NULL,
            expirynotify int(11) DEFAULT NULL,
            expirythreshold int(11) DEFAULT NULL,
            notifyall int(11) DEFAULT NULL,
            category_id int(11) DEFAULT NULL,
            default_status_id int(11) DEFAULT NULL,
            is_enrollment_fee tinyint(1) DEFAULT 0,
            installments int(11) DEFAULT NULL,
            data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY enrol_unique (moodle_enrol_id, moodle_course_id),
            KEY course_idx (moodle_course_id),
            KEY plugin_idx (enrol_plugin),
            KEY category_idx (category_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Run migrations
        self::run_migrations();
    }

    /**
     * Run database migrations
     */
    private static function run_migrations() {
        global $wpdb;
        
        $table_courses = $wpdb->prefix . 'moodle_courses';
        
        // Check if visibility column exists, if not add it
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'visibility'",
                DB_NAME,
                $table_courses
            )
        );
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_courses ADD COLUMN visibility int(1) DEFAULT 1 AFTER category_id");
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
