<?php
/**
 * Plugin Name:       Metodologia Mateus 24
 * Plugin URI:        https://github.com/ser/metodologia-leitor-apreciador
 * Description:       Implementa a Metodologia Mateus 24 para coleta estruturada de respostas reflexivas, com integração Supabase.
 * Version:           1.2.5
 * Requires at least: 6.5.7
 * Requires PHP:      7.4.33
 * Author:            SER
 * Author URI:        https://ser.org.br
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       metodologia-leitor-apreciador
 * Domain Path:       /languages
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Versão atual do plugin.
 */
define('MLA_VERSION', '1.2.5');

/**
 * Caminho absoluto do diretório do plugin.
 */
define('MLA_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * URL base do plugin.
 */
define('MLA_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Basename do plugin.
 */
define('MLA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Verificar requisitos mínimos.
 *
 * @return bool True se os requisitos forem atendidos.
 */
function mla_check_requirements()
{
    $php_version = '7.4.33';
    $wp_version = '6.5.7';

    if (version_compare(PHP_VERSION, $php_version, '<')) {
        add_action('admin_notices', function () use ($php_version) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: %s: Versão mínima do PHP */
                    esc_html__('Metodologia Mateus 24 requer PHP %s ou superior.', 'metodologia-leitor-apreciador'),
                    esc_html($php_version)
                )
            );
        });
        return false;
    }

    global $wp_version;
    if (version_compare($wp_version, $wp_version, '<')) {
        add_action('admin_notices', function () use ($wp_version) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    /* translators: %s: Versão mínima do WordPress */
                    esc_html__('Metodologia Mateus 24 requer WordPress %s ou superior.', 'metodologia-leitor-apreciador'),
                    esc_html($wp_version)
                )
            );
        });
        return false;
    }

    // Verificar se as credenciais do Supabase estão configuradas (via constantes ou opções)
    $settings = get_option('mla_settings', array());
    $has_settings = !empty($settings['supabase_url']) && !empty($settings['supabase_anon_key']);
    $has_constants = defined('MLA_SUPABASE_URL') && defined('MLA_SUPABASE_ANON_KEY');

    if (!$has_constants && !$has_settings) {
        add_action('admin_notices', function () {
            printf(
                '<div class="notice notice-warning"><p>%s <a href="%s">%s</a> %s</p></div>',
                esc_html__('Metodologia Mateus 24: Supabase não está configurado.', 'metodologia-leitor-apreciador'),
                esc_url(admin_url('admin.php?page=mla-settings')),
                esc_html__('Acesse as Configurações', 'metodologia-leitor-apreciador'),
                esc_html__('ou defina as constantes no wp-config.php.', 'metodologia-leitor-apreciador')
            );
        });
        // Não bloquear a ativação, apenas avisar
    }

    return true;
}

/**
 * Código executado durante a ativação do plugin.
 */
function mla_activate()
{
    require_once MLA_PLUGIN_DIR . 'includes/class-mla-activator.php';
    MLA_Activator::activate();
}

/**
 * Código executado durante a desativação do plugin.
 */
function mla_deactivate()
{
    require_once MLA_PLUGIN_DIR . 'includes/class-mla-activator.php';
    MLA_Activator::deactivate();
}

register_activation_hook(__FILE__, 'mla_activate');
register_deactivation_hook(__FILE__, 'mla_deactivate');

/**
 * Carregar classes do plugin.
 */
function mla_load_classes()
{
    // Includes
    require_once MLA_PLUGIN_DIR . 'includes/class-mla-loader.php';
    require_once MLA_PLUGIN_DIR . 'includes/class-mla-i18n.php';

    // Services
    require_once MLA_PLUGIN_DIR . 'services/class-mla-supabase-client.php';
    require_once MLA_PLUGIN_DIR . 'services/class-mla-projects-service.php';
    require_once MLA_PLUGIN_DIR . 'services/class-mla-texts-service.php';
    require_once MLA_PLUGIN_DIR . 'services/class-mla-responses-service.php';
    require_once MLA_PLUGIN_DIR . 'services/class-mla-ai-service.php';
    require_once MLA_PLUGIN_DIR . 'services/class-mla-ai-analysis-service.php';
    require_once MLA_PLUGIN_DIR . 'services/class-mla-toon.php';

    // Admin
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-admin.php';
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-admin-menu.php';
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-metabox.php';
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-projects.php';
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-texts.php';
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-responses.php';
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-settings.php';
    require_once MLA_PLUGIN_DIR . 'admin/class-mla-export.php';

    // Frontend
    require_once MLA_PLUGIN_DIR . 'frontend/class-mla-public.php';
    require_once MLA_PLUGIN_DIR . 'frontend/class-mla-form-renderer.php';
}

/**
 * Inicializar o plugin.
 */
function mla_init()
{
    if (!mla_check_requirements()) {
        return;
    }

    mla_load_classes();

    // Inicializar o loader
    $loader = new MLA_Loader();

    // Internacionalização
    $i18n = new MLA_I18n();
    $loader->add_action('plugins_loaded', $i18n, 'load_plugin_textdomain');

    // Admin
    if (is_admin()) {
        $admin = new MLA_Admin();
        $loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');

        $admin_menu = new MLA_Admin_Menu();
        $loader->add_action('admin_menu', $admin_menu, 'add_menu_pages');

        $metabox = new MLA_Metabox();
        $loader->add_action('add_meta_boxes', $metabox, 'add_meta_boxes');
        $loader->add_action('save_post', $metabox, 'save_meta_box_data');
    }

    // Frontend
    $public = new MLA_Public();
    $loader->add_action('wp_footer', $public, 'render_form_in_footer', 10);
    $loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
    $loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');

    // REST API
    $loader->add_action('rest_api_init', $public, 'register_rest_routes');

    // Executar todos os hooks
    $loader->run();
}

add_action('plugins_loaded', 'mla_init');
