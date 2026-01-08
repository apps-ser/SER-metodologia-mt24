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

        // Filtros
        $filter_project = isset($_GET['project_id']) ? sanitize_text_field(wp_unslash($_GET['project_id'])) : '';

        // Obter posts com metodologia ativa
        $args = array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => 50,
            'meta_query' => array(
                array(
                    'key' => '_mla_enabled',
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        );

        if (!empty($filter_project)) {
            $args['meta_query'][] = array(
                'key' => '_mla_project_id',
                'value' => $filter_project,
                'compare' => '=',
            );
        }

        $query = new WP_Query($args);
        $texts = $query->posts;

        // Obter projetos para filtro
        $projects = $this->projects_service->get_for_select();

        include MLA_PLUGIN_DIR . 'admin/partials/texts-list.php';
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
}
