<?php
/**
 * Classe responsável pelo menu administrativo.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Admin_Menu
 *
 * Registra o menu e submenus do plugin no admin.
 */
class MLA_Admin_Menu
{

    /**
     * Instância do controller de projetos.
     *
     * @var MLA_Projects
     */
    private $projects;

    /**
     * Instância do controller de textos.
     *
     * @var MLA_Texts
     */
    private $texts;

    /**
     * Instância do controller de respostas.
     *
     * @var MLA_Responses
     */
    private $responses;

    /**
     * Instância do controller de configurações.
     *
     * @var MLA_Settings
     */
    private $settings;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->projects = new MLA_Projects();
        $this->texts = new MLA_Texts();
        $this->responses = new MLA_Responses();
        $this->settings = new MLA_Settings();
    }

    /**
     * Registra as páginas do menu.
     *
     * @return void
     */
    public function add_menu_pages()
    {
        // Menu principal
        add_menu_page(
            __('Leitor-Apreciador', 'metodologia-leitor-apreciador'),
            __('Leitor-Apreciador', 'metodologia-leitor-apreciador'),
            'manage_options',
            'mla-main',
            array($this, 'render_main_page'),
            'dashicons-book-alt',
            30
        );

        // Submenu: Dashboard (renomeia o primeiro item)
        add_submenu_page(
            'mla-main',
            __('Dashboard', 'metodologia-leitor-apreciador'),
            __('Dashboard', 'metodologia-leitor-apreciador'),
            'manage_options',
            'mla-main',
            array($this, 'render_main_page')
        );

        // Submenu: Projetos
        add_submenu_page(
            'mla-main',
            __('Projetos', 'metodologia-leitor-apreciador'),
            __('Projetos', 'metodologia-leitor-apreciador'),
            'manage_options',
            'mla-projects',
            array($this->projects, 'render_page')
        );

        // Submenu: Textos
        add_submenu_page(
            'mla-main',
            __('Textos', 'metodologia-leitor-apreciador'),
            __('Textos', 'metodologia-leitor-apreciador'),
            'manage_options',
            'mla-texts',
            array($this->texts, 'render_page')
        );

        // Submenu: Respostas
        add_submenu_page(
            'mla-main',
            __('Respostas', 'metodologia-leitor-apreciador'),
            __('Respostas', 'metodologia-leitor-apreciador'),
            'manage_options',
            'mla-responses',
            array($this->responses, 'render_page')
        );

        // Submenu: Configurações
        add_submenu_page(
            'mla-main',
            __('Configurações', 'metodologia-leitor-apreciador'),
            __('Configurações', 'metodologia-leitor-apreciador'),
            'manage_options',
            'mla-settings',
            array($this->settings, 'render_page')
        );
    }

    /**
     * Renderiza a página principal (dashboard).
     *
     * @return void
     */
    public function render_main_page()
    {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'metodologia-leitor-apreciador'));
        }

        // Obter estatísticas
        $projects_service = new MLA_Projects_Service();
        $responses_service = new MLA_Responses_Service();

        $projects = $projects_service->get_all();
        $projects_count = is_wp_error($projects) ? 0 : count($projects);

        $responses_count = $responses_service->count();
        $submitted_count = $responses_service->count(array('status' => 'submitted'));
        $draft_count = $responses_service->count(array('status' => 'draft'));

        // Contar textos com metodologia ativa
        global $wpdb;
        $texts_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_mla_enabled' AND meta_value = '1'"
        );

        include MLA_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
}
