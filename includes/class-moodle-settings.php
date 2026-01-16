<?php
/**
 * Moodle Management Settings
 * 
 * Gerencia as configurações de exibição de preços e outros ajustes
 *
 * @package Moodle_Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moodle_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings menu
     */
    public function add_settings_menu() {
        add_submenu_page(
            'moodle-management',
            __('Configurações de Preços', 'moodle-management'),
            __('Configurações de Preços', 'moodle-management'),
            'manage_options',
            'moodle-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('moodle_pricing_settings', 'moodle_decimal_separator');
        register_setting('moodle_pricing_settings', 'moodle_currency_symbol');
        register_setting('moodle_pricing_settings', 'moodle_price_message');

        add_settings_section(
            'moodle_pricing_section',
            __('Configuração de Preços', 'moodle-management'),
            array($this, 'render_settings_section'),
            'moodle_pricing_settings'
        );

        add_settings_field(
            'moodle_decimal_separator',
            __('Separador de Decimais', 'moodle-management'),
            array($this, 'render_decimal_field'),
            'moodle_pricing_settings',
            'moodle_pricing_section'
        );

        add_settings_field(
            'moodle_currency_symbol',
            __('Símbolo de Moeda', 'moodle-management'),
            array($this, 'render_currency_field'),
            'moodle_pricing_settings',
            'moodle_pricing_section'
        );

        add_settings_field(
            'moodle_price_message',
            __('Mensagem de Preço', 'moodle-management'),
            array($this, 'render_message_field'),
            'moodle_pricing_settings',
            'moodle_pricing_section'
        );
    }

    /**
     * Render settings section
     */
    public function render_settings_section() {
        echo wp_kses_post(__('Configure aqui como os preços serão exibidos nos cards dos cursos.', 'moodle-management'));
    }

    /**
     * Render decimal separator field
     */
    public function render_decimal_field() {
        $value = get_option('moodle_decimal_separator', ',');
        ?>
        <select name="moodle_decimal_separator" id="moodle_decimal_separator">
            <option value="," <?php selected($value, ','); ?>>Vírgula (1.234,56)</option>
            <option value="." <?php selected($value, '.'); ?>>Ponto (1,234.56)</option>
        </select>
        <?php
    }

    /**
     * Render currency symbol field
     */
    public function render_currency_field() {
        $value = get_option('moodle_currency_symbol', 'R$');
        ?>
        <input 
            type="text" 
            name="moodle_currency_symbol" 
            id="moodle_currency_symbol" 
            value="<?php echo esc_attr($value); ?>"
            placeholder="R$, $, €, £"
            style="width: 100px;"
        />
        <p class="description"><?php echo esc_html(__('Símbolo que será exibido antes ou depois do preço.', 'moodle-management')); ?></p>
        <?php
    }

    /**
     * Render price message field
     */
    public function render_message_field() {
        $value = get_option('moodle_price_message', 'Em até {price} de {installments}x');
        ?>
        <textarea 
            name="moodle_price_message" 
            id="moodle_price_message"
            rows="3"
            style="width: 100%;"
        ><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php echo esc_html(__('Use {price} para o valor da parcela e {installments} para o número de parcelas.', 'moodle-management')); ?><br/>
            <?php echo esc_html(__('Exemplo: "Em até {price} de {installments}x" resulta em "Em até R$ 29,99 de 18x"', 'moodle-management')); ?>
        </p>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            add_settings_error('moodle_pricing_settings', 'settings_updated', __('Configurações salvas com sucesso.', 'moodle-management'), 'updated');
        }

        settings_errors('moodle_pricing_settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('moodle_pricing_settings');
                do_settings_sections('moodle_pricing_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get decimal separator
     */
    public static function get_decimal_separator() {
        return get_option('moodle_decimal_separator', ',');
    }

    /**
     * Get currency symbol
     */
    public static function get_currency_symbol() {
        return get_option('moodle_currency_symbol', 'R$');
    }

    /**
     * Get price message template
     */
    public static function get_price_message() {
        return get_option('moodle_price_message', 'Em até {price} de {installments}x');
    }

    /**
     * Format price with settings
     */
    public static function format_price($price, $installments = 1) {
        $decimal_sep = self::get_decimal_separator();
        $currency = self::get_currency_symbol();
        
        // Formatar o preço
        $formatted_price = number_format($price, 2, $decimal_sep, '');
        
        // Criar a mensagem
        $message = self::get_price_message();
        $message = str_replace(
            array('{price}', '{installments}'),
            array($currency . ' ' . $formatted_price, $installments),
            $message
        );
        
        return $message;
    }
}
