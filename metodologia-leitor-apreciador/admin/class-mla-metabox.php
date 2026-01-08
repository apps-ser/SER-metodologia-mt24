<?php
/**
 * Classe responsável pela metabox no editor de posts/páginas.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Metabox
 *
 * Adiciona e gerencia a metabox clássica para ativação da metodologia.
 * Compatível com Editor Clássico e Editor de Blocos.
 */
class MLA_Metabox
{

    /**
     * Serviço de projetos.
     *
     * @var MLA_Projects_Service
     */
    private $projects_service;

    /**
     * Serviço de textos.
     *
     * @var MLA_Texts_Service
     */
    private $texts_service;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->projects_service = new MLA_Projects_Service();
        $this->texts_service = new MLA_Texts_Service();
    }

    /**
     * Registra as metaboxes.
     *
     * @return void
     */
    public function add_meta_boxes()
    {
        $settings = get_option('mla_settings', array());
        $allowed_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');

        foreach ($allowed_types as $post_type) {
            add_meta_box(
                'mla_metodologia_settings',
                __('Metodologia do Leitor-Apreciador', 'metodologia-leitor-apreciador'),
                array($this, 'render_metabox'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Renderiza o conteúdo da metabox.
     *
     * @param WP_Post $post Objeto do post atual.
     *
     * @return void
     */
    public function render_metabox($post)
    {
        // Nonce para segurança
        wp_nonce_field('mla_metabox_nonce_action', 'mla_metabox_nonce');

        // Obter valores salvos
        $enabled = get_post_meta($post->ID, '_mla_enabled', true);
        $project_id = get_post_meta($post->ID, '_mla_project_id', true);
        $text_id = get_post_meta($post->ID, '_mla_text_id', true);

        // Obter lista de projetos
        $projects = $this->projects_service->get_for_select();

        include MLA_PLUGIN_DIR . 'admin/partials/metabox-content.php';
    }

    /**
     * Salva os dados da metabox.
     *
     * @param int $post_id ID do post.
     *
     * @return void
     */
    public function save_meta_box_data($post_id)
    {
        // Verificar nonce
        if (!isset($_POST['mla_metabox_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mla_metabox_nonce'])), 'mla_metabox_nonce_action')) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permissões
        if (isset($_POST['post_type']) && 'page' === $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Processar checkbox de ativação
        $enabled = isset($_POST['mla_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_mla_enabled', $enabled);

        // Processar projeto vinculado
        $project_id = isset($_POST['mla_project_id'])
            ? sanitize_text_field(wp_unslash($_POST['mla_project_id']))
            : '';
        update_post_meta($post_id, '_mla_project_id', $project_id);

        // Sincronizar com Supabase se ativado
        if ('1' === $enabled) {
            $this->sync_text_with_supabase($post_id, $project_id);
        }
    }

    /**
     * Sincroniza o texto com o Supabase.
     *
     * @param int    $post_id    ID do post.
     * @param string $project_id UUID do projeto.
     *
     * @return void
     */
    private function sync_text_with_supabase($post_id, $project_id)
    {
        $post = get_post($post_id);
        $title = $post ? $post->post_title : '';

        $result = $this->texts_service->sync($post_id, $title, $project_id);

        if (!is_wp_error($result) && isset($result['id'])) {
            update_post_meta($post_id, '_mla_text_id', $result['id']);
        } elseif (is_wp_error($result)) {
            // Log de erro
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MLA Sync Error: ' . $result->get_error_message());
            }
        }
    }
}
