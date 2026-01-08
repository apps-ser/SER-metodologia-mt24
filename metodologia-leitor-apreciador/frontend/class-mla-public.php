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

        wp_localize_script('mla-public-form', 'mlaSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('mla/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'postId' => $post_id,
            'textId' => $text_id,
            'projectId' => get_post_meta($post_id, '_mla_project_id', true),
            'userId' => get_current_user_id(),
            'userEmail' => wp_get_current_user()->user_email,
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

        if ('1' !== $enabled) {
            return;
        }

        // Renderiza o formulário em um container isolado
        echo '<div class="mla-form-container-wrapper">';
        echo $this->form_renderer->render();
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

        return new WP_REST_Response(array(
            'success' => true,
            'response' => $result,
        ), 200);
    }
}
