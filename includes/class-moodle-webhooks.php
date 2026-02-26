<?php
/**
 * Webhooks REST Endpoints
 *
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_Webhooks {

    const OPTION_SECRET = 'moodle_webhook_secret';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Get or create webhook secret
     */
    public static function get_secret() {
        $secret = get_option(self::OPTION_SECRET);

        if (empty($secret)) {
            $secret = wp_generate_password(32, false, false);
            update_option(self::OPTION_SECRET, $secret, true);
        }

        return $secret;
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route('moodle-management/v1', '/webhooks/categories', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_categories'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route('moodle-management/v1', '/webhooks/courses', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_courses'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route('moodle-management/v1', '/webhooks/prices', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_prices'),
            'permission_callback' => array($this, 'authorize_request'),
        ));
    }

    /**
     * Permission callback for webhooks
     */
    public function authorize_request($request) {
        $token = $request->get_header('X-Moodle-Webhook-Token');
        if (empty($token)) {
            $token = $request->get_param('token');
        }

        if (empty($token) || !hash_equals(self::get_secret(), $token)) {
            return new WP_Error('moodle_webhook_forbidden', __('Token inválido', 'moodle-management'), array('status' => 403));
        }

        return true;
    }

    /**
     * Handle categories webhook
     */
    public function handle_categories() {
        try {
            $api = new Moodle_API();
            if (!$api->is_configured()) {
                return new WP_Error('moodle_not_configured', __('Conexão não configurada', 'moodle-management'), array('status' => 400));
            }

            $categories = $api->get_categories();
            global $wpdb;
            $table = $wpdb->prefix . 'moodle_categories';

            $count = 0;
            foreach ($categories as $category) {
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO $table (moodle_id, name, description, parent_id, path) 
                     VALUES (%d, %s, %s, %d, %s)
                     ON DUPLICATE KEY UPDATE name = %s, description = %s, parent_id = %d, path = %s",
                    $category['id'],
                    $category['name'],
                    isset($category['description']) ? $category['description'] : '',
                    isset($category['parent']) ? (int) $category['parent'] : 0,
                    isset($category['path']) ? $category['path'] : '',
                    $category['name'],
                    isset($category['description']) ? $category['description'] : '',
                    isset($category['parent']) ? (int) $category['parent'] : 0,
                    isset($category['path']) ? $category['path'] : ''
                ));
                $count++;
            }

            return rest_ensure_response(array(
                'success' => true,
                'message' => sprintf(__('%d categorias sincronizadas com sucesso!', 'moodle-management'), $count)
            ));
        } catch (Exception $e) {
            return new WP_Error('moodle_webhook_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Handle courses webhook
     */
    public function handle_courses() {
        try {
            $api = new Moodle_API();
            if (!$api->is_configured()) {
                return new WP_Error('moodle_not_configured', __('Conexão não configurada', 'moodle-management'), array('status' => 400));
            }

            $courses = $api->get_courses();
            global $wpdb;
            $table = $wpdb->prefix . 'moodle_courses';

            $count = 0;
            foreach ($courses as $course) {
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO $table (moodle_id, name, shortname, description, category_id, visibility) 
                     VALUES (%d, %s, %s, %s, %d, %d)
                     ON DUPLICATE KEY UPDATE name = %s, shortname = %s, description = %s, category_id = %d, visibility = %d",
                    $course['id'],
                    $course['fullname'],
                    $course['shortname'],
                    isset($course['summary']) ? $course['summary'] : '',
                    isset($course['categoryid']) ? $course['categoryid'] : 0,
                    isset($course['visible']) ? intval($course['visible']) : 1,
                    $course['fullname'],
                    $course['shortname'],
                    isset($course['summary']) ? $course['summary'] : '',
                    isset($course['categoryid']) ? $course['categoryid'] : 0,
                    isset($course['visible']) ? intval($course['visible']) : 1
                ));
                $count++;
            }

            return rest_ensure_response(array(
                'success' => true,
                'message' => sprintf(__('%d cursos sincronizados com sucesso!', 'moodle-management'), $count)
            ));
        } catch (Exception $e) {
            return new WP_Error('moodle_webhook_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Handle prices webhook (sync enrol methods)
     */
    public function handle_prices() {
        try {
            $api = new Moodle_API();
            if (!$api->is_configured()) {
                return new WP_Error('moodle_not_configured', __('Conexão não configurada', 'moodle-management'), array('status' => 400));
            }

            global $wpdb;
            $courses_table = $wpdb->prefix . 'moodle_courses';
            $enrol_table = $wpdb->prefix . 'moodle_enrol_methods';

            // Clear existing enrol methods
            $wpdb->query("TRUNCATE TABLE $enrol_table");

            $courses = $wpdb->get_results("SELECT moodle_id FROM $courses_table");
            if (empty($courses)) {
                return new WP_Error('moodle_no_courses', __('Nenhum curso encontrado. Sincronize os cursos primeiro.', 'moodle-management'), array('status' => 400));
            }

            $total_count = 0;
            $total_courses = 0;
            $errors = array();

            foreach ($courses as $course) {
                try {
                    $methods = $api->get_course_enrolment_methods($course->moodle_id);
                    $course_count = $this->insert_enrol_methods($course->moodle_id, $methods, $wpdb, $enrol_table);

                    if ($course_count > 0) {
                        $total_count += $course_count;
                        $total_courses++;
                    }
                } catch (Exception $e) {
                    $errors[] = sprintf(__('Curso ID %d: %s', 'moodle-management'), $course->moodle_id, $e->getMessage());
                }
            }

            $message = sprintf(
                __('%d métodos de enrol sincronizados de %d cursos!', 'moodle-management'),
                $total_count,
                $total_courses
            );

            if (!empty($errors)) {
                $message .= ' ' . __('Erros:', 'moodle-management') . ' ' . implode('; ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= sprintf(__(' (e mais %d erros)', 'moodle-management'), count($errors) - 3);
                }
            }

            return rest_ensure_response(array(
                'success' => true,
                'message' => $message
            ));
        } catch (Exception $e) {
            return new WP_Error('moodle_webhook_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Insert enrol methods for a course
     */
    private function insert_enrol_methods($course_id, $methods, $wpdb, $table) {
        $count = 0;

        foreach ($methods as $m) {
            $moodle_enrol_id = isset($m['id']) ? intval($m['id']) : 0;
            $enrol_plugin = isset($m['enrol']) ? sanitize_text_field($m['enrol']) : '';
            $name = isset($m['name']) ? sanitize_text_field($m['name']) : '';
            $status = isset($m['status']) ? intval($m['status']) : 0;
            $roleid = isset($m['roleid']) ? intval($m['roleid']) : null;
            $cost = isset($m['cost']) ? floatval($m['cost']) : null;
            $currency = isset($m['currency']) ? sanitize_text_field($m['currency']) : null;
            $enrolstartdate = isset($m['enrolstartdate']) ? intval($m['enrolstartdate']) : null;
            $enrolenddate = isset($m['enrolenddate']) ? intval($m['enrolenddate']) : null;
            $enrolperiod = isset($m['enrolperiod']) ? intval($m['enrolperiod']) : null;
            $expirynotify = isset($m['expirynotify']) ? intval($m['expirynotify']) : null;
            $expirythreshold = isset($m['expirythreshold']) ? intval($m['expirythreshold']) : null;
            $notifyall = isset($m['notifyall']) ? intval($m['notifyall']) : null;
            $category_id = isset($m['category_id']) ? intval($m['category_id']) : null;
            $default_status_id = isset($m['default_status_id']) ? intval($m['default_status_id']) : null;
            $is_enrollment_fee = isset($m['is_enrollment_fee']) ? intval($m['is_enrollment_fee']) : 0;
            $installments = isset($m['installments']) ? intval($m['installments']) : null;
            $data = wp_json_encode($m);

            if ($moodle_enrol_id && $enrol_plugin) {
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO $table (
                        moodle_enrol_id, moodle_course_id, enrol_plugin, name, status,
                        roleid, cost, currency, enrolstartdate, enrolenddate, enrolperiod,
                        expirynotify, expirythreshold, notifyall, category_id, default_status_id,
                        is_enrollment_fee, installments, data
                    )
                     VALUES (%d, %d, %s, %s, %d, %d, %f, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s)",
                    $moodle_enrol_id,
                    $course_id,
                    $enrol_plugin,
                    $name,
                    $status,
                    $roleid,
                    $cost,
                    $currency,
                    $enrolstartdate,
                    $enrolenddate,
                    $enrolperiod,
                    $expirynotify,
                    $expirythreshold,
                    $notifyall,
                    $category_id,
                    $default_status_id,
                    $is_enrollment_fee,
                    $installments,
                    $data
                ));
                $count++;
            }
        }

        return $count;
    }
}
