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
                __('Metodologia Mateus 24', 'metodologia-leitor-apreciador'),
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
        $paragraph_questions_enabled = get_post_meta($post->ID, '_mla_paragraph_questions_enabled', true);

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
        $post_type_object = get_post_type_object(get_post_type($post_id));
        $capability = $post_type_object ? $post_type_object->cap->edit_post : 'edit_post';

        if (!current_user_can($capability, $post_id)) {
            return;
        }

        // Processar checkbox de ativação
        $enabled = isset($_POST['mla_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_mla_enabled', $enabled);

        // Processar checkbox de perguntas por parágrafo
        $paragraph_questions_enabled = isset($_POST['mla_paragraph_questions_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_mla_paragraph_questions_enabled', $paragraph_questions_enabled);

        // Se perguntas por parágrafo ativadas, extrair e salvar parágrafos
        if ('1' === $paragraph_questions_enabled) {
            $this->extract_and_save_paragraphs($post_id);
        } else {
            delete_post_meta($post_id, '_mla_extracted_paragraphs');
        }

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

    /**
     * Extrai e salva os parágrafos do conteúdo do post.
     *
     * @param int $post_id ID do post.
     *
     * @return void
     */
    private function extract_and_save_paragraphs($post_id)
    {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $content = $post->post_content;

        // Aplicar filtros de conteúdo para garantir que shortcodes e auto-paragraphs sejam processados
        // Mas com cuidado para não gerar loops ou overhead excessivo no save_post.
        // As vezes é melhor parsear o raw content ou uma versão levemente processada.
        // Vamos usar wpautop se o conteúdo não tiver parágrafos HTML explícitos, mas idealmente parseamos o HTML.

        // Regex simples para capturar conteúdo dentro de tags <p>
        // Nota: Isso é uma abordagem simplificada. Para HTML complexo, DOMDocument seria melhor,
        // mas DOMDocument pode ser chato com HTML inválido frequentemente encontrado em WP.

        // Verifica se tem tags p, se não tiver, aplica wpautop primeiro
        if (strpos($content, '<p') === false) {
            $content = wpautop($content);
        }

        // Usar DOMDocument para extração mais robusta
        $dom = new DOMDocument();

        // Suprimir erros de HTML mal formado
        libxml_use_internal_errors(true);

        // Carregar HTML com charset UTF-8 hack
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        $paragraphs = array();
        $nodes = $dom->getElementsByTagName('p');

        $count = 1;
        foreach ($nodes as $node) {
            $text = trim($node->textContent);
            // Ignorar parágrafos vazios ou muito curtos
            if (mb_strlen($text) > 10) {
                // Limitar tamanho do texto para visualização (opcional, mas bom pra não ficar gigante no JSON)
                // Mas queremos o texto todo para referência? Talvez truncar para display.
                // Vamos salvar o texto completo por enquanto, ou truncado em 200 chars pra "preview"

                $paragraphs[] = array(
                    'id' => 'p' . $count,
                    'content' => $text // Salvando texto completo para referência exata
                );
                $count++;
            }
        }

        // Salvar como JSON no post meta
        // Usamos update_post_meta com array, o WP serializa automaticamente, mas JSON explícito é mais portátil se precisarmos ler via REST/JS cru sem passar pelo WP REST API fields formatting as vezes.
        // Vamos salvar como array serializado do WP mesmo que é o padrão, ou JSON string?
        // JSON string é melhor para ser consumido diretamente pelo JS frontend se injetarmos via wp_localize_script
        update_post_meta($post_id, '_mla_extracted_paragraphs', wp_json_encode($paragraphs, JSON_UNESCAPED_UNICODE));
    }
}
