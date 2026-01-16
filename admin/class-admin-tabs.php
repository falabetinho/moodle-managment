<?php
/**
 * Admin Tabs Class
 *
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_Admin_Tabs {

    /**
     * Available tabs
     */
    private $tabs = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->tabs = array(
            'connection' => __('Configuração de Conexão', 'moodle-management'),
            'categories' => __('Gerenciar Categorias', 'moodle-management'),
            'courses' => __('Importar Cursos', 'moodle-management'),
            'enrol' => __('Importar Métodos de Enrol', 'moodle-management'),
        );
        
        $this->init_hooks();
    }

    /**
     * Initialize hooks for AJAX requests
     */
    private function init_hooks() {
        add_action('wp_ajax_moodle_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_moodle_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_moodle_sync_categories', array($this, 'ajax_sync_categories'));
        add_action('wp_ajax_moodle_sync_courses', array($this, 'ajax_sync_courses'));
        add_action('wp_ajax_moodle_sync_enrol_methods', array($this, 'ajax_sync_enrol_methods'));
        add_action('wp_ajax_moodle_sync_all_enrol_methods', array($this, 'ajax_sync_all_enrol_methods'));
    }

    /**
     * Calculate depth based on Moodle path
     */
    private function get_category_depth($path) {
        if (empty($path)) {
            return 0;
        }

        $parts = array_filter(explode('/', $path), 'strlen');
        $depth = count($parts) - 1;

        return $depth < 0 ? 0 : $depth;
    }

    /**
     * Render indented name for tree-like view
     */
    private function render_category_name($name, $depth) {
        $indent = str_repeat('&mdash; ', $depth);
        return wp_kses_post($indent . esc_html($name));
    }

    /**
     * Render admin page
     */
    public function render() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'connection';
        
        if (!array_key_exists($current_tab, $this->tabs)) {
            $current_tab = 'connection';
        }
        ?>
        <div class="wrap moodle-management-wrap">
            <h1><?php echo esc_html(__('Moodle Management', 'moodle-management')); ?></h1>
            
            <nav class="nav-tab-wrapper wp-clearfix">
                <?php foreach ($this->tabs as $tab_key => $tab_label) : ?>
                    <a href="?page=moodle-management&tab=<?php echo esc_attr($tab_key); ?>" 
                       class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content">
                <?php 
                switch ($current_tab) {
                    case 'connection':
                        $this->render_connection_tab();
                        break;
                    case 'categories':
                        $this->render_categories_tab();
                        break;
                    case 'courses':
                        $this->render_courses_tab();
                        break;
                    case 'enrol':
                        $this->render_enrol_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render connection configuration tab
     */
    private function render_connection_tab() {
        $settings = Moodle_API::get_settings();
        $api = new Moodle_API();
        ?>
        <div class="moodle-tab-content">
            <h2><?php echo esc_html(__('Configuração de Conexão ao Moodle', 'moodle-management')); ?></h2>
            
            <form id="moodle-connection-form" method="post" action="">
                <?php wp_nonce_field('moodle_management_nonce', 'nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="moodle_base_url"><?php echo esc_html(__('URL Base do Webservice', 'moodle-management')); ?></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="moodle_base_url" 
                                   name="base_url" 
                                   value="<?php echo esc_attr($settings['base_url'] ?? ''); ?>" 
                                   class="regular-text"
                                   placeholder="https://seu-moodle.com.br"
                                   required>
                            <p class="description">
                                <?php echo esc_html(__('Ex: https://seu-moodle.com.br', 'moodle-management')); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="moodle_username"><?php echo esc_html(__('Usuário', 'moodle-management')); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="moodle_username" 
                                   name="username" 
                                   value="<?php echo esc_attr($settings['username'] ?? ''); ?>" 
                                   class="regular-text"
                                   required>
                            <p class="description">
                                <?php echo esc_html(__('Usuário com permissão de webservice', 'moodle-management')); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="moodle_token"><?php echo esc_html(__('Token', 'moodle-management')); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="moodle_token" 
                                   name="token" 
                                   value="<?php echo esc_attr($settings['token'] ?? ''); ?>" 
                                   class="regular-text"
                                   required>
                            <p class="description">
                                <?php echo esc_html(__('Token de acesso do webservice', 'moodle-management')); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary" id="save-settings">
                        <?php echo esc_html(__('Salvar Configurações', 'moodle-management')); ?>
                    </button>
                    <button type="button" class="button" id="test-connection">
                        <?php echo esc_html(__('Testar Conexão', 'moodle-management')); ?>
                    </button>
                </p>
            </form>

            <div id="connection-result" class="notice" style="display:none;"></div>

            <?php if ($api->is_configured()) : ?>
                <div class="notice notice-success">
                    <p><?php echo esc_html(__('Configurações já foram preenchidas.', 'moodle-management')); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render categories management tab
     */
    private function render_categories_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'moodle_categories';
        $categories = $wpdb->get_results("SELECT * FROM $table ORDER BY path ASC, parent_id ASC, name ASC");
        ?>
        <div class="moodle-tab-content">
            <h2><?php echo esc_html(__('Gerenciar Categorias do Moodle', 'moodle-management')); ?></h2>
            
            <p>
                <button type="button" class="button button-primary" id="sync-categories">
                    <?php echo esc_html(__('Sincronizar Categorias do Moodle', 'moodle-management')); ?>
                </button>
            </p>

            <div id="sync-categories-result" class="notice" style="display:none;"></div>

            <?php if (empty($categories)) : ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html(__('Nenhuma categoria sincronizada ainda. Clique no botão acima para sincronizar.', 'moodle-management')); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(__('ID Moodle', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Nome', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Path', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Parent', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Descrição', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Data de Atualização', 'moodle-management')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) : ?>
                            <?php $depth = $this->get_category_depth($category->path); ?>
                            <tr>
                                <td><?php echo esc_html($category->moodle_id); ?></td>
                                <td><?php echo $this->render_category_name($category->name, $depth); ?></td>
                                <td><?php echo esc_html($category->path); ?></td>
                                <td><?php echo esc_html($category->parent_id); ?></td>
                                <td><?php echo wp_kses_post(wp_trim_words($category->description, 20)); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($category->updated_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render courses import tab
     */
    private function render_courses_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'moodle_courses';
        $courses = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
        ?>
        <div class="moodle-tab-content">
            <h2><?php echo esc_html(__('Importar Cursos do Moodle', 'moodle-management')); ?></h2>
            
            <p>
                <button type="button" class="button button-primary" id="sync-courses">
                    <?php echo esc_html(__('Sincronizar Cursos do Moodle', 'moodle-management')); ?>
                </button>
            </p>

            <div id="sync-courses-result" class="notice" style="display:none;"></div>

            <?php if (empty($courses)) : ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html(__('Nenhum curso sincronizado ainda. Clique no botão acima para sincronizar.', 'moodle-management')); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(__('ID Moodle', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Nome', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Nome Curto', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Status', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Data de Atualização', 'moodle-management')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course) : ?>
                            <tr>
                                <td><?php echo esc_html($course->moodle_id); ?></td>
                                <td><?php echo esc_html($course->name); ?></td>
                                <td><?php echo esc_html($course->shortname); ?></td>
                                <td>
                                    <?php 
                                    $status = $course->imported ? __('Importado', 'moodle-management') : __('Não Importado', 'moodle-management');
                                    echo esc_html($status);
                                    ?>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($course->updated_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render enrollments import tab
     */
    private function render_enrol_tab() {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'moodle_courses';
        $methods_table = $wpdb->prefix . 'moodle_enrol_methods';
        $courses = $wpdb->get_results("SELECT moodle_id, name FROM $courses_table ORDER BY name ASC");

        $selected_course = isset($_GET['course']) ? intval($_GET['course']) : 0;
        $methods = array();
        if ($selected_course) {
            $methods = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $methods_table WHERE moodle_course_id = %d ORDER BY enrol_plugin ASC",
                $selected_course
            ));
        }
        ?>
        <div class="moodle-tab-content">
            <h2><?php echo esc_html(__('Importar Métodos de Enrol do Moodle', 'moodle-management')); ?></h2>

            <p style="margin-bottom: 20px;">
                <button type="button" class="button button-secondary" id="sync-all-enrol-methods">
                    <?php echo esc_html(__('Sincronizar Todos os Enrol de Todos os Cursos', 'moodle-management')); ?>
                </button>
            </p>

            <div id="sync-all-enrol-result" class="notice" style="display:none;"></div>

            <hr style="margin: 20px 0;">

            <h3><?php echo esc_html(__('Sincronizar Enrol de um Curso Específico', 'moodle-management')); ?></h3>

            <form method="get" action="">
                <input type="hidden" name="page" value="moodle-management" />
                <input type="hidden" name="tab" value="enrol" />
                <label for="enrol-course-select"><?php echo esc_html(__('Selecione o Curso', 'moodle-management')); ?></label>
                <select id="enrol-course-select" name="course">
                    <option value="0"><?php echo esc_html(__('— Selecione —', 'moodle-management')); ?></option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo esc_attr($course->moodle_id); ?>" <?php selected($selected_course, $course->moodle_id); ?>>
                            <?php echo esc_html($course->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php echo esc_html(__('Carregar', 'moodle-management')); ?></button>
            </form>

            <p style="margin-top: 10px;">
                <button type="button" class="button button-primary" id="sync-enrol-methods" <?php disabled(!$selected_course); ?>>
                    <?php echo esc_html(__('Sincronizar Métodos de Enrol do Moodle', 'moodle-management')); ?>
                </button>
            </p>

            <div id="sync-enrol-result" class="notice" style="display:none;"></div>

            <?php if ($selected_course && empty($methods)) : ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html(__('Nenhum método sincronizado ainda. Clique acima para sincronizar.', 'moodle-management')); ?></p>
                </div>
            <?php elseif ($selected_course) : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(__('ID Enrol', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Plugin', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Nome', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Status', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Atualizado', 'moodle-management')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($methods as $method) : ?>
                            <tr>
                                <td><?php echo esc_html($method->moodle_enrol_id); ?></td>
                                <td><?php echo esc_html($method->enrol_plugin); ?></td>
                                <td><?php echo esc_html($method->name); ?></td>
                                <td><?php echo esc_html($method->status); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($method->updated_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Save connection settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('moodle_management_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Sem permissão', 'moodle-management')));
        }

        $base_url = isset($_POST['base_url']) ? sanitize_text_field($_POST['base_url']) : '';
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';

        Moodle_API::save_settings($base_url, $username, $token);

        wp_send_json_success(array(
            'message' => __('Configurações salvas com sucesso!', 'moodle-management')
        ));
    }

    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('moodle_management_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Sem permissão', 'moodle-management')));
        }

        $api = new Moodle_API();
        $result = $api->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Sync categories from Moodle
     */
    public function ajax_sync_categories() {
        check_ajax_referer('moodle_management_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Sem permissão', 'moodle-management')));
        }

        try {
            $api = new Moodle_API();
            
            if (!$api->is_configured()) {
                wp_send_json_error(array(
                    'message' => __('Conexão não configurada', 'moodle-management')
                ));
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

            wp_send_json_success(array(
                'message' => sprintf(__('%d categorias sincronizadas com sucesso!', 'moodle-management'), $count)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX: Sync courses from Moodle
     */
    public function ajax_sync_courses() {
        check_ajax_referer('moodle_management_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Sem permissão', 'moodle-management')));
        }

        try {
            $api = new Moodle_API();
            
            if (!$api->is_configured()) {
                wp_send_json_error(array(
                    'message' => __('Conexão não configurada', 'moodle-management')
                ));
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

            wp_send_json_success(array(
                'message' => sprintf(__('%d cursos sincronizados com sucesso!', 'moodle-management'), $count)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX: Sync enrollments from Moodle
     */
    public function ajax_sync_enrol_methods() {
        check_ajax_referer('moodle_management_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Sem permissão', 'moodle-management')));
        }

        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        if (!$course_id) {
            wp_send_json_error(array('message' => __('Curso inválido', 'moodle-management')));
        }

        try {
            $api = new Moodle_API();
            if (!$api->is_configured()) {
                wp_send_json_error(array('message' => __('Conexão não configurada', 'moodle-management')));
            }

            $methods = $api->get_course_enrolment_methods($course_id);
            global $wpdb;
            $table = $wpdb->prefix . 'moodle_enrol_methods';

            $count = 0;
            foreach ($methods as $m) {
                // Map all known fields from the API response
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
                         VALUES (%d, %d, %s, %s, %d, %d, %f, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s)
                         ON DUPLICATE KEY UPDATE 
                            enrol_plugin = %s, name = %s, status = %d,
                            roleid = %d, cost = %f, currency = %s, enrolstartdate = %d, enrolenddate = %d,
                            enrolperiod = %d, expirynotify = %d, expirythreshold = %d, notifyall = %d,
                            category_id = %d, default_status_id = %d, is_enrollment_fee = %d, installments = %d, data = %s",
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
                        $data,
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

            wp_send_json_success(array(
                'message' => sprintf(__('%d métodos de enrol sincronizados!', 'moodle-management'), $count)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * AJAX: Sync all enrol methods from all courses
     */
    public function ajax_sync_all_enrol_methods() {
        check_ajax_referer('moodle_management_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Sem permissão', 'moodle-management')));
        }

        try {
            $api = new Moodle_API();
            if (!$api->is_configured()) {
                wp_send_json_error(array('message' => __('Conexão não configurada', 'moodle-management')));
            }

            global $wpdb;
            $courses_table = $wpdb->prefix . 'moodle_courses';
            $enrol_table = $wpdb->prefix . 'moodle_enrol_methods';

            // Get all courses from database
            $courses = $wpdb->get_results("SELECT moodle_id FROM $courses_table");
            
            if (empty($courses)) {
                wp_send_json_error(array('message' => __('Nenhum curso encontrado. Sincronize os cursos primeiro.', 'moodle-management')));
            }

            $total_count = 0;
            $total_courses = 0;
            $errors = array();

            foreach ($courses as $course) {
                try {
                    $methods = $api->get_course_enrolment_methods($course->moodle_id);
                    $course_count = 0;

                    foreach ($methods as $m) {
                        // Map all known fields from the API response
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
                                "INSERT INTO $enrol_table (
                                    moodle_enrol_id, moodle_course_id, enrol_plugin, name, status,
                                    roleid, cost, currency, enrolstartdate, enrolenddate, enrolperiod,
                                    expirynotify, expirythreshold, notifyall, category_id, default_status_id,
                                    is_enrollment_fee, installments, data
                                )
                                 VALUES (%d, %d, %s, %s, %d, %d, %f, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s)
                                 ON DUPLICATE KEY UPDATE 
                                    enrol_plugin = %s, name = %s, status = %d,
                                    roleid = %d, cost = %f, currency = %s, enrolstartdate = %d, enrolenddate = %d,
                                    enrolperiod = %d, expirynotify = %d, expirythreshold = %d, notifyall = %d,
                                    category_id = %d, default_status_id = %d, is_enrollment_fee = %d, installments = %d, data = %s",
                                $moodle_enrol_id,
                                $course->moodle_id,
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
                                $data,
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
                            $course_count++;
                        }
                    }

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

            wp_send_json_success(array('message' => $message));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}
