<?php
/**
 * Archive template for Cursos - Exibe cursos da tabela moodle_courses
 * 
 * @package Moodle_Management
 */

wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

get_header();
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-courses.php';

global $moodle_cursos_shortcode_atts;

// Parâmetros do shortcode
$shortcode_category_id = null;
$show_subcategories = true;
$page_url = null;

if (!empty($moodle_cursos_shortcode_atts)) {
    $shortcode_category_id = !empty($moodle_cursos_shortcode_atts['category_id']) ? intval($moodle_cursos_shortcode_atts['category_id']) : null;
    $show_subcategories = !empty($moodle_cursos_shortcode_atts['show_subcategories']) ? $moodle_cursos_shortcode_atts['show_subcategories'] : true;
    $page_url = !empty($moodle_cursos_shortcode_atts['page_url']) ? $moodle_cursos_shortcode_atts['page_url'] : null;
}

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
if (isset($_GET['paged'])) {
    $paged = intval($_GET['paged']);
}

$per_page = 12;
$offset = ($paged - 1) * $per_page;

// Determine which courses to display
$all_courses = Moodle_Courses::get_all_courses();
$total_courses = count($all_courses);
$courses = array_slice($all_courses, $offset, $per_page);

// Get selected category for filtering
$selected_category = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$selected_subcategory = isset($_GET['subcategoria']) ? sanitize_text_field($_GET['subcategoria']) : '';
$search_term = isset($_GET['busca']) ? sanitize_text_field($_GET['busca']) : '';

// Se há categoria definida no shortcode, usar ela
if ($shortcode_category_id) {
    // Se tem subcategoria selecionada, usar ela
    if ($selected_subcategory) {
        $courses = Moodle_Courses::get_courses_by_category_tree(intval($selected_subcategory), false);
    } else {
        $courses = Moodle_Courses::get_courses_by_category_tree($shortcode_category_id, $show_subcategories);
    }
    
    // Aplicar busca se existir
    if ($search_term) {
        $courses = array_filter($courses, function($course) use ($search_term) {
            return stripos($course->name, $search_term) !== false || stripos($course->shortname, $search_term) !== false;
        });
    }
    
    $total_courses = count($courses);
    $courses = array_slice($courses, $offset, $per_page);
} elseif ($selected_category) {
    // Filtro manual de categoria (quando não usa shortcode)
    if ($selected_subcategory) {
        $courses = Moodle_Courses::get_courses_by_category_tree(intval($selected_subcategory), false);
    } else {
        $courses = Moodle_Courses::get_courses_by_category_tree(intval($selected_category), $show_subcategories);
    }
    $total_courses = count($courses);
    $courses = array_slice($courses, $offset, $per_page);
} elseif ($search_term) {
    $courses = Moodle_Courses::search_courses($search_term);
    $total_courses = count($courses);
    $courses = array_slice($courses, $offset, $per_page);
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <header class="page-header">
            <h1 class="page-title">
                <?php echo esc_html(get_the_title() ?: __('Cursos Disponíveis', 'moodle-management')); ?>
            </h1>
        </header>

        <div class="moodle-courses-container">
            <!-- Filtros e Busca -->
            <div class="courses-filters">
                <form method="get" action="<?php echo esc_url($page_url ?: ''); ?>" class="filters-form">
                    <!-- Linha 1: Categoria -->
                    <div class="filter-row filter-row-category">
                        <!-- Filtro por Categoria (apenas se não estiver restringido pelo shortcode) -->
                        <?php if (!$shortcode_category_id) : ?>
                            <div class="filter-categories">
                                <label for="categoria-filtro">
                                    <?php echo esc_html(__('Filtrar por Categoria:', 'moodle-management')); ?>
                                </label>
                                <select id="categoria-filtro" name="categoria" class="categoria-dropdown">
                                    <option value="">
                                        <?php echo esc_html(__('Todas as categorias', 'moodle-management')); ?>
                                    </option>
                                    <?php Moodle_Courses::render_category_options(); ?>
                                </select>
                            </div>
                        <?php else : ?>
                            <!-- Se categoria está fixa no shortcode, mostrar apenas título -->
                            <div class="filter-info">
                                <strong><?php echo esc_html(__('Categoria:', 'moodle-management')); ?></strong>
                                <?php
                                $category = Moodle_Courses::get_category($shortcode_category_id);
                                echo esc_html($category ? $category->name : __('Sem categoria', 'moodle-management'));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Linha 2: Especialização, Busca e Botão -->
                    <div class="filter-row filter-row-secondary">
                        <!-- Filtro por Subcategoria (se a categoria tem subcategorias) -->
                        <?php 
                        $category_to_check = $shortcode_category_id ?: ($selected_category ?: null);
                        if ($category_to_check && Moodle_Courses::has_subcategories($category_to_check)) : 
                        ?>
                            <div class="filter-subcategories">
                                <label for="subcategoria-filtro">
                                    <?php echo esc_html(__('Especialização:', 'moodle-management')); ?>
                                </label>
                                <select id="subcategoria-filtro" name="subcategoria" class="subcategoria-dropdown">
                                    <option value="">
                                        <?php echo esc_html(__('Todas as especializações', 'moodle-management')); ?>
                                    </option>
                                    <?php 
                                    $subcats = Moodle_Courses::get_subcategories($category_to_check);
                                    foreach ($subcats as $subcat) {
                                        echo sprintf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($subcat->moodle_id),
                                            selected($selected_subcategory, $subcat->moodle_id, false),
                                            esc_html($subcat->name)
                                        );
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Busca por Nome -->
                        <div class="filter-search">
                            <label for="curso-search">
                                <?php echo esc_html(__('Buscar curso:', 'moodle-management')); ?>
                            </label>
                            <input 
                                type="text" 
                                id="curso-search" 
                                name="busca"
                                class="curso-search-input" 
                                placeholder="<?php echo esc_attr(__('Nome ou código do curso...', 'moodle-management')); ?>"
                                value="<?php echo esc_attr($search_term); ?>"
                            />
                        </div>

                        <!-- Botão Filtrar -->
                        <div class="filter-submit">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html(__('Filtrar', 'moodle-management')); ?>
                            </button>
                            <?php if (($selected_category && !$shortcode_category_id) || $selected_subcategory || $search_term) : ?>
                                <a href="<?php echo esc_url($page_url ?: get_post_type_archive_link('curso')); ?>" class="button">
                                    <?php echo esc_html(__('Limpar Filtros', 'moodle-management')); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Lista de Cursos -->
            <div class="courses-list">
                <?php if (!empty($courses)) : ?>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course) : ?>
                            <?php
                            $category = Moodle_Courses::get_category($course->category_id);
                            $price_info = Moodle_Courses::get_course_best_price($course->moodle_id);
                            $is_promotional = $price_info && !empty($price_info->cost_promotional) && floatval($price_info->cost_promotional) > 0;
                            $display_price = null;
                            
                            if ($price_info) {
                                if ($is_promotional) {
                                    $display_price = floatval($price_info->cost_promotional);
                                } else {
                                    $display_price = floatval($price_info->cost);
                                }
                            }
                            ?>
                            <div class="course-card">
                                <?php
                                // Gerar padrão aleatório baseado no ID do curso
                                $pattern_id = $course->moodle_id % 5;
                                $color1 = sprintf('#%06X', mt_rand(0, 0xFFFFFF) & 0xFFFFFF);
                                $color2 = sprintf('#%06X', mt_rand(0, 0xFFFFFF) & 0xFFFFFF);
                                
                                // Usar semente baseada no ID para cores consistentes
                                srand(crc32($course->moodle_id));
                                $hue1 = mt_rand(0, 360);
                                $hue2 = ($hue1 + mt_rand(30, 120)) % 360;
                                $color1 = sprintf('hsl(%d, 70%%, 60%%)', $hue1);
                                $color2 = sprintf('hsl(%d, 70%%, 40%%)', $hue2);
                                ?>
                                <div class="course-image" style="background: linear-gradient(135deg, <?php echo esc_attr($color1); ?> 0%, <?php echo esc_attr($color2); ?> 100%);">
                                    <svg viewBox="0 0 400 250" xmlns="http://www.w3.org/2000/svg" class="pattern-svg">
                                        <?php if ($pattern_id === 0): // Círculos ?>
                                            <circle cx="100" cy="60" r="40" fill="rgba(255,255,255,0.1)"/>
                                            <circle cx="300" cy="150" r="50" fill="rgba(255,255,255,0.15)"/>
                                            <circle cx="200" cy="200" r="35" fill="rgba(255,255,255,0.1)"/>
                                        <?php elseif ($pattern_id === 1): // Linhas diagonais ?>
                                            <line x1="0" y1="0" x2="400" y2="250" stroke="rgba(255,255,255,0.2)" stroke-width="20"/>
                                            <line x1="-100" y1="0" x2="300" y2="250" stroke="rgba(255,255,255,0.15)" stroke-width="20"/>
                                            <line x1="100" y1="0" x2="500" y2="250" stroke="rgba(255,255,255,0.1)" stroke-width="20"/>
                                        <?php elseif ($pattern_id === 2): // Quadrados ?>
                                            <rect x="50" y="30" width="80" height="80" fill="rgba(255,255,255,0.15)"/>
                                            <rect x="260" y="100" width="100" height="100" fill="rgba(255,255,255,0.1)"/>
                                            <rect x="150" y="170" width="60" height="60" fill="rgba(255,255,255,0.12)"/>
                                        <?php elseif ($pattern_id === 3): // Ondas ?>
                                            <path d="M 0 100 Q 50 50, 100 100 T 200 100 T 300 100 T 400 100" stroke="rgba(255,255,255,0.2)" fill="none" stroke-width="3"/>
                                            <path d="M 0 150 Q 50 100, 100 150 T 200 150 T 300 150 T 400 150" stroke="rgba(255,255,255,0.15)" fill="none" stroke-width="3"/>
                                        <?php else: // Triângulos ?>
                                            <polygon points="50,30 100,120 0,120" fill="rgba(255,255,255,0.15)"/>
                                            <polygon points="300,40 380,160 220,160" fill="rgba(255,255,255,0.1)"/>
                                            <polygon points="200,180 250,240 150,240" fill="rgba(255,255,255,0.12)"/>
                                        <?php endif; ?>
                                    </svg>
                                </div>
                                
                                <?php if ($category) : ?>
                                    <div class="course-category-badge">
                                        <?php echo esc_html($category->name); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="course-header">
                                    <h3 class="course-title">
                                        <?php echo esc_html($course->name); ?>
                                    </h3>
                                </div>
                                
                                <div class="course-content">
                                    <?php if ($course->moodle_id) : ?>
                                        <p class="course-code">
                                            <strong><?php echo esc_html(__('Código:', 'moodle-management')); ?></strong>
                                            <code><?php echo esc_html($course->moodle_id); ?></code>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="course-footer">
                                    <?php if ($display_price !== null) : ?>
                                        <div class="course-price-wrapper">
                                            <?php if ($is_promotional) : ?>
                                                <span class="promotion-badge">
                                                    <i class="fas fa-tag"></i> <?php echo esc_html(__('Promoção', 'moodle-management')); ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="course-price">
                                                <?php 
                                                $currency = !empty($price_info->currency) ? $price_info->currency : 'R$';
                                                echo sprintf(
                                                    '<span class="price-value">%s %.2f</span>',
                                                    esc_html($currency),
                                                    $display_price
                                                );
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php
                    $max_pages = ceil($total_courses / $per_page);
                    if ($max_pages > 1) :
                    ?>
                        <div class="courses-pagination">
                            <?php
                            // Usar $page_url do shortcode se disponível, senão usar o arquivo de cursos
                            $base_url = $page_url ?: get_post_type_archive_link('curso');
                            
                            if ($selected_category && !$shortcode_category_id) {
                                $base_url = add_query_arg('categoria', $selected_category, $base_url);
                            }
                            if ($selected_subcategory) {
                                $base_url = add_query_arg('subcategoria', $selected_subcategory, $base_url);
                            }
                            if ($search_term) {
                                $base_url = add_query_arg('busca', $search_term, $base_url);
                            }

                            for ($i = 1; $i <= $max_pages; $i++) {
                                $pagination_url = add_query_arg('paged', $i, $base_url);
                                $class = $i === $paged ? 'current' : '';
                                
                                if ($i === $paged) {
                                    echo sprintf('<span class="page-numbers %s">%d</span>', $class, $i);
                                } else {
                                    echo sprintf(
                                        '<a class="page-numbers" href="%s">%d</a>',
                                        esc_url($pagination_url),
                                        $i
                                    );
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="no-courses-found">
                        <p><?php echo esc_html(__('Nenhum curso encontrado.', 'moodle-management')); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            /* Mobile First - Base mobile styles */
            * {
                box-sizing: border-box;
            }

            .moodle-courses-container {
                max-width: 1200px;
                margin: 20px auto;
                padding: 0 15px;
            }

            .courses-filters {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .filters-form {
                width: 100%;
            }

            .filter-row {
                display: flex;
                flex-direction: column;
                gap: 15px;
                width: 100%;
            }

            .filter-row-category {
                margin-bottom: 10px;
            }

            .filter-row-secondary {
                margin-top: 10px;
                padding-top: 15px;
                border-top: 1px solid #e0e0e0;
            }

            .filter-categories,
            .filter-search,
            .filter-submit,
            .filter-subcategories,
            .filter-info {
                width: 100%;
            }

            .filter-categories label,
            .filter-search label,
            .filter-subcategories label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }

            .categoria-dropdown,
            .subcategoria-dropdown,
            .curso-search-input {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
            }

            .categoria-dropdown:focus,
            .subcategoria-dropdown:focus,
            .curso-search-input:focus {
                outline: none;
                border-color: #0073aa;
                box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
            }

            .filter-info {
                padding: 12px;
                background: #e8f4f8;
                border-radius: 4px;
                border-left: 4px solid #0073aa;
                font-size: 14px;
            }

            .filter-submit {
                display: flex;
                gap: 10px;
                flex-direction: column;
            }

            .filter-submit .button {
                width: 100%;
                padding: 12px;
                font-size: 14px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
            }

            .button.button-primary {
                background: #0073aa;
                color: #fff;
            }

            .button.button-primary:hover {
                background: #005a87;
            }

            .button {
                background: #f0f0f0;
                color: #333;
                text-decoration: none;
                display: inline-block;
                text-align: center;
            }

            .button:hover {
                background: #e0e0e0;
            }

            .courses-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 15px;
                margin-bottom: 20px;
            }

            .course-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                display: flex;
                flex-direction: column;
                position: relative;
            }

            .course-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                border-color: #0073aa;
            }

            .course-image {
                width: 100%;
                height: 200px;
                overflow: hidden;
                background: #f0f0f0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .pattern-svg {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .course-card:hover .course-image {
                animation: patternShift 0.3s ease;
            }

            @keyframes patternShift {
                0% { transform: scale(1); }
                100% { transform: scale(1.05); }
            }

            .course-category-badge {
                background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
                color: #fff;
                padding: 8px 12px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .course-header {
                padding: 15px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 10px;
                flex-wrap: wrap;
            }

            .course-title {
                margin: 0;
                font-size: 16px;
                line-height: 1.4;
                flex: 1;
                min-width: 200px;
                color: #333;
            }

            .course-content {
                padding: 15px;
                flex: 1;
            }

            .course-code {
                margin: 0;
                color: #666;
                font-size: 13px;
            }

            .course-code code {
                background: #f0f0f0;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
                color: #333;
            }

            .course-footer {
                padding: 15px;
                background: #fafafa;
                border-top: 1px solid #eee;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .course-price-wrapper {
                display: flex;
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }

            .promotion-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #ff6b6b;
                color: #fff;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .promotion-badge i {
                font-size: 12px;
            }

            .course-price {
                display: flex;
                align-items: baseline;
                gap: 5px;
            }

            .price-value {
                font-size: 18px;
                font-weight: 700;
                color: #0073aa;
            }

            .no-courses-found {
                text-align: center;
                padding: 40px 15px;
                color: #666;
                background: #f5f5f5;
                border-radius: 8px;
            }

            .courses-pagination {
                text-align: center;
                margin-top: 20px;
                padding: 15px;
            }

            .courses-pagination a,
            .courses-pagination span {
                display: inline-block;
                padding: 8px 12px;
                margin: 0 4px 4px 0;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-decoration: none;
                color: #0073aa;
                font-size: 13px;
            }

            .courses-pagination span.page-numbers.current {
                background: #0073aa;
                color: #fff;
                border-color: #0073aa;
            }

            /* Tablet - 600px and up */
            @media (min-width: 600px) {
                .moodle-courses-container {
                    margin: 25px auto;
                    padding: 0 20px;
                }

                .courses-filters {
                    padding: 20px;
                    margin-bottom: 25px;
                }

                .filter-row {
                    flex-direction: row;
                    align-items: flex-end;
                }

                .filter-categories,
                .filter-search,
                .filter-submit,
                .filter-subcategories {
                    flex: 1;
                    min-width: 180px;
                }

                .filter-submit {
                    flex-direction: row;
                }

                .filter-submit .button {
                    flex: 1;
                }

                .courses-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                }

                .course-card {
                    height: 100%;
                }
            }

            /* Desktop - 768px and up */
            @media (min-width: 768px) {
                .moodle-courses-container {
                    margin: 30px auto;
                    padding: 0 20px;
                }

                .courses-filters {
                    padding: 20px;
                    margin-bottom: 30px;
                }

                .filter-row {
                    gap: 20px;
                }

                .filter-row-category {
                    margin-bottom: 15px;
                }

                .filter-row-secondary {
                    margin-top: 15px;
                    padding-top: 15px;
                }

                .filter-categories,
                .filter-search,
                .filter-submit,
                .filter-subcategories {
                    flex: 1;
                    min-width: 220px;
                }

                .filter-submit {
                    display: flex;
                    flex-direction: row;
                }

                .filter-submit .button {
                    flex: 1;
                    padding: 10px 20px;
                }

                .courses-grid {
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                }

                .course-card {
                    height: 100%;
                }

                .course-header {
                    padding: 20px;
                }

                .course-content {
                    padding: 20px;
                }

                .course-footer {
                    padding: 20px;
                }
            }

            /* Large Desktop - 1024px and up */
            @media (min-width: 1024px) {
                .courses-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }
        </style>

    </main>
</div>

<?php get_footer();
