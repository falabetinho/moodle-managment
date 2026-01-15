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
            'enrollments' => __('Importar Enrollments', 'moodle-management')
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
        add_action('wp_ajax_moodle_sync_enrollments', array($this, 'ajax_sync_enrollments'));
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
                    case 'enrollments':
                        $this->render_enrollments_tab();
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
        $categories = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
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
                            <th><?php echo esc_html(__('Descrição', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Data de Atualização', 'moodle-management')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) : ?>
                            <tr>
                                <td><?php echo esc_html($category->moodle_id); ?></td>
                                <td><?php echo esc_html($category->name); ?></td>
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
    private function render_enrollments_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'moodle_enrollments';
        $enrollments = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");
        ?>
        <div class="moodle-tab-content">
            <h2><?php echo esc_html(__('Importar Enrollments do Moodle', 'moodle-management')); ?></h2>
            
            <p>
                <button type="button" class="button button-primary" id="sync-enrollments">
                    <?php echo esc_html(__('Sincronizar Enrollments do Moodle', 'moodle-management')); ?>
                </button>
            </p>

            <div id="sync-enrollments-result" class="notice" style="display:none;"></div>

            <?php if (empty($enrollments)) : ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html(__('Nenhum enrollment sincronizado ainda. Clique no botão acima para sincronizar.', 'moodle-management')); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(__('ID Usuário Moodle', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('ID Curso Moodle', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Role', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Método de Inscrição', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Status', 'moodle-management')); ?></th>
                            <th><?php echo esc_html(__('Data', 'moodle-management')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment) : ?>
                            <tr>
                                <td><?php echo esc_html($enrollment->moodle_user_id); ?></td>
                                <td><?php echo esc_html($enrollment->moodle_course_id); ?></td>
                                <td><?php echo esc_html($enrollment->role); ?></td>
                                <td><?php echo esc_html($enrollment->enrol_method); ?></td>
                                <td>
                                    <?php 
                                    $status = $enrollment->imported ? __('Importado', 'moodle-management') : __('Não Importado', 'moodle-management');
                                    echo esc_html($status);
                                    ?>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($enrollment->created_at))); ?></td>
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
                    "INSERT INTO $table (moodle_id, name, description, parent_id) 
                     VALUES (%d, %s, %s, %d)
                     ON DUPLICATE KEY UPDATE name = %s, description = %s, parent_id = %d",
                    $category['id'],
                    $category['name'],
                    isset($category['description']) ? $category['description'] : '',
                    isset($category['parent']) ? $category['parent'] : 0,
                    $category['name'],
                    isset($category['description']) ? $category['description'] : '',
                    isset($category['parent']) ? $category['parent'] : 0
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
                    "INSERT INTO $table (moodle_id, name, shortname, description, category_id) 
                     VALUES (%d, %s, %s, %s, %d)
                     ON DUPLICATE KEY UPDATE name = %s, shortname = %s, description = %s, category_id = %d",
                    $course['id'],
                    $course['fullname'],
                    $course['shortname'],
                    isset($course['summary']) ? $course['summary'] : '',
                    isset($course['categoryid']) ? $course['categoryid'] : 0,
                    $course['fullname'],
                    $course['shortname'],
                    isset($course['summary']) ? $course['summary'] : '',
                    isset($course['categoryid']) ? $course['categoryid'] : 0
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
    public function ajax_sync_enrollments() {
        check_ajax_referer('moodle_management_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Sem permissão', 'moodle-management')));
        }

        wp_send_json_success(array(
            'message' => __('Funcionalidade de sincronização de enrollments em desenvolvimento.', 'moodle-management')
        ));
    }
}
