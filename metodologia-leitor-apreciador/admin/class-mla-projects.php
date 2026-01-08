<?php
/**
 * Classe responsável pelo CRUD de projetos no admin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Projects
 *
 * Gerencia a interface administrativa de projetos.
 */
class MLA_Projects
{

    /**
     * Serviço de projetos.
     *
     * @var MLA_Projects_Service
     */
    private $service;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->service = new MLA_Projects_Service();
    }

    /**
     * Renderiza a página de projetos.
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
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : 'list';

        switch ($action) {
            case 'new':
            case 'edit':
                $this->render_form();
                break;
            default:
                $this->render_list();
                break;
        }
    }

    /**
     * Processa ações de formulário.
     *
     * @return void
     */
    private function process_actions()
    {
        // Verificar se há dados de formulário
        if (!isset($_POST['mla_project_nonce'])) {
            return;
        }

        // Verificar nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mla_project_nonce'])), 'mla_project_action')) {
            wp_die(esc_html__('Verificação de segurança falhou.', 'metodologia-leitor-apreciador'));
        }

        $action = isset($_POST['mla_action']) ? sanitize_text_field(wp_unslash($_POST['mla_action'])) : '';

        switch ($action) {
            case 'create':
                $this->handle_create();
                break;
            case 'update':
                $this->handle_update();
                break;
            case 'delete':
                $this->handle_delete();
                break;
        }
    }

    /**
     * Processa criação de projeto.
     *
     * @return void
     */
    private function handle_create()
    {
        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active',
        );

        $result = $this->service->create($data);

        if (is_wp_error($result)) {
            add_settings_error(
                'mla_projects',
                'create_error',
                $result->get_error_message(),
                'error'
            );
        } else {
            // Salvar Template ID (Mapeamento WP)
            if (isset($_POST['template_id']) && isset($result['id'])) {
                $project_templates = get_option('mla_project_templates', array());
                $project_templates[$result['id']] = sanitize_key($_POST['template_id']);
                update_option('mla_project_templates', $project_templates);
            }

            // Redirecionar para lista com mensagem de sucesso
            wp_safe_redirect(add_query_arg(array(
                'page' => 'mla-projects',
                'message' => 'created',
            ), admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Processa atualização de projeto.
     *
     * @return void
     */
    private function handle_update()
    {
        $id = isset($_POST['project_id']) ? sanitize_text_field(wp_unslash($_POST['project_id'])) : '';

        if (empty($id)) {
            add_settings_error('mla_projects', 'missing_id', __('ID do projeto inválido.', 'metodologia-leitor-apreciador'), 'error');
            return;
        }

        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active',
        );

        $result = $this->service->update($id, $data);

        if (is_wp_error($result)) {
            add_settings_error('mla_projects', 'update_error', $result->get_error_message(), 'error');
        } else {
            // Atualizar Template ID (Mapeamento WP)
            if (isset($_POST['template_id'])) {
                $project_templates = get_option('mla_project_templates', array());
                $project_templates[$id] = sanitize_key($_POST['template_id']);
                update_option('mla_project_templates', $project_templates);
            }

            wp_safe_redirect(add_query_arg(array(
                'page' => 'mla-projects',
                'message' => 'updated',
            ), admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Processa exclusão de projeto.
     *
     * @return void
     */
    private function handle_delete()
    {
        $id = isset($_POST['project_id']) ? sanitize_text_field(wp_unslash($_POST['project_id'])) : '';

        if (empty($id)) {
            return;
        }

        $result = $this->service->delete($id);

        if (is_wp_error($result)) {
            add_settings_error('mla_projects', 'delete_error', $result->get_error_message(), 'error');
        } else {
            wp_safe_redirect(add_query_arg(array(
                'page' => 'mla-projects',
                'message' => 'deleted',
            ), admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Renderiza a lista de projetos.
     *
     * @return void
     */
    private function render_list()
    {
        // Exibir mensagens
        $message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';

        // Obter projetos
        $projects = $this->service->get_all();

        include MLA_PLUGIN_DIR . 'admin/partials/projects-list.php';
    }

    /**
     * Renderiza o formulário de criação/edição.
     *
     * @return void
     */
    private function render_form()
    {
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : 'new';
        $project_id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        $project = null;

        if ('edit' === $action && !empty($project_id)) {
            $project = $this->service->get_by_id($project_id);

            if (is_wp_error($project) || !$project) {
                wp_die(esc_html__('Projeto não encontrado.', 'metodologia-leitor-apreciador'));
            }
        }

        include MLA_PLUGIN_DIR . 'admin/partials/projects-form.php';
    }
}
