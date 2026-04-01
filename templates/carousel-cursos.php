<?php
/**
 * Carousel template for Cursos
 * Para uso via shortcode [moodle_carrossel]
 *
 * @package Moodle_Management
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-courses.php';
require_once MOODLE_MANAGEMENT_PATH . 'includes/class-moodle-settings.php';

wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );

global $moodle_carrossel_shortcode_atts;

// ─── Parse atts ───────────────────────────────────────────────────────────────
$atts                  = is_array( $moodle_carrossel_shortcode_atts ) ? $moodle_carrossel_shortcode_atts : array();
$shortcode_category_id = ! empty( $atts['category_id'] )       ? intval( $atts['category_id'] )                                     : null;
$show_subcategories    = isset( $atts['show_subcategories'] )   ? filter_var( $atts['show_subcategories'], FILTER_VALIDATE_BOOLEAN ) : true;
$show_title            = isset( $atts['show_title'] )           ? filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN )         : false;
$carousel_title        = ! empty( $atts['title'] )              ? sanitize_text_field( $atts['title'] )                             : __( 'Cursos Disponíveis', 'moodle-management' );
$color_scheme          = ! empty( $atts['color_scheme'] )       ? sanitize_text_field( $atts['color_scheme'] )                      : 'auto';
$limit                 = ! empty( $atts['limit'] )              ? max( 0, intval( $atts['limit'] ) )                                : 0;
$autoplay              = isset( $atts['autoplay'] )             ? filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN )           : false;
$autoplay_speed        = ! empty( $atts['autoplay_speed'] )     ? max( 1000, intval( $atts['autoplay_speed'] ) )                    : 4000;
$instance_id           = ! empty( $atts['instance_id'] )        ? sanitize_html_class( $atts['instance_id'] )                       : 'mcw0';

// ─── Load courses ──────────────────────────────────────────────────────────────
if ( $shortcode_category_id ) {
    $courses = Moodle_Courses::get_courses_by_category_tree( $shortcode_category_id, $show_subcategories );
} else {
    $courses = Moodle_Courses::get_all_courses();
}

if ( $limit > 0 && ! empty( $courses ) ) {
    $courses = array_slice( $courses, 0, $limit );
}

// ─── Settings ─────────────────────────────────────────────────────────────────
global $wpdb;
$settings_table             = $wpdb->prefix . 'moodle_settings';
$moodle_base_url            = rtrim( (string) $wpdb->get_var( "SELECT setting_value FROM {$settings_table} WHERE setting_key = 'base_url'" ), '/' );
$enroll_button_title        = Moodle_Settings::get_enroll_button_title();
$enroll_button_url_template = Moodle_Settings::get_enroll_button_url();
$enroll_button_color        = Moodle_Settings::get_enroll_button_color();

$wrap_id   = 'mcw-wrap-'   . $instance_id;
$drawer_id = 'mcw-drawer-' . $instance_id;
$track_id  = 'mcw-track-'  . $instance_id;
$vp_id     = 'mcw-vp-'     . $instance_id;
$all_courses_url = get_post_type_archive_link( 'curso' );

if ( ! $all_courses_url ) {
    $all_courses_url = home_url( '/curso/' );
}
?>
<div class="moodle-carousel-wrap" id="<?php echo esc_attr( $wrap_id ); ?>">

    <div class="mcw-title-row">
        <?php if ( $show_title ) : ?>
        <h2 class="mcw-title"><?php echo esc_html( $carousel_title ); ?></h2>
        <?php else : ?>
        <span class="mcw-title-spacer" aria-hidden="true"></span>
        <?php endif; ?>

        <a class="mcw-view-all-btn"
           href="<?php echo esc_url( $all_courses_url ); ?>"
           aria-label="<?php echo esc_attr( __( 'Ver todos os cursos', 'moodle-management' ) ); ?>">
            <?php echo esc_html( __( 'Ver todos', 'moodle-management' ) ); ?>
        </a>
    </div>

    <div class="mcw-carousel-outer">
        <button class="mcw-arrow mcw-prev" type="button" aria-label="<?php echo esc_attr( __( 'Anterior', 'moodle-management' ) ); ?>">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>

        <div class="mcw-viewport" id="<?php echo esc_attr( $vp_id ); ?>">
            <div class="mcw-track" id="<?php echo esc_attr( $track_id ); ?>">
                <?php if ( ! empty( $courses ) ) : ?>
                    <?php foreach ( $courses as $course ) : ?>
                        <?php
                        $category    = Moodle_Courses::get_category( $course->category_id );
                        $price_info  = Moodle_Courses::get_course_best_price( $course->moodle_id );
                        $is_promo    = $price_info && ! empty( $price_info->default_status_id ) && intval( $price_info->default_status_id ) === 1;
                        $disp_price  = null;
                        $installments = 1;
                        $price_msg   = '';

                        if ( $price_info && ! empty( $price_info->cost ) ) {
                            $total_cost   = floatval( $price_info->cost );
                            $installments = ! empty( $price_info->installments ) ? intval( $price_info->installments ) : 1;
                            $disp_price   = $total_cost / $installments;
                            $price_msg    = Moodle_Settings::format_price( $disp_price, $installments );
                        }

                        $pattern_id = $course->moodle_id % 5;
                        $gradient   = Moodle_Courses::get_course_gradient( $course->moodle_id, $color_scheme );
                        $card_style = '--mcw-gradient:' . $gradient . ';';
                        ?>
                        <div class="mcw-slide"
                             data-category-id="<?php echo esc_attr( $course->category_id ); ?>">
                            <div class="mcw-card"
                                 style="<?php echo esc_attr( $card_style ); ?>"
                                 tabindex="0"
                                 role="button"
                                 aria-label="<?php echo esc_attr( sprintf( __( 'Ver detalhes do curso %s', 'moodle-management' ), $course->name ) ); ?>"
                                 data-course-id="<?php echo esc_attr( $course->moodle_id ); ?>"
                                 data-course-name="<?php echo esc_attr( $course->name ); ?>"
                                 data-course-description="<?php echo esc_attr( wp_kses_post( $course->description ) ); ?>"
                                 data-course-category="<?php echo $category ? esc_attr( $category->name ) : ''; ?>"
                                 data-course-price="<?php echo $disp_price > 0 ? esc_attr( wp_strip_all_tags( $price_msg ) ) : ''; ?>">

                                <!-- Background pattern -->
                                <div class="mcw-card-image" aria-hidden="true">
                                    <svg viewBox="0 0 400 220" xmlns="http://www.w3.org/2000/svg" class="mcw-pattern-svg">
                                        <?php if ( $pattern_id === 0 ) : ?>
                                            <circle cx="80"  cy="55"  r="45" fill="rgba(255,255,255,.1)"/>
                                            <circle cx="310" cy="145" r="55" fill="rgba(255,255,255,.15)"/>
                                            <circle cx="200" cy="190" r="30" fill="rgba(255,255,255,.1)"/>
                                        <?php elseif ( $pattern_id === 1 ) : ?>
                                            <line x1="0"   y1="0"   x2="400" y2="220" stroke="rgba(255,255,255,.2)"  stroke-width="22"/>
                                            <line x1="-80" y1="0"   x2="320" y2="220" stroke="rgba(255,255,255,.15)" stroke-width="22"/>
                                            <line x1="80"  y1="0"   x2="480" y2="220" stroke="rgba(255,255,255,.1)"  stroke-width="22"/>
                                        <?php elseif ( $pattern_id === 2 ) : ?>
                                            <rect x="40"  y="20"  width="80"  height="80"  fill="rgba(255,255,255,.15)"/>
                                            <rect x="260" y="95"  width="100" height="100" fill="rgba(255,255,255,.1)"/>
                                            <rect x="155" y="155" width="60"  height="60"  fill="rgba(255,255,255,.12)"/>
                                        <?php elseif ( $pattern_id === 3 ) : ?>
                                            <path d="M 0 90 Q 50 40, 100 90 T 200 90 T 300 90 T 400 90"    stroke="rgba(255,255,255,.2)"  fill="none" stroke-width="3"/>
                                            <path d="M 0 140 Q 50 90, 100 140 T 200 140 T 300 140 T 400 140" stroke="rgba(255,255,255,.15)" fill="none" stroke-width="3"/>
                                        <?php else : ?>
                                            <polygon points="50,20 100,112 0,112"     fill="rgba(255,255,255,.15)"/>
                                            <polygon points="300,30 380,152 220,152"  fill="rgba(255,255,255,.1)"/>
                                            <polygon points="190,165 245,220 135,220" fill="rgba(255,255,255,.12)"/>
                                        <?php endif; ?>
                                    </svg>
                                </div>

                                <div class="mcw-card-overlay" aria-hidden="true"></div>

                                <div class="mcw-card-header">
                                    <?php if ( $category ) : ?>
                                    <span class="mcw-category-badge"><?php echo esc_html( $category->name ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( $is_promo ) : ?>
                                    <span class="mcw-promo-badge">
                                        <i class="fas fa-tag" aria-hidden="true"></i>
                                        <?php echo esc_html( __( 'Promoção', 'moodle-management' ) ); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mcw-card-body">
                                    <h3 class="mcw-card-title"><?php echo esc_html( $course->name ); ?></h3>
                                    <?php if ( $disp_price !== null && $disp_price > 0 ) : ?>
                                    <div class="mcw-card-price"><?php echo wp_kses_post( $price_msg ); ?></div>
                                    <?php endif; ?>
                                </div>

                                <button class="mcw-info-btn" type="button"
                                        aria-label="<?php echo esc_attr( __( 'Ver detalhes do curso', 'moodle-management' ) ); ?>"
                                        title="<?php echo esc_attr( __( 'Ver detalhes do curso', 'moodle-management' ) ); ?>">
                                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                                </button>
                            </div><!-- .mcw-card -->
                        </div><!-- .mcw-slide -->
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="mcw-empty">
                        <i class="fas fa-graduation-cap mcw-empty-icon" aria-hidden="true"></i>
                        <p><?php echo esc_html( __( 'Nenhum curso disponível no momento.', 'moodle-management' ) ); ?></p>
                    </div>
                <?php endif; ?>
            </div><!-- .mcw-track -->
        </div><!-- .mcw-viewport -->

        <button class="mcw-arrow mcw-next" type="button" aria-label="<?php echo esc_attr( __( 'Próximo', 'moodle-management' ) ); ?>">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div><!-- .mcw-carousel-outer -->

    <div class="mcw-dots" role="tablist" aria-label="<?php echo esc_attr( __( 'Páginas do carrossel', 'moodle-management' ) ); ?>"></div>

</div><!-- .moodle-carousel-wrap -->

<!-- ── Drawer ───────────────────────────────────────────────────────────────── -->
<div id="<?php echo esc_attr( $drawer_id ); ?>"
     class="mcw-drawer"
     role="dialog"
     aria-labelledby="mcw-drawer-title-<?php echo esc_attr( $instance_id ); ?>"
     aria-hidden="true">
    <div class="mcw-drawer-overlay"></div>
    <div class="mcw-drawer-content">
        <div class="mcw-drawer-header">
            <h2 class="mcw-drawer-name" id="mcw-drawer-title-<?php echo esc_attr( $instance_id ); ?>"></h2>
            <button class="mcw-drawer-close" type="button"
                    aria-label="<?php echo esc_attr( __( 'Fechar detalhes do curso', 'moodle-management' ) ); ?>">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="mcw-drawer-body">
            <div class="mcw-drawer-category"></div>
            <div class="mcw-drawer-description"></div>
            <div class="mcw-drawer-price"></div>
        </div>
        <div class="mcw-drawer-footer">
            <a class="mcw-enroll-btn"
               id="mcw-enroll-<?php echo esc_attr( $instance_id ); ?>"
               href="#"
               target="_blank"
               rel="noopener noreferrer"
               style="--mcw-btn-color:<?php echo esc_attr( $enroll_button_color ); ?>;">
                <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                <?php echo esc_html( $enroll_button_title ); ?>
            </a>
        </div>
    </div>
</div>

<style>
/* ═══════════════════════════════════════════════════════
   Moodle Carousel Widget  ·  scoped to #wrap_id
   ═══════════════════════════════════════════════════════ */
#<?php echo esc_attr( $wrap_id ); ?> {
    --mcw-gap: 20px;
    --mcw-radius: 12px;
    --mcw-primary: #0078d4;
    --mcw-primary-dk: #106ebe;
    --mcw-slide-width: 320px;
    --mcw-shadow-sm: 0 1px 4px rgba(0,0,0,.08);
    --mcw-shadow-md: 0 4px 14px rgba(0,0,0,.13);
    --mcw-shadow-lg: 0 8px 28px rgba(0,0,0,.18);
    --mcw-ease: cubic-bezier(0.4, 0, 0.2, 1);
    margin: 40px 0;
    font-family: inherit;
    box-sizing: border-box;
}

/* ─ Title ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-title-row {
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
    color: inherit;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-title-spacer {
    display: inline-block;
    min-height: 1px;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-view-all-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid rgba(0,120,212,.3);
    color: var(--mcw-primary);
    background: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    line-height: 1;
    transition: all .25s var(--mcw-ease);
    box-shadow: var(--mcw-shadow-sm);
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-view-all-btn:hover,
#<?php echo esc_attr( $wrap_id ); ?> .mcw-view-all-btn:focus-visible {
    background: var(--mcw-primary);
    color: #fff;
    box-shadow: var(--mcw-shadow-md);
}

/* ─ Carousel outer ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-carousel-outer {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* ─ Arrows ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-arrow {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1.5px solid rgba(0,120,212,.3);
    background: #fff;
    color: var(--mcw-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: all .25s var(--mcw-ease);
    box-shadow: var(--mcw-shadow-sm);
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-arrow:hover {
    background: var(--mcw-primary);
    color: #fff;
    box-shadow: var(--mcw-shadow-md);
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-arrow:disabled,
#<?php echo esc_attr( $wrap_id ); ?> .mcw-arrow[disabled] {
    opacity: .35;
    cursor: not-allowed;
    pointer-events: none;
}

/* ─ Viewport ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-viewport {
    flex: 1;
    overflow: hidden;
    position: relative;
}

/* ─ Track ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-track {
    display: flex;
    gap: var(--mcw-gap);
    transition: transform 0.4s var(--mcw-ease);
    will-change: transform;
}

/* ─ Slide ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-slide {
    flex: 0 0 var(--mcw-slide-width);
    min-width: 0;
}

/* ─ Card ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card {
    height: 280px;
    border-radius: var(--mcw-radius);
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    box-shadow: var(--mcw-shadow-sm);
    transition: transform .3s var(--mcw-ease), box-shadow .3s var(--mcw-ease);
    cursor: pointer;
    user-select: none;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--mcw-shadow-lg);
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card:focus-visible {
    outline: 3px solid rgba(0,120,212,.5);
    outline-offset: 3px;
}

/* ─ Card image / background ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card-image {
    position: absolute;
    inset: 0;
    background: var(--mcw-gradient, linear-gradient(135deg,#0078d4,#106ebe));
    overflow: hidden;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-pattern-svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* ─ Overlay ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg,
        rgba(0,0,0,.18) 0%,
        rgba(0,0,0,.44) 50%,
        rgba(0,0,0,.74) 100%);
    z-index: 1;
    transition: background .3s var(--mcw-ease);
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card:hover .mcw-card-overlay {
    background: linear-gradient(180deg,
        rgba(0,0,0,.28) 0%,
        rgba(0,0,0,.54) 50%,
        rgba(0,0,0,.82) 100%);
}

/* ─ Card header (badges) ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card-header {
    position: absolute;
    top: 14px;
    left: 14px;
    right: 14px;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-category-badge {
    background: linear-gradient(135deg,#0078d4,#106ebe);
    color: #fff;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .9px;
    border-radius: 16px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 65%;
    box-shadow: 0 3px 10px rgba(0,120,212,.35);
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-promo-badge {
    background: linear-gradient(135deg,#dc3545,#c82333);
    color: #fff;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    border-radius: 8px;
    white-space: nowrap;
    box-shadow: 0 3px 10px rgba(220,53,69,.45);
    animation: mcwPulse 2s ease-in-out infinite;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
@keyframes mcwPulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.05); }
}

/* ─ Card body (title + price) ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card-body {
    position: absolute;
    bottom: 50px;
    left: 14px;
    right: 14px;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card-title {
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.35;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-card-price {
    display: inline-block;
    background: rgba(255,255,255,.18);
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    backdrop-filter: blur(6px);
    align-self: flex-start;
}

/* ─ Info icon button ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-info-btn {
    position: absolute;
    bottom: 12px;
    right: 12px;
    z-index: 3;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,.7);
    background: rgba(255,255,255,.92);
    color: var(--mcw-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: all .25s var(--mcw-ease);
    backdrop-filter: blur(6px);
    box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-info-btn:hover {
    background: var(--mcw-primary);
    color: #fff;
    border-color: var(--mcw-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,120,212,.45);
}

/* ─ Dots ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 22px;
    min-height: 16px;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-dot {
    width: 8px;
    height: 8px;
    border-radius: 4px;
    background: rgba(0,120,212,.22);
    border: none;
    cursor: pointer;
    padding: 0;
    transition: all .3s var(--mcw-ease);
    flex-shrink: 0;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-dot.active {
    width: 24px;
    background: var(--mcw-primary);
}

/* ─ Empty ─ */
#<?php echo esc_attr( $wrap_id ); ?> .mcw-empty {
    padding: 50px 20px;
    text-align: center;
    color: #888;
}
#<?php echo esc_attr( $wrap_id ); ?> .mcw-empty-icon {
    font-size: 36px;
    margin-bottom: 12px;
    display: block;
    opacity: .45;
}

/* ─ Responsive ─ */
@media (max-width: 900px) {
    #<?php echo esc_attr( $wrap_id ); ?> { --mcw-gap: 16px; }
}
@media (max-width: 580px) {
    #<?php echo esc_attr( $wrap_id ); ?> .mcw-arrow { display: none; }
}

/* ═══════════════════════════════════════════════════════
   Drawer
   ═══════════════════════════════════════════════════════ */
#<?php echo esc_attr( $drawer_id ); ?> {
    position: fixed;
    inset: 0;
    z-index: 99999;
    pointer-events: none;
    opacity: 0;
    transition: opacity .3s ease;
}
#<?php echo esc_attr( $drawer_id ); ?>.active {
    pointer-events: all;
    opacity: 1;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.5);
    backdrop-filter: blur(3px);
    cursor: pointer;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-content {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 90%;
    max-width: 500px;
    background: #fff;
    box-shadow: -4px 0 24px rgba(0,0,0,.2);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform .3s var(--mcw-ease, cubic-bezier(0.4,0,0.2,1));
}
#<?php echo esc_attr( $drawer_id ); ?>.active .mcw-drawer-content {
    transform: translateX(0);
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-header {
    padding: 24px;
    background: linear-gradient(135deg, #0078d4, #106ebe);
    color: #fff;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-name {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
    line-height: 1.3;
    flex: 1;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-close {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all .3s;
    flex-shrink: 0;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-close:hover {
    background: rgba(255,255,255,.35);
    transform: rotate(90deg);
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-category {
    display: inline-block;
    background: linear-gradient(135deg,#0078d4,#106ebe);
    color: #fff;
    padding: 5px 14px;
    border-radius: 14px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    margin-bottom: 16px;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-category:empty { display: none; }
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-description {
    color: #333;
    line-height: 1.7;
    font-size: 15px;
    margin-bottom: 20px;
    white-space: pre-wrap;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-description:empty::before {
    content: 'Nenhuma descrição disponível.';
    color: #999;
    font-style: italic;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-price {
    display: inline-block;
    background: rgba(0,120,212,.1);
    color: #0078d4;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    border: 1px solid rgba(0,120,212,.2);
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-price:empty { display: none; }
#<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-footer {
    border-top: 1px solid rgba(0,0,0,.1);
    background: #f3f3f3;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-enroll-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 18px 24px;
    background: var(--mcw-btn-color, #107c10);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: .5px;
    transition: filter .3s;
}
#<?php echo esc_attr( $drawer_id ); ?> .mcw-enroll-btn:hover {
    filter: brightness(1.1);
    box-shadow: 0 4px 16px rgba(0,0,0,.3);
}
@media (min-width: 768px) {
    #<?php echo esc_attr( $drawer_id ); ?> .mcw-drawer-content { width: 500px; }
}
</style>

<script>
(function () {
    'use strict';

    var wrapId       = <?php echo wp_json_encode( $wrap_id ); ?>;
    var drawerId     = <?php echo wp_json_encode( $drawer_id ); ?>;
    var autoplay     = <?php echo wp_json_encode( $autoplay ); ?>;
    var autoplayMs   = <?php echo wp_json_encode( $autoplay_speed ); ?>;
    var enrollConfig = <?php echo wp_json_encode( array(
        'title'       => $enroll_button_title,
        'color'       => $enroll_button_color,
        'urlTemplate' => $enroll_button_url_template,
        'baseUrl'     => $moodle_base_url,
    ), JSON_UNESCAPED_SLASHES ); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        var wrap   = document.getElementById(wrapId);
        var drawer = document.getElementById(drawerId);
        if (!wrap || !drawer) return;

        var viewport    = wrap.querySelector('.mcw-viewport');
        var track       = wrap.querySelector('.mcw-track');
        var dotsWrap    = wrap.querySelector('.mcw-dots');
        var prevBtn     = wrap.querySelector('.mcw-prev');
        var nextBtn     = wrap.querySelector('.mcw-next');
        var allSlides   = Array.prototype.slice.call(track.querySelectorAll('.mcw-slide'));

        // Drawer elements
        var drawerOverlay  = drawer.querySelector('.mcw-drawer-overlay');
        var drawerClose    = drawer.querySelector('.mcw-drawer-close');
        var drawerName     = drawer.querySelector('.mcw-drawer-name');
        var drawerCategory = drawer.querySelector('.mcw-drawer-category');
        var drawerDesc     = drawer.querySelector('.mcw-drawer-description');
        var drawerPrice    = drawer.querySelector('.mcw-drawer-price');
        var drawerEnroll   = drawer.querySelector('.mcw-enroll-btn');

        // ── State ────────────────────────────────────────────────────────────
        var currentPage = 0;
        var autoTimer   = null;

        // ── Helpers ──────────────────────────────────────────────────────────
        function visibleSlides() {
            return allSlides.filter(function (s) {
                return !s.classList.contains('is-filtered');
            });
        }

        function getSPV() {
            var w = viewport.offsetWidth;
            if (w < 580) return 1;
            if (w < 900) return 2;
            return 3;
        }

        function getGap() {
            var g = parseFloat(getComputedStyle(track).gap);
            return isNaN(g) ? 20 : g;
        }

        function calcSlideWidth(spv, gap) {
            return Math.floor((viewport.offsetWidth - gap * (spv - 1)) / spv);
        }

        function totalPages() {
            var vis = visibleSlides().length;
            return vis > 0 ? Math.ceil(vis / getSPV()) : 1;
        }

        function applyTransform(page) {
            var spv = getSPV();
            var gap = getGap();
            var sw  = calcSlideWidth(spv, gap);
            wrap.style.setProperty('--mcw-slide-width', sw + 'px');
            var tx = page * spv * (sw + gap);
            track.style.transform = 'translateX(-' + tx + 'px)';
        }

        function renderDots() {
            var pages = totalPages();
            dotsWrap.innerHTML = '';
            if (pages <= 1) return;
            for (var i = 0; i < pages; i++) {
                var btn = document.createElement('button');
                btn.className   = 'mcw-dot' + (i === currentPage ? ' active' : '');
                btn.type        = 'button';
                btn.setAttribute('role', 'tab');
                btn.setAttribute('aria-selected', i === currentPage ? 'true' : 'false');
                btn.setAttribute('aria-label', 'Página ' + (i + 1));
                btn.dataset.page = i;
                dotsWrap.appendChild(btn);
            }
        }

        function updateArrows() {
            if (prevBtn) prevBtn.disabled = (currentPage <= 0);
            if (nextBtn) nextBtn.disabled = (currentPage >= totalPages() - 1);
        }

        function goTo(page) {
            var max = totalPages() - 1;
            currentPage = Math.max(0, Math.min(page, max));
            applyTransform(currentPage);
            renderDots();
            updateArrows();
        }

        // ── Autoplay ─────────────────────────────────────────────────────────
        function startAutoplay() {
            if (!autoplay) return;
            stopAutoplay();
            autoTimer = setInterval(function () {
                goTo(currentPage >= totalPages() - 1 ? 0 : currentPage + 1);
            }, autoplayMs);
        }
        function stopAutoplay() {
            if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
        }

        // ── Navigation ───────────────────────────────────────────────────────
        if (prevBtn) prevBtn.addEventListener('click', function () { stopAutoplay(); goTo(currentPage - 1); startAutoplay(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { stopAutoplay(); goTo(currentPage + 1); startAutoplay(); });

        dotsWrap.addEventListener('click', function (e) {
            var dot = e.target.closest('.mcw-dot');
            if (!dot) return;
            stopAutoplay();
            goTo(parseInt(dot.dataset.page, 10));
            startAutoplay();
        });

        // ── Touch / Swipe ────────────────────────────────────────────────────
        var touchX = 0;
        track.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', function (e) {
            var diff = touchX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) {
                stopAutoplay();
                goTo(diff > 0 ? currentPage + 1 : currentPage - 1);
                startAutoplay();
            }
        }, { passive: true });

        // ── Keyboard ─────────────────────────────────────────────────────────
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  { stopAutoplay(); goTo(currentPage - 1); startAutoplay(); }
            if (e.key === 'ArrowRight') { stopAutoplay(); goTo(currentPage + 1); startAutoplay(); }
        });

        // ── Resize ───────────────────────────────────────────────────────────
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () { goTo(currentPage); }, 150);
        });

        // ── Drawer ───────────────────────────────────────────────────────────
        function openDrawer(data) {
            drawerName.textContent     = data.name        || '';
            drawerCategory.textContent = data.category    || '';
            drawerDesc.innerHTML       = data.description || '';

            if (data.price && data.price.trim()) {
                drawerPrice.innerHTML      = data.price;
                drawerPrice.style.display  = 'inline-block';
            } else {
                drawerPrice.style.display  = 'none';
            }

            var btnTitle = enrollConfig.title || '<?php echo esc_js( __( 'Inscrever-se no Curso', 'moodle-management' ) ); ?>';
            drawerEnroll.innerHTML = '<i class="fas fa-graduation-cap" aria-hidden="true"></i> ' + btnTitle;

            if (enrollConfig.color) {
                drawerEnroll.style.setProperty('--mcw-btn-color', enrollConfig.color);
            }

            var url = '#';
            if (enrollConfig.urlTemplate) {
                url = enrollConfig.urlTemplate.replace('{course_id}', data.id);
            } else if (enrollConfig.baseUrl) {
                url = enrollConfig.baseUrl + '/course/view.php?id=' + data.id;
            }
            drawerEnroll.href = url;

            drawer.classList.add('active');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            stopAutoplay();
        }

        function closeDrawer() {
            drawer.classList.remove('active');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            startAutoplay();
        }

        function cardData(card) {
            return {
                id:          card.dataset.courseId,
                name:        card.dataset.courseName,
                description: card.dataset.courseDescription,
                category:    card.dataset.courseCategory,
                price:       card.dataset.coursePrice,
            };
        }

        // Click anywhere on the card
        wrap.querySelectorAll('.mcw-card').forEach(function (card) {
            card.addEventListener('click', function () { openDrawer(cardData(card)); });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDrawer(cardData(card)); }
            });
        });

        // Info button (stop propagation to avoid double-fire)
        wrap.querySelectorAll('.mcw-info-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var card = btn.closest('.mcw-card');
                if (card) openDrawer(cardData(card));
            });
        });

        drawerOverlay.addEventListener('click', closeDrawer);
        drawerClose.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('active')) closeDrawer();
        });

        // ── Init ─────────────────────────────────────────────────────────────
        goTo(0);
        startAutoplay();
    });
}());
</script>
