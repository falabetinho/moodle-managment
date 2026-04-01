<?php
/**
 * Moodle Courses Display Class
 * 
 * Trabalha com as tabelas sincronizadas do Moodle
 *
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_Courses {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('template_include', array($this, 'load_course_template'));
        add_filter('body_class', array($this, 'add_body_classes'));
        add_shortcode('moodle_cursos', array($this, 'render_courses_shortcode'));
        add_shortcode('moodle_carrossel', array($this, 'render_carousel_shortcode'));
    }

    /**
     * Load custom template for course archive
     */
    public function load_course_template($template) {
        if (is_post_type_archive('curso') || is_tax('categoria-curso')) {
            $custom_template = MOODLE_MANAGEMENT_PATH . 'templates/archive-cursos.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    /**
     * Add custom body classes
     */
    public function add_body_classes($classes) {
        if (is_post_type_archive('curso') || is_tax('categoria-curso')) {
            $classes[] = 'moodle-cursos';
        }
        return $classes;
    }

    /**
     * Get all categories from Moodle
     */
    public static function get_categories($parent_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'moodle_categories';
        
        // Root Moodle categories can be stored as NULL or 0.
        $query = "SELECT * FROM $table WHERE parent_id IS NULL OR parent_id = 0";
        
        if ($parent_id !== null) {
            $query = $wpdb->prepare("SELECT * FROM $table WHERE parent_id = %d", $parent_id);
        }
        
        $query .= " ORDER BY name ASC";
        
        return $wpdb->get_results($query);
    }

    /**
     * Get subcategories for a parent category
     */
    public static function get_subcategories($parent_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'moodle_categories';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE parent_id = %d ORDER BY name ASC",
            $parent_id
        ));
    }

    /**
     * Get courses by category including subcategories
     */
    public static function get_courses_by_category_tree($category_id, $include_subcategories = true) {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'moodle_courses';
        $categories_table = $wpdb->prefix . 'moodle_categories';

        if (!$include_subcategories) {
            // Apenas cursos da categoria especificada
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $courses_table WHERE category_id = %d AND visibility = 1 ORDER BY name ASC",
                $category_id
            ));
        }

        // Cursos da categoria e de todas as subcategorias
        $category_ids = self::get_all_subcategory_ids($category_id);
        
        if (empty($category_ids)) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $courses_table WHERE category_id IN ($placeholders) ORDER BY name ASC",
                ...$category_ids
            )
        );
    }

    /**
     * Get all subcategory IDs recursively
     */
    public static function get_all_subcategory_ids($category_id) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'moodle_categories';

        $ids = array((int) $category_id);

        // Get direct subcategories
        $subcategories = $wpdb->get_results($wpdb->prepare(
            "SELECT moodle_id FROM $categories_table WHERE parent_id = %d",
            $category_id
        ));

        foreach ($subcategories as $subcat) {
            // Recursively get all nested subcategories
            $ids = array_merge($ids, self::get_all_subcategory_ids($subcat->moodle_id));
        }

        return $ids;
    }

    /**
     * Delete a category and all related data (subcategories, courses and prices)
     */
    public static function delete_category_cascade($category_id) {
        $category_id = intval($category_id);

        if ($category_id <= 0) {
            return new WP_Error('invalid_category', __('Categoria inválida.', 'moodle-management'));
        }

        global $wpdb;

        $categories_table = $wpdb->prefix . 'moodle_categories';
        $courses_table = $wpdb->prefix . 'moodle_courses';
        $enrol_methods_table = $wpdb->prefix . 'moodle_enrol_methods';
        $enrollments_table = $wpdb->prefix . 'moodle_enrollments';

        $category_ids = array_values(array_unique(array_map('intval', self::get_all_subcategory_ids($category_id))));
        if (empty($category_ids)) {
            return new WP_Error('category_not_found', __('Categoria não encontrada.', 'moodle-management'));
        }

        $category_placeholders = implode(',', array_fill(0, count($category_ids), '%d'));

        $course_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT moodle_id FROM $courses_table WHERE category_id IN ($category_placeholders)",
                ...$category_ids
            )
        );
        $course_ids = array_values(array_unique(array_map('intval', $course_ids)));

        $deleted = array(
            'categories' => 0,
            'courses' => 0,
            'prices' => 0,
            'enrollments' => 0,
            'posts' => 0,
            'terms' => 0,
        );

        try {
            $wpdb->query('START TRANSACTION');

            if (!empty($course_ids)) {
                $course_placeholders = implode(',', array_fill(0, count($course_ids), '%d'));

                $deleted_prices = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $enrol_methods_table WHERE moodle_course_id IN ($course_placeholders)",
                        ...$course_ids
                    )
                );
                if ($deleted_prices === false) {
                    throw new Exception($wpdb->last_error ?: __('Erro ao excluir preços dos cursos.', 'moodle-management'));
                }
                $deleted['prices'] += (int) $deleted_prices;

                $deleted_enrollments = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $enrollments_table WHERE moodle_course_id IN ($course_placeholders)",
                        ...$course_ids
                    )
                );
                if ($deleted_enrollments === false) {
                    throw new Exception($wpdb->last_error ?: __('Erro ao excluir matrículas dos cursos.', 'moodle-management'));
                }
                $deleted['enrollments'] += (int) $deleted_enrollments;
            }

            $deleted_prices_by_category = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $enrol_methods_table WHERE category_id IN ($category_placeholders)",
                    ...$category_ids
                )
            );
            if ($deleted_prices_by_category === false) {
                throw new Exception($wpdb->last_error ?: __('Erro ao excluir preços por categoria.', 'moodle-management'));
            }
            $deleted['prices'] += (int) $deleted_prices_by_category;

            $deleted_courses = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $courses_table WHERE category_id IN ($category_placeholders)",
                    ...$category_ids
                )
            );
            if ($deleted_courses === false) {
                throw new Exception($wpdb->last_error ?: __('Erro ao excluir cursos da categoria.', 'moodle-management'));
            }
            $deleted['courses'] = (int) $deleted_courses;

            $deleted_categories = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $categories_table WHERE moodle_id IN ($category_placeholders)",
                    ...$category_ids
                )
            );
            if ($deleted_categories === false) {
                throw new Exception($wpdb->last_error ?: __('Erro ao excluir categorias.', 'moodle-management'));
            }
            $deleted['categories'] = (int) $deleted_categories;

            if (!empty($course_ids)) {
                $deleted['posts'] = self::delete_course_posts($course_ids);
            }

            $deleted['terms'] = self::delete_category_terms($category_ids);

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('category_delete_failed', $e->getMessage());
        }

        return $deleted;
    }

    /**
     * Delete WP posts linked to Moodle course IDs
     */
    private static function delete_course_posts($course_ids) {
        global $wpdb;

        $course_ids = array_values(array_unique(array_map('intval', $course_ids)));
        if (empty($course_ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($course_ids), '%d'));
        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'moodle_course_id' AND meta_value IN ($placeholders)",
                ...$course_ids
            )
        );

        $deleted_posts = 0;
        foreach ($post_ids as $post_id) {
            $deleted = wp_delete_post((int) $post_id, true);
            if ($deleted) {
                $deleted_posts++;
            }
        }

        return $deleted_posts;
    }

    /**
     * Delete WP terms linked to Moodle category IDs
     */
    private static function delete_category_terms($category_ids) {
        if (!taxonomy_exists('categoria-curso')) {
            return 0;
        }

        $category_ids = array_values(array_unique(array_map('intval', $category_ids)));
        if (empty($category_ids)) {
            return 0;
        }

        $term_ids = get_terms(array(
            'taxonomy' => 'categoria-curso',
            'hide_empty' => false,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'moodle_category_id',
                    'value' => $category_ids,
                    'compare' => 'IN',
                ),
            ),
        ));

        if (is_wp_error($term_ids) || empty($term_ids)) {
            return 0;
        }

        $deleted_terms = 0;
        foreach ($term_ids as $term_id) {
            $deleted = wp_delete_term((int) $term_id, 'categoria-curso');
            if (!is_wp_error($deleted)) {
                $deleted_terms++;
            }
        }

        return $deleted_terms;
    }

    /**
     * Check if category has direct subcategories
     */
    public static function has_subcategories($category_id) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'moodle_categories';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $categories_table WHERE parent_id = %d",
            $category_id
        ));

        return $count > 0;
    }

    /**
     * Get all courses from all categories in a path
     */
    public static function get_courses_by_path($path) {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'moodle_courses';
        $categories_table = $wpdb->prefix . 'moodle_categories';
        
        // Get all category IDs from path and its children
        $category_ids = self::get_category_ids_from_path($path);
        
        if (empty($category_ids)) {
            return array();
        }
        
        $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $courses_table WHERE category_id IN ($placeholders) ORDER BY name ASC",
                ...$category_ids
            )
        );
    }

    /**
     * Get all category IDs from path and children
     */
    public static function get_category_ids_from_path($path) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'moodle_categories';
        
        // Extract the last ID from path
        $path_parts = array_filter(explode('/', $path));
        if (empty($path_parts)) {
            return array();
        }
        
        $category_id = end($path_parts);
        
        // Get this category and all its children
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT moodle_id FROM $categories_table 
             WHERE moodle_id = %d OR path LIKE %s",
            $category_id,
            $wpdb->esc_like($path) . '%'
        ));
        
        return array_map('intval', $results);
    }

    /**
     * Search courses by name or shortname
     */
    public static function search_courses($search_term, $category_id = null) {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'moodle_courses';
        
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $courses_table 
             WHERE (name LIKE %s OR shortname LIKE %s)",
            $search_term,
            $search_term
        );
        
        if ($category_id !== null) {
            $query .= $wpdb->prepare(" AND category_id = %d", $category_id);
        }
        
        $query .= " ORDER BY name ASC";
        
        return $wpdb->get_results($query);
    }

    /**
     * Get category by ID
     */
    public static function get_category($category_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'moodle_categories';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE moodle_id = %d",
            $category_id
        ));
    }

    /**
     * Get all courses
     */
    public static function get_all_courses($limit = null, $offset = 0) {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'moodle_courses';
        
        $query = "SELECT * FROM $courses_table WHERE visibility = 1 ORDER BY name ASC";
        
        if ($limit !== null) {
            $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }
        
        return $wpdb->get_results($query);
    }

    /**
     * Get total courses count
     */
    public static function get_courses_count() {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'moodle_courses';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $courses_table WHERE visibility = 1");
    }

    /**
     * Render category dropdown options
     */
    public static function render_category_options($selected_category = '') {
        $categories = self::get_categories();
        
        foreach ($categories as $category) {
            echo sprintf(
                '<option value="%s" data-category-id="%d" %s>%s</option>',
                esc_attr($category->moodle_id),
                esc_attr($category->moodle_id),
                selected((string) $selected_category, (string) $category->moodle_id, false),
                esc_html($category->name)
            );
        }
    }

    /**
     * Recursively render subcategory options
     */
    public static function render_subcategory_options($parent_id, $depth = 0) {
        $subcategories = self::get_subcategories($parent_id);
        
        foreach ($subcategories as $subcat) {
            echo sprintf(
                '<option value="%s" data-category-id="%d">%s%s</option>',
                esc_attr($subcat->moodle_id),
                esc_attr($subcat->moodle_id),
                str_repeat('&nbsp;&nbsp;', $depth + 1),
                esc_html($subcat->name)
            );
            
            // Recursively get deeper subcategories
            self::render_subcategory_options($subcat->moodle_id, $depth + 1);
        }
    }

    /**
     * Get enrol methods for a specific course
     */
    public static function get_course_enrol_methods($course_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'moodle_enrol_methods';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE moodle_course_id = %d ORDER BY cost DESC",
            $course_id
        ));
    }

    /**
     * Get best price for a course (default price or promotional price)
     * Returns the promotional price if available, otherwise the default price
     * Promotional status: default_status_id = 1
     */
    public static function get_course_best_price($course_id) {
        $enrol_methods = self::get_course_enrol_methods($course_id);
        
        if (empty($enrol_methods)) {
            return null;
        }

        $promotional = null;
        $default = null;

        foreach ($enrol_methods as $method) {
            // Buscar por preço válido (cost não vazio)
            if (empty($method->cost)) {
                continue;
            }

            // Se é promocional (default_status_id = 1) e tem preço
            if (!empty($method->default_status_id) && intval($method->default_status_id) === 1) {
                $promotional = $method;
                break;
            }

            // Se é padrão (default_status_id = 0) e tem preço
            if (empty($default) && (empty($method->default_status_id) || intval($method->default_status_id) === 0)) {
                $default = $method;
            }
        }

        // Retornar promocional se existir, senão padrão
        return $promotional ?? $default;
    }

    /**
     * Render courses shortcode
     * 
     * Uso:
     * [moodle_cursos] - Todos os cursos
     * [moodle_cursos category_id="123"] - Cursos da categoria 123 e subcategorias
     * [moodle_cursos category_id="123" show_subcategories="false"] - Apenas da categoria 123
     */
    public function render_courses_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category_id' => null,
            'show_subcategories' => true,
            'show_title' => false,
            'title' => '',
            'color_scheme' => 'auto',
        ), $atts, 'moodle_cursos');

        // Armazenar parâmetros para usar no template
        global $moodle_cursos_shortcode_atts, $post;
        $moodle_cursos_shortcode_atts = $atts;
        
        // Armazenar URL da página atual para redirecionar filtros para ela
        $moodle_cursos_shortcode_atts['page_url'] = get_permalink($post->ID);

        ob_start();
        include MOODLE_MANAGEMENT_PATH . 'templates/archive-cursos.php';
        return ob_get_clean();
    }

    /**
     * Render carousel shortcode
     *
     * Uso:
     * [moodle_carrossel] - Todos os cursos em carrossel
     * [moodle_carrossel category_id="123"] - Carrossel de cursos da categoria 123
     * [moodle_carrossel limit="9" autoplay="true" autoplay_speed="5000"]
     */
    public function render_carousel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category_id'        => null,
            'show_subcategories' => true,
            'show_title'         => false,
            'title'              => '',
            'color_scheme'       => 'auto',
            'limit'              => 0,
            'autoplay'           => false,
            'autoplay_speed'     => 4000,
        ), $atts, 'moodle_carrossel');

        global $moodle_carrossel_shortcode_atts;
        $moodle_carrossel_shortcode_atts = $atts;
        $moodle_carrossel_shortcode_atts['instance_id'] = 'mcw' . substr( md5( uniqid( '', true ) ), 0, 8 );

        ob_start();
        include MOODLE_MANAGEMENT_PATH . 'templates/carousel-cursos.php';
        return ob_get_clean();
    }

    /**
     * Generate gradient colors for a course
     * Uses only blue color palette for consistency
     * 
     * Supports color schemes:
     * - 'auto': Generate based on course ID (consistent colors)
     * - 'category': Use category colors
     * - 'custom': Use custom colors from course meta
     * 
     * @param int $course_id Course ID
     * @param string $color_scheme Color scheme type
     * @return string CSS gradient value
     */
    public static function generate_course_gradient($course_id, $color_scheme = 'auto') {
        // Paleta de tons de azul (hue entre 200-240 = azul)
        $blue_palette = array(
            array('#0078d4', '#005a9e'), // Azure Blue
            array('#1890ff', '#0050b3'), // Bright Blue
            array('#2b88d8', '#0063b1'), // Sky Blue
            array('#0086bf', '#005b94'), // Ocean Blue
            array('#3399ff', '#1a66cc'), // Light Blue
            array('#0066cc', '#004c99'), // Royal Blue
            array('#4da6ff', '#0080ff'), // Soft Blue
            array('#0099cc', '#006699'), // Teal Blue
            array('#2e8bc0', '#1a5490'), // Steel Blue
            array('#006ba6', '#004770'), // Deep Blue
        );
        
        // Usar semente baseada no ID para escolha consistente
        $index = abs(crc32($course_id)) % count($blue_palette);
        $colors = $blue_palette[$index];

        return sprintf('linear-gradient(135deg, %s 0%%, %s 100%%)', $colors[0], $colors[1]);
    }

    /**
     * Get course gradient - always uses blue palette
     * 
     * @param int $course_id Moodle course ID
     * @param string $color_scheme Color scheme (kept for compatibility)
     * @return string CSS gradient value
     */
    public static function get_course_gradient($course_id, $color_scheme = 'auto') {
        return self::generate_course_gradient($course_id, $color_scheme);
    }
}

