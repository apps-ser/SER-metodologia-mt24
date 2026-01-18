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
        add_action('wp_ajax_mla_get_texts_by_project', array($this, 'ajax_get_texts_by_project'));
        add_action('wp_ajax_mla_get_analysis_history', array($this, 'ajax_get_analysis_history'));
        add_action('wp_ajax_mla_save_analysis', array($this, 'ajax_save_analysis'));
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

        // Processar ações
        $this->process_actions();

        // Determinar visualização
        $view = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : 'list';

        switch ($view) {
            case 'detail':
                $this->render_detail();
                break;
            case 'analyses':
                $this->render_analyses();
                break;
            default:
                $this->render_list();
                break;
        }
    }

    /**
     * Processa ações de lista.
     */
    private function process_actions()
    {
        if (!isset($_POST['mla_response_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mla_response_nonce'])), 'mla_response_action')) {
            wp_die(esc_html__('Verificação de segurança falhou.', 'metodologia-leitor-apreciador'));
        }

        $action = isset($_POST['mla_action']) ? sanitize_text_field(wp_unslash($_POST['mla_action'])) : '';
        $response_id = isset($_POST['response_id']) ? sanitize_text_field(wp_unslash($_POST['response_id'])) : '';

        if (empty($response_id))
            return;

        switch ($action) {
            case 'archive':
                $this->service->archive($response_id);
                wp_safe_redirect(add_query_arg(array('page' => 'mla-responses', 'message' => 'archived'), admin_url('admin.php')));
                exit;
            case 'restore':
                $this->service->restore($response_id);
                wp_safe_redirect(add_query_arg(array('page' => 'mla-responses', 'message' => 'restored'), admin_url('admin.php')));
                exit;
            case 'delete':
                $this->service->delete($response_id);
                wp_safe_redirect(add_query_arg(array('page' => 'mla-responses', 'message' => 'deleted'), admin_url('admin.php')));
                exit;
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
            'is_archived' => isset($_GET['is_archived']) ? sanitize_text_field(wp_unslash($_GET['is_archived'])) : 'false',
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
        $texts = $this->get_texts_for_filter($filters['project_id']);

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

        // Obter template para exibição dinâmica
        $form_renderer = new MLA_Form_Renderer();
        $project_id = isset($response['project_id']) ? $response['project_id'] : null;
        $steps = $form_renderer->get_steps($project_id);

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

        // 2. Formatar dados para a IA (Incluindo nomes de usuários e títulos dos campos)
        $project_id = !empty($responses[0]['project_id']) ? $responses[0]['project_id'] : null;
        $form_renderer = new MLA_Form_Renderer();
        $steps = $form_renderer->get_steps($project_id);

        $step_labels = array();
        foreach ($steps as $step) {
            if (!empty($step['key'])) {
                $step_labels[$step['key']] = $step['title'];
            }
        }

        $analysis_data = array();
        foreach ($responses as $resp) {
            $user = get_user_by('id', $resp['wp_user_id']);
            $reader_name = $user ? $user->display_name : __('Anônimo', 'metodologia-leitor-apreciador');

            $labeled_respostas = array();
            if (isset($resp['data']) && is_array($resp['data'])) {
                foreach ($resp['data'] as $k => $v) {
                    if ($k === 'perguntas_paragrafos') {
                        // Trata perguntas por parágrafo separadamente se necessário, ou apenas passa a chave
                        $labeled_respostas['Perguntas por Parágrafo'] = $v;
                        continue;
                    }
                    $label = isset($step_labels[$k]) ? $step_labels[$k] : ucwords(str_replace('_', ' ', $k));
                    $labeled_respostas[$label] = $v;
                }
            }

            $analysis_data[] = array(
                'leitor' => $reader_name,
                'respostas' => $labeled_respostas
            );
        }

        // 3. Buscar contexto adicional (Texto Original e Metodologia)
        $settings = get_option('mla_settings', array());

        // Obter tipos de post permitidos das configurações
        $allowed_post_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');

        // Texto Original
        $original_text = '';
        $text_query = new WP_Query(array(
            'post_type' => $allowed_post_types,
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_mla_text_id',
                    'value' => $text_id,
                    'compare' => '=',
                ),
            ),
        ));

        if ($text_query->have_posts()) {
            $original_text = $text_query->posts[0]->post_content;
        }

        $context = array(
            'methodology' => isset($settings['methodology_explanation']) ? $settings['methodology_explanation'] : '',
            'original_text' => $original_text
        );

        // 4. Chamar o serviço de IA (com suporte a Lotes/Map-Reduce se necessário)
        $ai_service = new MLA_AI_Service();
        $batch_size = 30; // Ajustável
        $total_responses = count($analysis_data);
        $result = '';

        if ($total_responses <= $batch_size) {
            // Processamento Direto (Caso Simples)
            $result = $ai_service->analyze_responses($analysis_data, $context);
        } else {
            // Processamento em Lotes (Map-Reduce)
            $batches = array_chunk($analysis_data, $batch_size);
            $partial_results = array();
            $batch_count = count($batches);

            foreach ($batches as $index => $batch) {
                $batch_context = $context;
                $batch_context['is_partial'] = true;

                $partial_res = $ai_service->analyze_responses($batch, $batch_context);
                if (is_wp_error($partial_res)) {
                    wp_send_json_error(array('message' => sprintf(__('Erro no lote %d de %d: %s', 'metodologia-leitor-apreciador'), $index + 1, $batch_count, $partial_res->get_error_message())));
                }
                $partial_results[] = $partial_res;
            }

            // Consolidação Final (Reduce)
            $result = $ai_service->consolidate_partial_analyses($partial_results, $context);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // 4. Persistir no Supabase
        $analysis_persist_service = new MLA_AI_Analysis_Service();
        $settings = get_option('mla_settings', array());
        $model = isset($settings['openrouter_model']) ? $settings['openrouter_model'] : 'openai/gpt-4o-mini';

        $persist_result = $analysis_persist_service->save($text_id, $result, $model);

        if (is_wp_error($persist_result)) {
            // Log do erro de persistência
            error_log('MLA AI Persistence Error for text_id ' . $text_id . ': ' . $persist_result->get_error_message());
        }

        wp_send_json_success(array('content' => $result));
    }

    /**
     * Handler AJAX para obter textos de um projeto.
     */
    public function ajax_get_texts_by_project()
    {
        check_ajax_referer('mla_admin_nonce', 'nonce');

        $project_id = isset($_POST['project_id']) ? sanitize_text_field(wp_unslash($_POST['project_id'])) : '';
        $texts = $this->get_texts_for_filter($project_id);

        wp_send_json_success(array('texts' => $texts));
    }

    /**
     * Handler AJAX para obter o histórico de análises.
     */
    public function ajax_get_analysis_history()
    {
        // Debug persistente (visível mesmo sem WP_DEBUG ativo)
        error_log('MLA DEBUG: AJAX get_analysis_history triggered');

        if (!isset($_POST['nonce'])) {
            error_log('MLA DEBUG: Nonce missing in request');
        }

        check_ajax_referer('mla_admin_nonce', 'nonce');

        $text_id = isset($_POST['text_id']) ? sanitize_text_field(wp_unslash($_POST['text_id'])) : '';
        error_log('MLA DEBUG: text_id received: ' . $text_id);

        if (empty($text_id)) {
            error_log('MLA DEBUG: Missing text_id error');
            wp_send_json_error(array('message' => __('ID do texto não informado.', 'metodologia-leitor-apreciador')));
        }

        try {
            $analysis_service = new MLA_AI_Analysis_Service();
            $analyses = $analysis_service->get_all_by_text($text_id);

            if (is_wp_error($analyses)) {
                error_log('MLA DEBUG: Supabase FETCH ERROR: ' . $analyses->get_error_message());
                wp_send_json_error(array('message' => $analyses->get_error_message()));
            }

            error_log('MLA DEBUG: SUCCESS. Records found: ' . (is_array($analyses) ? count($analyses) : 0));

            // Return raw data for frontend rendering
            wp_send_json_success(array(
                'analyses' => is_array($analyses) ? $analyses : array()
            ));

        } catch (Exception $e) {
            error_log('MLA DEBUG: EXCEPTION: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Handler AJAX para salvar edição de uma análise.
     */
    public function ajax_save_analysis()
    {
        check_ajax_referer('mla_admin_nonce', 'nonce');

        $analysis_id = isset($_POST['analysis_id']) ? sanitize_text_field(wp_unslash($_POST['analysis_id'])) : '';
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';

        if (empty($analysis_id) || empty($content)) {
            wp_send_json_error(array('message' => __('Dados incompletos.', 'metodologia-leitor-apreciador')));
        }

        try {
            $analysis_service = new MLA_AI_Analysis_Service();
            $result = $analysis_service->update($analysis_id, array('content' => $content));

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }

            wp_send_json_success(array('message' => __('Análise salva com sucesso.', 'metodologia-leitor-apreciador')));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Renderiza o histórico de análises de IA.
     */
    private function render_analyses()
    {
        error_log('MLA Debug: render_analyses called. GET params: ' . print_r($_GET, true));
        try {
            $text_id = isset($_GET['text_id']) ? sanitize_text_field(wp_unslash($_GET['text_id'])) : '';

            if (empty($text_id)) {
                wp_die(esc_html__('ID do texto não informado.', 'metodologia-leitor-apreciador'));
            }

            // Obter tipos de post permitidos das configurações
            $settings = get_option('mla_settings', array());
            $allowed_post_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');

            // Obter título do texto
            $text_title = __('Texto não encontrado', 'metodologia-leitor-apreciador');
            $text_query = new WP_Query(array(
                'post_type' => $allowed_post_types,
                'posts_per_page' => 1,
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => '_mla_text_id',
                        'value' => $text_id,
                        'compare' => '=',
                    ),
                ),
            ));

            if ($text_query->have_posts()) {
                $text_title = $text_query->posts[0]->post_title;
            }

            $analysis_service = new MLA_AI_Analysis_Service();
            $analyses = $analysis_service->get_all_by_text($text_id);

            include MLA_PLUGIN_DIR . 'admin/partials/responses-analyses.php';
        } catch (Exception $e) {
            wp_die(esc_html__('Erro ao carregar histórico: ', 'metodologia-leitor-apreciador') . esc_html($e->getMessage()));
        }
    }

    /**
     * Obtém textos para o filtro de seleção.
     *
     * @param string $project_id Opcional. ID do projeto para filtrar.
     * @return array Lista de textos (post_id => título).
     */
    private function get_texts_for_filter($project_id = '')
    {
        // Obter tipos de post permitidos das configurações
        $settings = get_option('mla_settings', array());
        $allowed_post_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');

        $meta_query = array(
            array(
                'key' => '_mla_enabled',
                'value' => '1',
                'compare' => '=',
            ),
        );

        if (!empty($project_id)) {
            $meta_query[] = array(
                'key' => '_mla_project_id',
                'value' => $project_id,
                'compare' => '=',
            );
        }

        $args = array(
            'post_type' => $allowed_post_types,
            'posts_per_page' => 100,
            'meta_query' => $meta_query
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
