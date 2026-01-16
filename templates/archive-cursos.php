<?php
/**
 * Archive template for Cursos - Exibe cursos da tabela moodle_courses
 * 
 * @package Moodle_Management
 */

wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

get_header();
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-courses.php';
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-settings.php';

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

        <?php if (!empty($moodle_cursos_shortcode_atts['show_title'])) : ?>
            <header class="page-header">
                <h1 class="page-title">
                    <?php 
                    $title = !empty($moodle_cursos_shortcode_atts['title']) 
                        ? $moodle_cursos_shortcode_atts['title'] 
                        : get_the_title();
                    echo esc_html($title ?: __('Cursos Disponíveis', 'moodle-management')); 
                    ?>
                </h1>
            </header>
        <?php endif; ?>

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
                            $is_promotional = $price_info && !empty($price_info->default_status_id) && intval($price_info->default_status_id) === 1;
                            $display_price = null;
                            
                            if ($price_info && !empty($price_info->cost)) {
                                $total_cost = floatval($price_info->cost);
                                $installments = !empty($price_info->installments) ? intval($price_info->installments) : 1;
                                $display_price = $total_cost / $installments;
                            }
                            ?>
                            <?php
                            // Gerar cores baseadas no ID do curso para consistência
                            $pattern_id = $course->moodle_id % 5;
                            $color_scheme = !empty($moodle_cursos_shortcode_atts['color_scheme']) ? $moodle_cursos_shortcode_atts['color_scheme'] : 'auto';
                            $gradient = Moodle_Courses::get_course_gradient($course->moodle_id, $color_scheme);
                            $cardStyle = "--course-gradient: {$gradient};";
                            ?>
                            <div class="course-card" style="<?php echo esc_attr($cardStyle); ?>">
                                <!-- Background com padrão -->
                                <div class="course-card-image">
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

                                <!-- Overlay escuro -->
                                <div class="course-card-overlay"></div>

                                <!-- Conteúdo sobre a imagem -->
                                <div class="course-card-header">
                                    <?php if ($category) : ?>
                                        <span class="course-category-badge">
                                            <?php echo esc_html($category->name); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="course-card-body">
                                    <h3 class="course-title">
                                        <?php echo esc_html($course->name); ?>
                                    </h3>
                                    
                                    <?php if ($display_price !== null && $display_price > 0) : ?>
                                        <div class="course-card-price">
                                            <?php if ($is_promotional) : ?>
                                                <span class="promotion-badge">
                                                    <i class="fas fa-tag"></i> <?php echo esc_html(__('Promoção', 'moodle-management')); ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="course-price">
                                                <?php 
                                                $installments = !empty($price_info->installments) ? intval($price_info->installments) : 1;
                                                $price_message = Moodle_Settings::format_price($display_price, $installments);
                                                echo wp_kses_post($price_message);
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
            /* ====== FLUENT DESIGN SYSTEM ====== */
            /* Base & Reset */
            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                --primary-color: #0078d4;
                --primary-light: #50e6ff;
                --success-color: #107c10;
                --warning-color: #ffb900;
                --danger-color: #da3b01;
                --neutral-dark: #242424;
                --neutral-light: #f3f3f3;
                --neutral-surface: #ffffff;
                --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
                --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);
                --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
                --shadow-xl: 0 16px 40px rgba(0, 0, 0, 0.16);
                --glass-blur: blur(10px);
                --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* ====== LAYOUT CONTAINER ====== */
            .moodle-courses-container {
                max-width: 1200px;
                margin: 40px auto;
                padding: 0 15px;
            }

            /* ====== FILTER SECTION ====== */
            .courses-filters {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: var(--glass-blur);
                border: 1px solid rgba(0, 0, 0, 0.06);
                padding: 24px;
                border-radius: 12px;
                margin-bottom: 32px;
                box-shadow: var(--shadow-md);
                transition: var(--transition);
            }

            .courses-filters:hover {
                background: rgba(255, 255, 255, 0.9);
                box-shadow: var(--shadow-lg);
            }

            .filters-form {
                width: 100%;
            }

            .filter-row {
                display: flex;
                flex-direction: column;
                gap: 16px;
                width: 100%;
            }

            .filter-row-category {
                margin-bottom: 8px;
            }

            .filter-row-secondary {
                margin-top: 8px;
                padding-top: 16px;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
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
                font-weight: 500;
                color: var(--neutral-dark);
                font-size: 14px;
                letter-spacing: 0.3px;
            }

            .categoria-dropdown,
            .subcategoria-dropdown,
            .curso-search-input {
                width: 100%;
                padding: 12px 14px;
                border: 1px solid rgba(0, 0, 0, 0.12);
                border-radius: 8px;
                font-size: 14px;
                background: var(--neutral-surface);
                color: var(--neutral-dark);
                transition: var(--transition);
                font-family: inherit;
            }

            .categoria-dropdown:hover,
            .subcategoria-dropdown:hover,
            .curso-search-input:hover {
                border-color: rgba(0, 0, 0, 0.2);
                box-shadow: var(--shadow-sm);
            }

            .categoria-dropdown:focus,
            .subcategoria-dropdown:focus,
            .curso-search-input:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.1);
            }

            .filter-info {
                padding: 12px 16px;
                background: rgba(0, 120, 212, 0.08);
                border-radius: 8px;
                border-left: 4px solid var(--primary-color);
                font-size: 14px;
                color: var(--neutral-dark);
            }

            .filter-submit {
                display: flex;
                gap: 10px;
                flex-direction: column;
            }

            .filter-submit .button {
                width: 100%;
                padding: 12px 16px;
                font-size: 14px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
                transition: var(--transition);
                letter-spacing: 0.3px;
            }

            .button.button-primary {
                background: var(--primary-color);
                color: #fff;
                box-shadow: var(--shadow-sm);
            }

            .button.button-primary:hover {
                background: #106ebe;
                box-shadow: var(--shadow-md);
                transform: translateY(-2px);
            }

            .button.button-primary:active {
                transform: translateY(0);
            }

            .button {
                background: var(--neutral-light);
                color: var(--neutral-dark);
                text-decoration: none;
                display: inline-block;
                text-align: center;
                box-shadow: var(--shadow-sm);
            }

            .button:hover {
                background: #e8e8e8;
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
            }

            /* ====== COURSES GRID ====== */
            .courses-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 20px;
                margin-bottom: 32px;
            }

            /* ====== COURSE CARD ====== */
            .course-card {
                border-radius: 12px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                position: relative;
                box-shadow: var(--shadow-sm);
                transition: var(--transition);
                height: 100%;
                background: var(--neutral-surface);
            }

            .course-card:hover {
                box-shadow: var(--shadow-lg);
                transform: translateY(-4px);
            }

            /* ====== COURSE CARD IMAGE (BACKGROUND) ====== */
            .course-card-image {
                width: 100%;
                height: 220px;
                background: var(--course-gradient, linear-gradient(135deg, #0078d4 0%, #50e6ff 100%));
                position: relative;
                overflow: hidden;
                flex-shrink: 0;
            }

            .pattern-svg {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .course-card:hover .course-card-image {
                animation: cardImageZoom 0.4s ease;
            }

            @keyframes cardImageZoom {
                0% { transform: scale(1); }
                100% { transform: scale(1.08); }
            }

            /* ====== OVERLAY ESCURO ====== */
            .course-card-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(180deg, 
                    rgba(0, 0, 0, 0.2) 0%, 
                    rgba(0, 0, 0, 0.4) 50%,
                    rgba(0, 0, 0, 0.7) 100%);
                z-index: 1;
                transition: var(--transition);
            }

            .course-card:hover .course-card-overlay {
                background: linear-gradient(180deg, 
                    rgba(0, 0, 0, 0.3) 0%, 
                    rgba(0, 0, 0, 0.5) 50%,
                    rgba(0, 0, 0, 0.8) 100%);
            }

            /* ====== CARD HEADER (CATEGORY BADGE) ====== */
            .course-card-header {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                padding: 16px;
                z-index: 2;
            }

            /* ====== CATEGORY BADGE ====== */
            .course-category-badge {
                background: linear-gradient(135deg, #0078d4 0%, #106ebe 100%);
                color: #fff;
                padding: 8px 16px;
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
                border-radius: 20px;
                display: inline-block;
                position: relative;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0, 120, 212, 0.3);
            }

            .course-category-badge::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                transition: left 0.6s ease;
            }

            .course-card:hover .course-category-badge::before {
                left: 100%;
            }

            /* ====== CARD BODY (TITLE + PRICE) ====== */
            .course-card-body {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 20px;
                z-index: 2;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .course-title {
                margin: 0;
                font-size: 22px;
                line-height: 1.3;
                color: #fff;
                font-weight: 700;
                letter-spacing: -0.3px;
                text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            }

            /* ====== COURSE PRICE (ABAIXO DO TÍTULO) ====== */
            .course-card-price {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .course-price {
                font-size: 16px;
                font-weight: 700;
                color: #fff;
                letter-spacing: 0.3px;
                text-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
                margin: 0;
            }

            /* ====== COURSE HEADER (LEGACY - HIDDEN) ====== */
            .course-header {
                display: none;
            }

            /* ====== COURSE CONTENT (HIDDEN) ====== */
            .course-content {
                display: none;
            }

            .course-code {
                margin: 0;
                color: #666;
                font-size: 13px;
                font-weight: 500;
            }

            .course-code code {
                background: rgba(0, 120, 212, 0.1);
                padding: 4px 8px;
                border-radius: 4px;
                font-family: 'Segoe UI', monospace;
                color: var(--primary-color);
                font-weight: 600;
            }

            /* ====== COURSE FOOTER ====== */
            .course-footer {
                padding: 20px;
                background: var(--neutral-surface);
                display: flex;
                flex-direction: column;
                gap: 12px;
                border-top: 1px solid rgba(0, 0, 0, 0.06);
            }

            .course-price-wrapper {
                display: flex;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
                min-height: 32px;
                justify-content: center;
            }

            /* ====== PROMOTION BADGE ====== */
            .promotion-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: linear-gradient(135deg, #da3b01 0%, #ea4300 100%);
                color: #fff;
                padding: 8px 14px;
                border-radius: 6px;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                box-shadow: 0 4px 12px rgba(218, 59, 1, 0.3);
                animation: pulse 2s ease-in-out infinite;
            }

            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.8; }
            }

            .promotion-badge i {
                font-size: 12px;
            }

            /* ====== COURSE PRICE ====== */
            .course-price {
                font-size: 14px;
                font-weight: 700;
                color: #107c10;
                letter-spacing: 0.5px;
            }

            /* ====== NO COURSES FOUND ====== */
            .no-courses-found {
                text-align: center;
                padding: 60px 20px;
                color: #666;
                background: rgba(0, 0, 0, 0.02);
                border-radius: 12px;
                border: 1px dashed rgba(0, 0, 0, 0.1);
            }

            .no-courses-found p {
                font-size: 16px;
                margin: 0;
            }

            /* ====== PAGINATION ====== */
            .courses-pagination {
                text-align: center;
                margin-top: 32px;
                padding: 20px;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 8px;
            }

            .courses-pagination a,
            .courses-pagination span {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 12px;
                min-width: 40px;
                border: 1px solid rgba(0, 0, 0, 0.12);
                border-radius: 8px;
                text-decoration: none;
                color: var(--primary-color);
                font-size: 13px;
                font-weight: 500;
                transition: var(--transition);
                background: var(--neutral-surface);
            }

            .courses-pagination a:hover {
                background: var(--neutral-light);
                border-color: var(--primary-color);
                box-shadow: var(--shadow-sm);
                transform: translateY(-2px);
            }

            .courses-pagination span.page-numbers.current {
                background: var(--primary-color);
                color: #fff;
                border-color: var(--primary-color);
                font-weight: 600;
                box-shadow: var(--shadow-md);
            }

            /* ====== MOBILE STYLES ====== */
            @media (min-width: 600px) {
                .moodle-courses-container {
                    margin: 40px auto;
                    padding: 0 20px;
                }

                .courses-filters {
                    padding: 28px;
                    margin-bottom: 36px;
                }

                .filter-row {
                    flex-direction: row;
                    align-items: flex-end;
                    gap: 20px;
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
                    gap: 24px;
                }

                .course-card {
                    height: 100%;
                }
            }

            /* ====== TABLET STYLES ====== */
            @media (min-width: 768px) {
                .moodle-courses-container {
                    margin: 50px auto;
                    padding: 0 24px;
                }

                .courses-filters {
                    padding: 32px;
                    margin-bottom: 40px;
                }

                .filter-row {
                    gap: 24px;
                }

                .filter-row-category {
                    margin-bottom: 16px;
                }

                .filter-row-secondary {
                    margin-top: 16px;
                    padding-top: 20px;
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
                    padding: 12px 20px;
                }

                .courses-grid {
                    grid-template-columns: repeat(3, 1fr);
                    gap: 28px;
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

                .course-title {
                    font-size: 17px;
                }
            }

            /* ====== DESKTOP STYLES ====== */
            @media (min-width: 1024px) {
                .courses-grid {
                    grid-template-columns: repeat(3, 1fr);
                    gap: 32px;
                }

                .moodle-courses-container {
                    padding: 0 32px;
                }
            }

            /* ====== ACCESSIBILITY ====== */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }

            @media (prefers-color-scheme: dark) {
                body {
                    --neutral-dark: #ffffff;
                    --neutral-light: #2d2d2d;
                    --neutral-surface: #1e1e1e;
                }

                .courses-filters {
                    background: rgba(30, 30, 30, 0.7);
                    border-color: rgba(255, 255, 255, 0.1);
                }

                .course-card {
                    background: #2d2d2d;
                    border-color: rgba(255, 255, 255, 0.1);
                }

                .course-content {
                    background: rgba(255, 255, 255, 0.05);
                }

                .course-footer {
                    background: rgba(0, 120, 212, 0.08);
                }

                .course-title {
                    color: #ffffff;
                }

                .filter-info {
                    background: rgba(0, 120, 212, 0.15);
                }
            }
        </style>

    </main>
</div>

<?php get_footer();
