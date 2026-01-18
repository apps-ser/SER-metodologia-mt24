<?php
/**
 * Classe principal da área administrativa.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Admin
 *
 * Gerencia estilos e scripts da área administrativa.
 */
class MLA_Admin
{

    /**
     * Registra os estilos do admin.
     *
     * @param string $hook Hook da página atual.
     *
     * @return void
     */
    public function enqueue_styles($hook)
    {
        // Verificar se estamos em uma página do plugin
        if (!$this->is_plugin_page($hook)) {
            return;
        }

        wp_enqueue_style(
            'mla-admin',
            MLA_PLUGIN_URL . 'assets/css/mla-admin.css',
            array(),
            MLA_VERSION,
            'all'
        );

        wp_enqueue_style(
            'mla-easymde',
            'https://unpkg.com/easymde/dist/easymde.min.css',
            array(),
            '2.18.0'
        );

        wp_enqueue_style('wp-jquery-ui-dialog');
    }

    /**
     * Registra os scripts do admin.
     *
     * @param string $hook Hook da página atual.
     *
     * @return void
     */
    public function enqueue_scripts($hook)
    {
        // Verificar se estamos em uma página do plugin
        if (!$this->is_plugin_page($hook)) {
            return;
        }

        wp_enqueue_script(
            'mla-easymde',
            'https://unpkg.com/easymde/dist/easymde.min.js',
            array(),
            '2.18.0',
            true
        );

        wp_enqueue_script(
            'mla-admin',
            MLA_PLUGIN_URL . 'assets/js/mla-admin.js',
            array('jquery', 'jquery-ui-dialog', 'mla-easymde'),
            MLA_VERSION,
            true
        );

        wp_localize_script('mla-admin', 'mlaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'nonce' => wp_create_nonce('mla_admin_nonce'),
            'i18n' => array(
                'confirmDelete' => __('Tem certeza que deseja excluir este item?', 'metodologia-leitor-apreciador'),
                'processingAI' => __('Processando análise... isso pode levar cerca de um minuto.', 'metodologia-leitor-apreciador'),
                'errorAI' => __('Ocorreu um erro ao processar a análise por IA.', 'metodologia-leitor-apreciador'),
                'dontCloseTip' => __('Por favor, não feche esta janela ou mude de página até que o processo seja concluído.', 'metodologia-leitor-apreciador'),
                'saving' => __('Salvando...', 'metodologia-leitor-apreciador'),
                'saved' => __('Salvo!', 'metodologia-leitor-apreciador'),
                'error' => __('Erro ao salvar.', 'metodologia-leitor-apreciador'),
            ),
        ));
    }

    /**
     * Verifica se estamos em uma página do plugin.
     *
     * @param string $hook Hook da página atual.
     *
     * @return bool True se for uma página do plugin.
     */
    private function is_plugin_page($hook)
    {
        $plugin_pages = array(
            'toplevel_page_mla-main',
            'mateus-24_page_mla-projects',
            'mateus-24_page_mla-texts',
            'mateus-24_page_mla-responses',
            'mateus-24_page_mla-settings',
            'mateus-24_page_mla-debug',
            // Variantes baseadas no slug (padrão WP)
            'mla-main_page_mla-projects',
            'mla-main_page_mla-texts',
            'mla-main_page_mla-responses',
            'mla-main_page_mla-settings',
            'mla-main_page_mla-debug',
        );

        // Também verificar páginas de edição de posts
        if (in_array($hook, array('post.php', 'post-new.php'), true)) {
            return true;
        }

        return in_array($hook, $plugin_pages, true);
    }
}
