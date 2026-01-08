<?php
/**
 * Classe responsável pela visualização de respostas no admin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Responses
 *
 * Gerencia a interface administrativa de respostas.
 */
class MLA_Responses
{

    /**
     * Serviço de respostas.
     *
     * @var MLA_Responses_Service
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
        $this->service = new MLA_Responses_Service();
        $this->projects_service = new MLA_Projects_Service();
    }

    /**
     * Renderiza a página de respostas.
     *
     * @return void
     */
    public function render_page()
    {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'metodologia-leitor-apreciador'));
        }

        // Determinar visualização
        $view = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : 'list';

        switch ($view) {
            case 'detail':
                $this->render_detail();
                break;
            default:
                $this->render_list();
                break;
        }
    }

    /**
     * Renderiza a lista de respostas.
     *
     * @return void
     */
    private function render_list()
    {
        // Filtros
        $filters = array(
            'project_id' => isset($_GET['project_id']) ? sanitize_text_field(wp_unslash($_GET['project_id'])) : '',
            'text_id' => isset($_GET['text_id']) ? sanitize_text_field(wp_unslash($_GET['text_id'])) : '',
            'wp_user_id' => isset($_GET['user_id']) ? intval($_GET['user_id']) : '',
            'status' => isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '',
        );

        // Paginação
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters['limit'] = $limit;
        $filters['offset'] = $offset;

        // Obter respostas
        $responses = $this->service->get_all($filters);
        $total = $this->service->count($filters);

        // Obter projetos para filtro
        $projects = $this->projects_service->get_for_select();

        // Obter textos para filtro
        $texts = $this->get_texts_for_filter();

        // Calcular páginas
        $total_pages = ceil($total / $limit);

        include MLA_PLUGIN_DIR . 'admin/partials/responses-list.php';
    }

    /**
     * Renderiza o detalhe de uma resposta.
     *
     * @return void
     */
    private function render_detail()
    {
        $response_id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';

        if (empty($response_id)) {
            wp_die(esc_html__('ID da resposta não informado.', 'metodologia-leitor-apreciador'));
        }

        $response = $this->service->get_by_id($response_id);

        if (is_wp_error($response) || !$response) {
            wp_die(esc_html__('Resposta não encontrada.', 'metodologia-leitor-apreciador'));
        }

        // Obter histórico de versões
        $history = $this->service->get_history($response_id);

        // Obter dados do usuário WordPress
        $user = get_user_by('id', $response['wp_user_id']);

        // Obter dados do texto
        $text = null;
        if (!empty($response['text_id'])) {
            $texts_service = new MLA_Texts_Service();
            $text = $texts_service->get_by_id($response['text_id']);
        }

        include MLA_PLUGIN_DIR . 'admin/partials/responses-detail.php';
    }

    /**
     * Obtém textos para o filtro de seleção.
     *
     * @return array Lista de textos (post_id => título).
     */
    private function get_texts_for_filter()
    {
        $args = array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => 100,
            'meta_query' => array(
                array(
                    'key' => '_mla_enabled',
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        );

        $query = new WP_Query($args);
        $texts = array();

        foreach ($query->posts as $post) {
            $text_id = get_post_meta($post->ID, '_mla_text_id', true);
            if ($text_id) {
                $texts[$text_id] = $post->post_title;
            }
        }

        return $texts;
    }
}
