<?php
/**
 * Classe responsável pela listagem de textos no admin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Texts
 *
 * Gerencia a interface administrativa de textos (posts com metodologia ativa).
 */
class MLA_Texts
{

    /**
     * Serviço de textos.
     *
     * @var MLA_Texts_Service
     */
    private $service;

    /**
     * Serviço de projetos.
     *
     * @var MLA_Projects_Service
     */
    private $projects_service;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->service = new MLA_Texts_Service();
        $this->projects_service = new MLA_Projects_Service();
    }

    /**
     * Renderiza a página de textos.
     *
     * @return void
     */
    public function render_page()
    {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'metodologia-leitor-apreciador'));
        }

        // Processar ações
        $this->process_actions();

        // Obter configurações
        $settings = get_option('mla_settings', array());
        $allowed_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');

        // Filtros
        $filter_project = isset($_GET['project_id']) ? sanitize_text_field(wp_unslash($_GET['project_id'])) : '';
        $filter_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'active';

        // Obter posts com metodologia ativa
        // NOTA: O status 'archived' está no Supabase, não no WP. Precisamos filtrar pela tabela do Supabase.
        // A lógica atual busca posts WP. Precisamos cruzar com os dados do Supabase.

        // 1. Obter todos os textos do Supabase filtrados por status
        $supabase_args = array(
            'status' => $filter_status,
            'limit' => 1000 // Aumentar limite para cobrir paginação do WP se possível, ou refatorar para buscar do Supabase primeiro
        );

        if (!empty($filter_project)) {
            $supabase_args['project_id'] = $filter_project;
        }

        $supabase_texts = $this->service->get_all($supabase_args);

        // Extrair IDs de posts WP
        $wp_post_ids = array();
        if (!is_wp_error($supabase_texts)) {
            foreach ($supabase_texts as $text) {
                if (isset($text['wp_post_id'])) {
                    $wp_post_ids[] = intval($text['wp_post_id']);
                }
            }
        }

        // 2. Query WP usando os IDs obtidos
        $texts = array();
        if (!empty($wp_post_ids)) {
            $args = array(
                'post_type' => $allowed_types,
                'posts_per_page' => 50,
                'post__in' => $wp_post_ids,
                'orderby' => 'post__in' // Manter ordem do Supabase se possível, ou data
            );

            $query = new WP_Query($args);
            $texts = $query->posts;
        }

        // Obter projetos para filtro
        $projects = $this->projects_service->get_for_select();

        include MLA_PLUGIN_DIR . 'admin/partials/texts-list.php';
    }

    /**
     * Processa ações de formulário/lista.
     */
    private function process_actions()
    {
        if (!isset($_POST['mla_text_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mla_text_nonce'])), 'mla_text_action')) {
            wp_die(esc_html__('Verificação de segurança falhou.', 'metodologia-leitor-apreciador'));
        }

        $action = isset($_POST['mla_action']) ? sanitize_text_field(wp_unslash($_POST['mla_action'])) : '';
        $text_id = isset($_POST['text_id']) ? sanitize_text_field(wp_unslash($_POST['text_id'])) : '';

        if (empty($text_id))
            return;

        switch ($action) {
            case 'archive':
                $this->service->archive($text_id);
                wp_safe_redirect(add_query_arg(array('page' => 'mla-texts', 'message' => 'archived'), admin_url('admin.php')));
                exit;
            case 'restore':
                $this->service->restore($text_id);
                wp_safe_redirect(add_query_arg(array('page' => 'mla-texts', 'message' => 'restored'), admin_url('admin.php')));
                exit;
        }
    }

    /**
     * Obtém contagem de respostas para um texto.
     *
     * @param int $post_id ID do post WordPress.
     *
     * @return int Número de respostas.
     */
    public function get_response_count($post_id)
    {
        $text_id = get_post_meta($post_id, '_mla_text_id', true);

        if (empty($text_id)) {
            return 0;
        }

        return $this->service->count_responses($text_id);
    }

    /**
     * Helper para obter o ID do texto Supabase pelo Post ID
     */
    public function get_text_id($post_id)
    {
        return get_post_meta($post_id, '_mla_text_id', true);
    }
}
