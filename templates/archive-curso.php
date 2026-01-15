<?php
/**
 * Archive template for Cursos
 * 
 * @package Moodle_Management
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <header class="page-header">
            <h1 class="page-title">
                <?php
                if (is_post_type_archive('curso')) {
                    echo esc_html(__('Cursos', 'moodle-management'));
                } elseif (is_tax('categoria-curso')) {
                    echo single_term_title();
                }
                ?>
            </h1>
            <?php the_archive_description('<div class="taxonomy-description">', '</div>'); ?>
        </header>

        <div class="moodle-courses-container">
            <!-- Filtros e Busca -->
            <div class="courses-filters">
                <div class="filter-row">
                    <!-- Filtro por Categoria -->
                    <div class="filter-categories">
                        <label for="categoria-filtro"><?php echo esc_html(__('Filtrar por Categoria:', 'moodle-management')); ?></label>
                        <select id="categoria-filtro" class="categoria-dropdown">
                            <option value=""><?php echo esc_html(__('Todas as categorias', 'moodle-management')); ?></option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'categoria-curso',
                                'hide_empty' => false,
                                'parent' => 0, // Only top-level categories
                            ));

                            foreach ($categories as $category) {
                                $subcats = get_terms(array(
                                    'taxonomy' => 'categoria-curso',
                                    'parent' => $category->term_id,
                                    'hide_empty' => false,
                                ));
                                
                                echo sprintf(
                                    '<optgroup label="%s">',
                                    esc_attr($category->name)
                                );
                                
                                echo sprintf(
                                    '<option value="%s">%s</option>',
                                    esc_attr($category->slug),
                                    esc_html($category->name)
                                );

                                foreach ($subcats as $subcat) {
                                    echo sprintf(
                                        '<option value="%s">&nbsp;&nbsp;%s</option>',
                                        esc_attr($subcat->slug),
                                        esc_html($subcat->name)
                                    );
                                }
                                
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Busca por Nome -->
                    <div class="filter-search">
                        <input 
                            type="text" 
                            id="curso-search" 
                            class="curso-search-input" 
                            placeholder="<?php echo esc_attr(__('Buscar cursos...', 'moodle-management')); ?>"
                        />
                    </div>
                </div>
            </div>

            <!-- Lista de Cursos -->
            <div class="courses-list">
                <?php
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                $args = array(
                    'post_type' => 'curso',
                    'posts_per_page' => 12,
                    'paged' => $paged,
                    'orderby' => 'title',
                    'order' => 'ASC',
                );

                // Add category filter if selected
                if (is_tax('categoria-curso')) {
                    $term = get_queried_object();
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'categoria-curso',
                            'field' => 'term_id',
                            'terms' => $term->term_id,
                            'include_children' => true,
                        ),
                    );
                }

                $courses_query = new WP_Query($args);

                if ($courses_query->have_posts()) {
                    echo '<div class="courses-grid">';
                    
                    while ($courses_query->have_posts()) {
                        $courses_query->the_post();
                        
                        // Get course metadata
                        $moodle_course_id = get_post_meta(get_the_ID(), 'moodle_course_id', true);
                        $shortname = get_post_meta(get_the_ID(), 'course_shortname', true);
                        $visibility = get_post_meta(get_the_ID(), 'course_visibility', true);
                        $categories = get_the_terms(get_the_ID(), 'categoria-curso');
                        
                        ?>
                        <div class="course-card">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="course-thumbnail">
                                    <?php the_post_thumbnail('medium', array('class' => 'course-image')); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="course-content">
                                <h3 class="course-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>

                                <?php if ($shortname) : ?>
                                    <p class="course-shortname">
                                        <strong><?php echo esc_html(__('Código:', 'moodle-management')); ?></strong>
                                        <?php echo esc_html($shortname); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                                    <div class="course-categories">
                                        <?php
                                        foreach ($categories as $category) {
                                            echo sprintf(
                                                '<a href="%s" class="category-tag">%s</a>',
                                                esc_url(get_term_link($category, 'categoria-curso')),
                                                esc_html($category->name)
                                            );
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (get_the_excerpt()) : ?>
                                    <div class="course-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                <?php endif; ?>

                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                                    <?php echo esc_html(__('Ver mais', 'moodle-management')); ?>
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                    
                    echo '</div>';

                    // Pagination
                    if ($courses_query->max_num_pages > 1) {
                        echo '<div class="courses-pagination">';
                        echo paginate_links(array(
                            'total' => $courses_query->max_num_pages,
                            'current' => $paged,
                        ));
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-courses-found">';
                    echo '<p>' . esc_html(__('Nenhum curso encontrado.', 'moodle-management')) . '</p>';
                    echo '</div>';
                }

                wp_reset_postdata();
                ?>
            </div>
        </div>

        <style>
            .moodle-courses-container {
                max-width: 1200px;
                margin: 30px auto;
                padding: 0 20px;
            }

            .courses-filters {
                background: #f5f5f5;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
            }

            .filter-row {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }

            .filter-categories,
            .filter-search {
                flex: 1;
                min-width: 250px;
            }

            .filter-categories label,
            .filter-search label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
            }

            .categoria-dropdown,
            .curso-search-input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }

            .categoria-dropdown:focus,
            .curso-search-input:focus {
                outline: none;
                border-color: #0073aa;
                box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
            }

            .courses-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }

            .course-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .course-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                border-color: #0073aa;
            }

            .course-thumbnail {
                width: 100%;
                height: 200px;
                overflow: hidden;
                background: #e9ecef;
            }

            .course-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .course-content {
                padding: 20px;
            }

            .course-title {
                margin: 0 0 10px 0;
                font-size: 18px;
                line-height: 1.4;
            }

            .course-title a {
                color: #0073aa;
                text-decoration: none;
            }

            .course-title a:hover {
                color: #005a87;
                text-decoration: underline;
            }

            .course-shortname {
                margin: 8px 0;
                color: #666;
                font-size: 13px;
            }

            .course-categories {
                margin: 12px 0;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .category-tag {
                display: inline-block;
                background: #0073aa;
                color: #fff;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                text-decoration: none;
                transition: background 0.2s;
            }

            .category-tag:hover {
                background: #005a87;
            }

            .course-excerpt {
                margin: 12px 0;
                color: #555;
                font-size: 14px;
                line-height: 1.6;
                max-height: 60px;
                overflow: hidden;
            }

            .btn {
                display: inline-block;
                padding: 10px 20px;
                border-radius: 4px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.2s;
            }

            .btn-primary {
                background: #0073aa;
                color: #fff;
                margin-top: 10px;
            }

            .btn-primary:hover {
                background: #005a87;
            }

            .no-courses-found {
                text-align: center;
                padding: 60px 20px;
                color: #666;
            }

            .courses-pagination {
                text-align: center;
                margin-top: 30px;
            }

            .courses-pagination a,
            .courses-pagination span {
                display: inline-block;
                padding: 8px 12px;
                margin: 0 4px;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-decoration: none;
                color: #0073aa;
            }

            .courses-pagination span.page-numbers.current {
                background: #0073aa;
                color: #fff;
                border-color: #0073aa;
            }

            @media (max-width: 768px) {
                .filter-row {
                    flex-direction: column;
                }

                .courses-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

    </main>
</div>

<?php get_footer();
