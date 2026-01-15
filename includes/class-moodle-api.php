<?php
/**
 * Moodle API Class
 *
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_API {

    /**
     * Base URL for Moodle webservice
     */
    private $base_url;

    /**
     * Moodle username
     */
    private $username;

    /**
     * Moodle token
     */
    private $token;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'moodle_settings';
        
        $this->base_url = $wpdb->get_var("SELECT setting_value FROM $table WHERE setting_key = 'base_url'");
        $this->username = $wpdb->get_var("SELECT setting_value FROM $table WHERE setting_key = 'username'");
        $this->token = $wpdb->get_var("SELECT setting_value FROM $table WHERE setting_key = 'token'");
    }

    /**
     * Check if connection settings are configured
     */
    public function is_configured() {
        return !empty($this->base_url) && !empty($this->username) && !empty($this->token);
    }

    /**
     * Test connection to Moodle
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => __('Connection settings not configured', 'moodle-management')
            );
        }

        try {
            $response = $this->call('core_webservice_get_site_info', array());
            return array(
                'success' => true,
                'message' => __('Connection successful!', 'moodle-management'),
                'data' => $response
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Call Moodle webservice function
     */
    public function call($function, $params = array()) {
        if (!$this->is_configured()) {
            throw new Exception(__('Connection settings not configured', 'moodle-management'));
        }

        $url = rtrim($this->base_url, '/') . '/webservice/rest/server.php';
        
        $data = array(
            'wstoken' => $this->token,
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json'
        );

        // Add parameters
        foreach ($params as $key => $value) {
            $data[$key] = $value;
        }

        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['exception'])) {
            throw new Exception($result['message']);
        }

        return $result;
    }

    /**
     * Get all categories from Moodle
     */
    public function get_categories() {
        return $this->call('core_course_get_categories', array());
    }

    /**
     * Get all courses from Moodle
     */
    public function get_courses() {
        return $this->call('core_course_get_courses', array());
    }

    /**
     * Get enrollments for a course
     */
    public function get_course_enrollments($course_id) {
        return $this->call('core_enrol_get_enrolled_users', array(
            'courseid' => $course_id
        ));
    }

    /**
     * Get enrolment methods for a course
     */
    public function get_course_enrolment_methods($course_id) {
        return $this->call('core_enrol_get_course_enrolment_methods', array(
            'courseid' => (int) $course_id
        ));
    }

    /**
     * Save connection settings
     */
    public static function save_settings($base_url, $username, $token) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'moodle_settings';
        
        $settings = array(
            'base_url' => $base_url,
            'username' => $username,
            'token' => $token
        );

        foreach ($settings as $key => $value) {
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $table (setting_key, setting_value) VALUES (%s, %s)
                ON DUPLICATE KEY UPDATE setting_value = %s",
                $key,
                $value,
                $value
            ));
        }

        return true;
    }

    /**
     * Get connection settings
     */
    public static function get_settings() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'moodle_settings';
        
        $results = $wpdb->get_results(
            "SELECT setting_key, setting_value FROM $table"
        );

        $settings = array();
        foreach ($results as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        return $settings;
    }
}
