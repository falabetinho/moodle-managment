<?php
/**
 * Category Colors Admin
 * Gerencia customização de cores por categoria
 * 
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_Category_Colors {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('moodle_management_admin_tabs', array($this, 'register_tab'));
        add_action('moodle_management_render_tab_category_colors', array($this, 'render_tab'));
        add_action('wp_ajax_moodle_save_category_colors', array($this, 'handle_save_colors'));
    }

    /**
     * Register the tab
     */
    public function register_tab() {
        return array(
            'id' => 'category_colors',
            'title' => __('Cores das Categorias', 'moodle-management'),
            'icon' => 'dashicons-admin-appearance',
        );
    }

    /**
     * Handle AJAX save
     */
    public function handle_save_colors() {
        check_ajax_referer('moodle_category_colors_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'moodle-management'));
        }

        $category_id = intval($_POST['category_id'] ?? 0);
        $color1 = sanitize_hex_color($_POST['color1'] ?? '');
        $color2 = sanitize_hex_color($_POST['color2'] ?? '');

        if (!$category_id) {
            wp_send_json_error(__('Categoria inválida.', 'moodle-management'));
        }

        // Salvar as cores como metadata da categoria
        update_term_meta($category_id, '_course_gradient_color1', $color1);
        update_term_meta($category_id, '_course_gradient_color2', $color2);

        wp_send_json_success(array(
            'message' => __('Cores salvadas com sucesso!', 'moodle-management'),
        ));
    }

    /**
     * Render the admin tab
     */
    public function render_tab() {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'moodle_categories';

        // Obter categorias
        $categories = $wpdb->get_results(
            "SELECT moodle_id, name, parent_id FROM $categories_table ORDER BY parent_id, name ASC"
        );

        ?>
        <div class="moodle-admin-section">
            <h2><?php echo esc_html(__('Customizar Cores das Categorias', 'moodle-management')); ?></h2>
            
            <p class="description">
                <?php echo esc_html(__('Customize as cores dos gradientes para cada categoria. Isso será aplicado a todos os cursos dessa categoria.', 'moodle-management')); ?>
            </p>

            <div class="category-colors-container">
                <?php
                $parents = array();
                $children = array();

                // Organizar categorias por parent
                foreach ($categories as $cat) {
                    if ($cat->parent_id == 0) {
                        $parents[$cat->moodle_id] = $cat;
                    } else {
                        if (!isset($children[$cat->parent_id])) {
                            $children[$cat->parent_id] = array();
                        }
                        $children[$cat->parent_id][] = $cat;
                    }
                }

                // Renderizar categorias principais e subcategorias
                foreach ($parents as $parent_cat) {
                    $this->render_category_color_form($parent_cat);

                    // Renderizar subcategorias
                    if (isset($children[$parent_cat->moodle_id])) {
                        echo '<div class="subcategories-wrapper">';
                        foreach ($children[$parent_cat->moodle_id] as $sub_cat) {
                            $this->render_category_color_form($sub_cat, true);
                        }
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>

        <style>
            .category-colors-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }

            .category-color-form {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            }

            .category-color-form.is-subcategory {
                margin-left: 20px;
                background: #f5f5f5;
                border-left: 4px solid #0078d4;
            }

            .category-color-form h3 {
                margin: 0 0 15px 0;
                font-size: 14px;
                color: #333;
                font-weight: 600;
            }

            .color-inputs {
                display: flex;
                gap: 10px;
                align-items: flex-end;
                margin-bottom: 15px;
            }

            .color-input-group {
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .color-input-group label {
                font-size: 12px;
                font-weight: 500;
                margin-bottom: 5px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .color-input-group input[type="color"] {
                width: 100%;
                height: 40px;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
            }

            .color-preview {
                width: 100%;
                height: 60px;
                border-radius: 4px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
            }

            .category-color-form button {
                width: 100%;
                padding: 10px;
                background: #0078d4;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
                transition: all 0.3s ease;
            }

            .category-color-form button:hover {
                background: #106ebe;
            }

            .category-color-form button:disabled {
                background: #999;
                cursor: not-allowed;
            }

            .message {
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                margin-top: 10px;
                display: none;
            }

            .message.success {
                background: #e6ffe6;
                color: #107c10;
                border: 1px solid #107c10;
                display: block;
            }

            .message.error {
                background: #ffe6e6;
                color: #da3b01;
                border: 1px solid #da3b01;
                display: block;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Preview das cores
            $(document).on('change', '.color-input', function() {
                var $form = $(this).closest('.category-color-form');
                var color1 = $form.find('.color1').val();
                var color2 = $form.find('.color2').val();
                
                if (color1 && color2) {
                    var gradient = 'linear-gradient(135deg, ' + color1 + ' 0%, ' + color2 + ' 100%)';
                    $form.find('.color-preview').css('background', gradient);
                }
            });

            // Salvar cores
            $(document).on('click', '.save-category-colors', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $form = $btn.closest('.category-color-form');
                var categoryId = $form.data('category-id');
                var color1 = $form.find('.color1').val();
                var color2 = $form.find('.color2').val();

                if (!color1 || !color2) {
                    $form.find('.message').removeClass('success').addClass('error').text('<?php echo esc_js(__('Selecione ambas as cores.', 'moodle-management')); ?>').show();
                    return;
                }

                $btn.prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'moodle_save_category_colors',
                    nonce: '<?php echo wp_create_nonce('moodle_category_colors_nonce'); ?>',
                    category_id: categoryId,
                    color1: color1,
                    color2: color2
                }, function(response) {
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        $form.find('.message').removeClass('error').addClass('success').text(response.data.message).show();
                        setTimeout(function() {
                            $form.find('.message').fadeOut();
                        }, 3000);
                    } else {
                        $form.find('.message').removeClass('success').addClass('error').text(response.data).show();
                    }
                });
            });

            // Trigger preview on load
            $('.color-input').trigger('change');
        });
        </script>
        <?php
    }

    /**
     * Render individual category color form
     */
    private function render_category_color_form($category, $is_subcategory = false) {
        $color1 = get_term_meta($category->moodle_id, '_course_gradient_color1', true) ?: '#0078d4';
        $color2 = get_term_meta($category->moodle_id, '_course_gradient_color2', true) ?: '#50e6ff';

        $class = $is_subcategory ? 'category-color-form is-subcategory' : 'category-color-form';
        ?>
        <div class="<?php echo esc_attr($class); ?>" data-category-id="<?php echo esc_attr($category->moodle_id); ?>">
            <h3><?php echo esc_html($category->name); ?></h3>
            
            <div class="color-preview" style="background: linear-gradient(135deg, <?php echo esc_attr($color1); ?> 0%, <?php echo esc_attr($color2); ?> 100%);"></div>
            
            <div class="color-inputs">
                <div class="color-input-group">
                    <label><?php echo esc_html(__('Cor 1', 'moodle-management')); ?></label>
                    <input type="color" class="color-input color1" value="<?php echo esc_attr($color1); ?>">
                </div>
                <div class="color-input-group">
                    <label><?php echo esc_html(__('Cor 2', 'moodle-management')); ?></label>
                    <input type="color" class="color-input color2" value="<?php echo esc_attr($color2); ?>">
                </div>
            </div>
            
            <button type="button" class="save-category-colors">
                <?php echo esc_html(__('Salvar Cores', 'moodle-management')); ?>
            </button>
            
            <div class="message"></div>
        </div>
        <?php
    }

    /**
     * Get category colors
     */
    public static function get_category_colors($category_id) {
        $color1 = get_term_meta($category_id, '_course_gradient_color1', true);
        $color2 = get_term_meta($category_id, '_course_gradient_color2', true);

        return array(
            'color1' => $color1 ?: '#0078d4',
            'color2' => $color2 ?: '#50e6ff',
        );
    }
}

// Instantiate
new Moodle_Category_Colors();
