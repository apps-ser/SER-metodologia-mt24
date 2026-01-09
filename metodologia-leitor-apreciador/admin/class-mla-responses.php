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

        // AJAX para Análise de IA
        add_action('wp_ajax_mla_analyze_responses', array($this, 'ajax_analyze_responses'));
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
     * Handler AJAX para realizar a análise por IA.
     */
    public function ajax_analyze_responses()
    {
        check_ajax_referer('mla_admin_nonce', 'nonce');

        $text_id = isset($_POST['text_id']) ? sanitize_text_field(wp_unslash($_POST['text_id'])) : '';

        if (empty($text_id)) {
            wp_send_json_error(array('message' => __('ID do texto não informado.', 'metodologia-leitor-apreciador')));
        }

        // 1. Buscar todas as respostas submetidas para este texto
        $responses = $this->service->get_all(array(
            'text_id' => $text_id,
            'status' => 'submitted',
            'limit' => 200 // Limite razoável para análise
        ));

        if (empty($responses)) {
            wp_send_json_error(array('message' => __('Nenhuma resposta submetida encontrada para este texto.', 'metodologia-leitor-apreciador')));
        }

        // 2. Formatar dados para a IA (Incluindo nomes de usuários)
        $analysis_data = array();
        foreach ($responses as $resp) {
            $user = get_user_by('id', $resp['wp_user_id']);
            $reader_name = $user ? $user->display_name : __('Anônimo', 'metodologia-leitor-apreciador');

            $analysis_data[] = array(
                'leitor' => $reader_name,
                'respostas' => $resp['content']
            );
        }

        // 3. Chamar o serviço de IA
        $ai_service = new MLA_AI_Service();
        $result = $ai_service->analyze_responses($analysis_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // 4. Persistir no Supabase
        $analysis_persist_service = new MLA_AI_Analysis_Service();
        $settings = get_option('mla_settings', array());
        $model = isset($settings['openrouter_model']) ? $settings['openrouter_model'] : 'openai/gpt-4o-mini';

        $persist_result = $analysis_persist_service->save($text_id, $result, $model);

        if (is_wp_error($persist_result)) {
            // Log do erro de persistência, mas retorna o resultado da IA para o usuário
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MLA AI Persistence Error: ' . $persist_result->get_error_message());
            }
        }

        wp_send_json_success(array('content' => $result));
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
