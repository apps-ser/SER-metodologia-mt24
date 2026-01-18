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

        // Integração com LearnDash: Marcar como concluído se estiver em uma lição/tópico
        $ld_completed = false;
        if (function_exists('learndash_process_mark_complete') && !empty($result['text_id'])) {
            $texts_service = new MLA_Texts_Service();
            $text_record = $texts_service->get_by_id($result['text_id']);

            if ($text_record && !empty($text_record['wp_post_id'])) {
                $post_id = intval($text_record['wp_post_id']);
                $post_type = get_post_type($post_id);

                if (in_array($post_type, array('sfwd-lessons', 'sfwd-topic'), true)) {
                    $user_id = get_current_user_id();
                    $course_id = function_exists('learndash_get_course_id') ? learndash_get_course_id($post_id) : 0;

                    // Tenta marcar como concluído no LearnDash
                    $ld_completed = learndash_process_mark_complete($user_id, $post_id, false, $course_id);

                    // Fallback: se a função principal retornar false, tenta forçar via atividade do usuário
                    // Isso é útil se houver restrições de LearnDash que impedem a conclusão normal mas queremos forçar pela metodologia.
                    if (!$ld_completed && function_exists('learndash_update_user_activity')) {
                        learndash_update_user_activity(array(
                            'user_id' => $user_id,
                            'post_id' => $post_id,
                            'course_id' => $course_id,
                            'activity_type' => 'lesson' === $post_type ? 'lesson' : ('topic' === $post_type ? 'topic' : $post_type),
                            'activity_status' => true,
                            'activity_completed' => time(),
                        ));
                        $ld_completed = true; // Forçamos o feedback positivo para o usuário
                    }

                    // Se falhar (ex: por causa de pré-requisitos), tenta forçar via meta
                    if (!$ld_completed && function_exists('learndash_get_course_id')) {
                        // Às vezes o learndash_process_mark_complete é rigoroso demais
                        // No entanto, vamos logar se possível ou apenas retornar o status
                    }
                }
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'response' => $result,
            'learndash_completed' => $ld_completed,
        ), 200);
    }
}
