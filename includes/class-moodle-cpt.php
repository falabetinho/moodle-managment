<?php
/**
 * Moodle Custom Post Type and Taxonomy
 *
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_CPT {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('template_include', array($this, 'load_course_template'));
    }

    /**
     * Register Cursos post type
     */
    public function register_post_type() {
        $args = array(
            'label' => __('Cursos', 'moodle-management'),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'curso',
                'with_front' => false
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'taxonomies' => array('categoria-curso'),
            'show_in_rest' => true,
            'rest_base' => 'cursos',
        );

        register_post_type('curso', $args);
    }

    /**
     * Register Categoria Curso taxonomy
     */
    public function register_taxonomy() {
        $args = array(
            'label' => __('Categorias de Cursos', 'moodle-management'),
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'categoria-curso',
                'with_front' => false
            ),
            'show_in_rest' => true,
            'rest_base' => 'categorias-cursos',
        );

        register_taxonomy('categoria-curso', 'curso', $args);
    }

    /**
     * Load custom template for curso archive
     */
    public function load_course_template($template) {
        if (is_post_type_archive('curso') || is_tax('categoria-curso')) {
            $custom_template = MOODLE_MANAGEMENT_PATH . 'templates/archive-curso.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    /**
     * Create or update category as term
     */
    public static function sync_category_to_term($moodle_category_id, $name, $parent_term_id = 0, $slug = '') {
        if (empty($slug)) {
            $slug = sanitize_title($name);
        }

        // Check if term already exists
        $term = term_exists($slug, 'categoria-curso');
        
        if ($term) {
            // Update existing term
            $term_id = $term['term_id'];
            wp_update_term($term_id, 'categoria-curso', array(
                'name' => $name,
                'slug' => $slug,
                'parent' => $parent_term_id,
                'description' => sprintf(__('Categoria importada do Moodle (ID: %d)', 'moodle-management'), $moodle_category_id)
            ));
        } else {
            // Create new term
            $result = wp_insert_term($name, 'categoria-curso', array(
                'slug' => $slug,
                'parent' => $parent_term_id,
                'description' => sprintf(__('Categoria importada do Moodle (ID: %d)', 'moodle-management'), $moodle_category_id)
            ));
            
            if (is_wp_error($result)) {
                return false;
            }
            
            $term_id = $result['term_id'];
        }

        // Store Moodle category ID in term meta
        update_term_meta($term_id, 'moodle_category_id', $moodle_category_id);
        
        return $term_id;
    }

    /**
     * Create course post from Moodle course
     */
    public static function create_course_post($moodle_course_id, $course_data, $category_term_id = 0) {
        global $wpdb;
        
        // Check if post already exists
        $existing_post = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_type = 'curso' AND post_content LIKE %s LIMIT 1",
            '%moodle_course_id:' . $moodle_course_id . '%'
        ));

        $post_args = array(
            'post_type' => 'curso',
            'post_title' => $course_data['name'],
            'post_content' => isset($course_data['description']) ? $course_data['description'] : '',
            'post_excerpt' => isset($course_data['shortname']) ? $course_data['shortname'] : '',
            'post_status' => 'publish',
        );

        if ($existing_post) {
            $post_args['ID'] = $existing_post;
            $post_id = wp_update_post($post_args);
        } else {
            $post_id = wp_insert_post($post_args);
        }

        if ($post_id && !is_wp_error($post_id)) {
            // Add course to category
            if ($category_term_id) {
                wp_set_post_terms($post_id, array($category_term_id), 'categoria-curso', false);
            }

            // Store Moodle course ID in post meta
            update_post_meta($post_id, 'moodle_course_id', $moodle_course_id);
            update_post_meta($post_id, 'course_shortname', isset($course_data['shortname']) ? $course_data['shortname'] : '');
            update_post_meta($post_id, 'course_visibility', isset($course_data['visibility']) ? $course_data['visibility'] : 1);
            
            return $post_id;
        }

        return false;
    }

    /**
     * Get category parent by path
     */
    public static function get_category_parent_by_path($path) {
        if (empty($path)) {
            return 0;
        }

        global $wpdb;
        
        // The path is a string with category IDs separated by /
        $path_parts = array_filter(explode('/', $path));
        
        if (count($path_parts) <= 1) {
            return 0;
        }

        // Get the last but one ID (parent)
        $parent_moodle_id = $path_parts[count($path_parts) - 2];

        $parent_term = get_terms(array(
            'taxonomy' => 'categoria-curso',
            'meta_key' => 'moodle_category_id',
            'meta_value' => $parent_moodle_id,
            'hide_empty' => false
        ));

        return !empty($parent_term) ? $parent_term[0]->term_id : 0;
    }
}
