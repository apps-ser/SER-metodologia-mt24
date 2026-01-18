<?php
/**
 * Classe responsável pelo frontend público.
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Public
 *
 * Gerencia a exibição do formulário no frontend.
 */
class MLA_Public
{

    /**
     * Form renderer.
     *
     * @var MLA_Form_Renderer
     */
    private $form_renderer;

    /**
     * Responses service.
     *
     * @var MLA_Responses_Service
     */
    private $responses_service;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->form_renderer = new MLA_Form_Renderer();
        $this->responses_service = new MLA_Responses_Service();
    }

    /**
     * Registra os estilos públicos.
     *
     * @return void
     */
    public function enqueue_styles()
    {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'mla-public',
            MLA_PLUGIN_URL . 'assets/css/mla-public.css',
            array(),
            MLA_VERSION
        );

        // Fix for LearnDash sidebar layout issue
        wp_add_inline_style(
            'mla-public',
            '.lms-topic-sidebar-wrapper .lms-topic-sidebar-data { position: static !important; }'
        );
    }

    /**
     * Registra os scripts públicos.
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_script(
            'mla-public-form',
            MLA_PLUGIN_URL . 'assets/js/mla-public-form.js',
            array('jquery'),
            MLA_VERSION,
            true
        );

        $settings = get_option('mla_settings', array());
        $post_id = get_the_ID();
        $text_id = get_post_meta($post_id, '_mla_text_id', true);
        $project_id = get_post_meta($post_id, '_mla_project_id', true);

        // Obter nome do projeto
        $project_name = '';
        if ($project_id && class_exists('MLA_Projects_Service')) {
            $projects_service = new MLA_Projects_Service();
            $project = $projects_service->get_by_id($project_id);
            if ($project && !is_wp_error($project)) {
                $project_name = $project['name'];
            }
        }

        // Obter etapas dinâmicas
        $steps = array();
        if (isset($this->form_renderer)) {
            $steps = $this->form_renderer->get_steps($project_id);
        }

        wp_localize_script('mla-public-form', 'mlaSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('mla/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'postId' => $post_id,
            'textId' => $text_id,
            'textTitle' => get_the_title($post_id),
            'projectId' => $project_id,
            'projectName' => $project_name,
            'userId' => get_current_user_id(),
            'userEmail' => wp_get_current_user()->user_email,
            'steps' => array_values($steps), // Garante array indexado para JS
            'autosaveInterval' => isset($settings['autosave_interval']) ? intval($settings['autosave_interval']) * 1000 : 20000,
            'progressiveForm' => isset($settings['progressive_form']) ? (bool) $settings['progressive_form'] : true,
            'i18n' => array(
                'saving' => __('Salvando...', 'metodologia-leitor-apreciador'),
                'saved' => __('Rascunho salvo automaticamente', 'metodologia-leitor-apreciador'),
                'error' => __('Erro ao salvar', 'metodologia-leitor-apreciador'),
                'submitting' => __('Submetendo...', 'metodologia-leitor-apreciador'),
                'submitted' => __('Apreciação submetida com sucesso!', 'metodologia-leitor-apreciador'),
                'confirmSubmit' => __('Suas respostas poderão ser analisadas de forma coletiva e comparativa dentro do projeto. Deseja submeter?', 'metodologia-leitor-apreciador'),
                'continueEditing' => __('Você já iniciou sua apreciação deste texto. Deseja continuar?', 'metodologia-leitor-apreciador'),
                'editWarning' => __('Esta edição substituirá a versão anterior para fins de análise.', 'metodologia-leitor-apreciador'),
                'submitButton' => __('✓ Submeter Apreciação', 'metodologia-leitor-apreciador'),
                'confirmTitle' => __('Confirmar Submissão', 'metodologia-leitor-apreciador'),
                'cancel' => __('Cancelar', 'metodologia-leitor-apreciador'),
                'confirm' => __('Confirmar', 'metodologia-leitor-apreciador'),
                'learndashCompletion' => isset($settings['learndash_completion_message']) ? $settings['learndash_completion_message'] : __('Etapa do curso concluída! Você já pode prosseguir para a próxima aula.', 'metodologia-leitor-apreciador'),
            ),
        ));
    }

    /**
     * Verifica se deve carregar os assets.
     *
     * @return bool
     */
    private function should_load_assets()
    {
        if (!is_singular()) {
            return false;
        }

        $post_id = get_the_ID();
        $enabled = get_post_meta($post_id, '_mla_enabled', true);

        return '1' === $enabled;
    }

    /**
     * Renderiza o formulário no rodapé da página.
     *
     * @return void
     */
    public function render_form_in_footer()
    {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        $enabled = get_post_meta($post_id, '_mla_enabled', true);
        $project_id = get_post_meta($post_id, '_mla_project_id', true);

        if ('1' !== $enabled) {
            return;
        }

        // Renderiza o formulário em um container isolado
        echo '<div class="mla-form-container-wrapper">';
        echo $this->form_renderer->render($project_id);
        echo '</div>';
    }

    /**
     * Registra as rotas REST API.
     *
     * @return void
     */
    public function register_rest_routes()
    {
        register_rest_route('mla/v1', '/responses', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_response'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('mla/v1', '/responses/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_response'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('mla/v1', '/responses/(?P<id>[a-zA-Z0-9-]+)/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_response'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('mla/v1', '/responses/by-text/(?P<text_id>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_response_by_text'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    /**
     * Verifica permissão para endpoints REST.
     *
     * @return bool
     */
    public function check_permission()
    {
        return is_user_logged_in();
    }

    /**
     * Salva uma resposta (rascunho).
     *
     * @param WP_REST_Request $request Requisição.
     *
     * @return WP_REST_Response
     */
    public function save_response($request)
    {
        $data = array(
            'text_id' => sanitize_text_field($request->get_param('text_id')),
            'project_id' => sanitize_text_field($request->get_param('project_id')),
            'wp_user_id' => get_current_user_id(),
            'wp_user_email' => wp_get_current_user()->user_email,
            'data' => $request->get_param('data'),
        );

        $result = $this->responses_service->save_draft($data);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'response' => $result,
        ), 200);
    }

    /**
     * Obtém uma resposta.
     *
     * @param WP_REST_Request $request Requisição.
     *
     * @return WP_REST_Response
     */
    public function get_response($request)
    {
        $id = sanitize_text_field($request->get_param('id'));
        $result = $this->responses_service->get_by_id($id);

        if (is_wp_error($result) || !$result) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Resposta não encontrada.', 'metodologia-leitor-apreciador'),
            ), 404);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'response' => $result,
        ), 200);
    }

    /**
     * Obtém a resposta do usuário para um texto.
     *
     * @param WP_REST_Request $request Requisição.
     *
     * @return WP_REST_Response
     */
    public function get_user_response_by_text($request)
    {
        $text_id = sanitize_text_field($request->get_param('text_id'));
        $user_id = get_current_user_id();

        $result = $this->responses_service->get_user_response($user_id, $text_id);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'response' => $result,
            'exists' => !empty($result),
        ), 200);
    }

    /**
     * Submete uma resposta.
     *
     * @param WP_REST_Request $request Requisição.
     *
     * @return WP_REST_Response
     */
    public function submit_response($request)
    {
        $id = sanitize_text_field($request->get_param('id'));
        $result = $this->responses_service->submit($id);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ), 400);
        }

        // Integração com LearnDash
        $learndash_result = $this->mark_learndash_complete($result);

        return new WP_REST_Response(array(
            'success' => true,
            'response' => $result,
            'learndash_completed' => $learndash_result['completed'],
            'debug_ld' => $learndash_result['debug'],
        ), 200);
    }

    /**
     * Marca uma lição/tópico do LearnDash como concluído.
     *
     * @param array $response_data Dados da resposta submetida.
     *
     * @return array Array com 'completed' (bool) e 'debug' (array).
     */
    private function mark_learndash_complete($response_data)
    {
        $debug = array(
            'triggered' => false,
            'text_id' => null,
            'wp_post_id' => null,
            'post_type' => null,
            'course_id' => null,
            'user_id' => null,
        );

        // Verifica se o LearnDash está disponível
        if (!function_exists('learndash_process_mark_complete')) {
            $debug['error'] = 'LearnDash not available';
            return array('completed' => false, 'debug' => $debug);
        }

        // Verifica se temos o text_id
        if (empty($response_data['text_id'])) {
            $debug['error'] = 'text_id is empty';
            return array('completed' => false, 'debug' => $debug);
        }

        $debug['text_id'] = $response_data['text_id'];

        // Obtém o user_id
        $user_id = $this->get_user_id_for_completion($response_data);
        if (!$user_id) {
            $debug['error'] = 'Could not determine user_id';
            return array('completed' => false, 'debug' => $debug);
        }
        $debug['user_id'] = $user_id;

        // Busca o registro do texto para obter o wp_post_id
        $post_data = $this->get_learndash_post_data($response_data['text_id']);
        if (isset($post_data['error'])) {
            $debug['error'] = $post_data['error'];
            return array('completed' => false, 'debug' => $debug);
        }

        $debug['wp_post_id'] = $post_data['post_id'];
        $debug['post_type'] = $post_data['post_type'];
        $debug['course_id'] = $post_data['course_id'];
        $debug['triggered'] = true;

        // Marca como concluído no LearnDash
        $completed = $this->execute_learndash_completion(
            $user_id,
            $post_data['post_id'],
            $post_data['course_id'],
            $post_data['post_type']
        );

        $debug['primary_result'] = $completed;

        return array('completed' => $completed, 'debug' => $debug);
    }

    /**
     * Obtém o user_id para marcar a conclusão.
     *
     * @param array $response_data Dados da resposta.
     *
     * @return int|null User ID ou null se não encontrado.
     */
    private function get_user_id_for_completion($response_data)
    {
        $user_id = get_current_user_id();

        // Fallback para wp_user_id da resposta
        if (!$user_id && !empty($response_data['wp_user_id'])) {
            $user_id = intval($response_data['wp_user_id']);
        }

        return $user_id ?: null;
    }

    /**
     * Obtém os dados do post LearnDash a partir do text_id.
     *
     * @param string $text_id UUID do texto.
     *
     * @return array Dados do post ou array com 'error'.
     */
    private function get_learndash_post_data($text_id)
    {
        $texts_service = new MLA_Texts_Service();
        $text_record = $texts_service->get_by_id($text_id);

        if (is_wp_error($text_record)) {
            return array('error' => 'Failed to fetch text: ' . $text_record->get_error_message());
        }

        if (!$text_record || empty($text_record['wp_post_id'])) {
            return array('error' => 'Text record not found or missing wp_post_id');
        }

        $post_id = intval($text_record['wp_post_id']);
        $post_type = get_post_type($post_id);

        // Se for revisão, obtém o post original
        if ('revision' === $post_type) {
            $parent_id = wp_get_post_parent_id($post_id);
            if ($parent_id) {
                $post_id = $parent_id;
                $post_type = get_post_type($post_id);
            }
        }

        // Verifica se é um tipo de post do LearnDash
        $valid_types = array('sfwd-lessons', 'sfwd-topic');
        if (!in_array($post_type, $valid_types, true)) {
            return array('error' => 'Invalid post type: ' . $post_type);
        }

        // Obtém o course_id
        $course_id = $this->get_course_id($post_id);

        return array(
            'post_id' => $post_id,
            'post_type' => $post_type,
            'course_id' => $course_id,
        );
    }

    /**
     * Obtém o ID do curso associado a uma lição/tópico.
     *
     * @param int $post_id ID do post.
     *
     * @return int Course ID.
     */
    private function get_course_id($post_id)
    {
        if (function_exists('learndash_get_course_id')) {
            $course_id = learndash_get_course_id($post_id);
            if ($course_id) {
                return (int) $course_id;
            }
        }

        // Fallback para meta
        return (int) get_post_meta($post_id, 'course_id', true);
    }

    /**
     * Executa a marcação de conclusão no LearnDash.
     *
     * @param int    $user_id   ID do usuário.
     * @param int    $post_id   ID do post.
     * @param int    $course_id ID do curso.
     * @param string $post_type Tipo do post.
     *
     * @return bool True se concluído com sucesso.
     */
    private function execute_learndash_completion($user_id, $post_id, $course_id, $post_type)
    {
        // Tenta marcar como concluído
        // $onlycalculate = false: persiste a conclusão
        // $force = true: ignora pré-requisitos
        $completed = learndash_process_mark_complete($user_id, $post_id, false, $course_id, true);

        if ($completed) {
            return true;
        }

        // Fallback: tenta via activity API
        return $this->fallback_activity_completion($user_id, $post_id, $course_id, $post_type);
    }

    /**
     * Fallback para marcar conclusão via activity API.
     *
     * @param int    $user_id   ID do usuário.
     * @param int    $post_id   ID do post.
     * @param int    $course_id ID do curso.
     * @param string $post_type Tipo do post.
     *
     * @return bool True se registrado com sucesso.
     */
    private function fallback_activity_completion($user_id, $post_id, $course_id, $post_type)
    {
        if (!function_exists('learndash_update_user_activity')) {
            return false;
        }

        $activity_type = ('sfwd-topic' === $post_type) ? 'topic' : 'lesson';

        learndash_update_user_activity(array(
            'user_id' => $user_id,
            'post_id' => $post_id,
            'course_id' => $course_id,
            'activity_type' => $activity_type,
            'activity_status' => true,
            'activity_completed' => time(),
            'activity_updated' => time(),
        ));

        return true;
    }
}
