<?php
/**
 * Classe responsável pela renderização do formulário.
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Form_Renderer
 *
 * Renderiza o formulário progressivo da metodologia.
 */
class MLA_Form_Renderer
{

    /**
     * Configurações do plugin.
     *
     * @var array
     */
    private $settings;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->settings = get_option('mla_settings', array());
    }

    /**
     * Renderiza o formulário completo.
     *
     * @param int|null $project_id ID do projeto para carregar o modelo correto.
     * @return string HTML do formulário.
     */
    public function render($project_id = null)
    {
        // Verificar se usuário está logado
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $steps = $this->get_steps($project_id);

        // Contexto de parágrafos
        global $post;
        $paragraph_context = $this->get_paragraph_context($post ? $post->ID : 0);

        if ($paragraph_context['enabled'] && $paragraph_context['step']) {
            $steps[] = $paragraph_context['step'];
        }

        // Variáveis para a view
        $paragraph_questions_enabled = $paragraph_context['enabled'];
        $paragraphs_json = $paragraph_context['json'];

        ob_start();
        include MLA_PLUGIN_DIR . 'frontend/partials/form-container.php';
        return ob_get_clean();
    }

    /**
     * Renderiza mensagem para usuário não logado.
     *
     * @return string HTML da mensagem.
     */
    private function render_login_required()
    {
        ob_start();
        include MLA_PLUGIN_DIR . 'frontend/partials/login-required.php';
        return ob_get_clean();
    }

    /**
     * Obtém as etapas do formulário.
     *
     * @return array Etapas configuradas.
     */
    public function get_steps($project_id = null)
    {
        $settings = $this->settings;
        $templates = isset($settings['step_templates']) ? $settings['step_templates'] : array();

        // Reparo: Tentar consertar caracteres unicode corrompidos (ex: u00fa -> ú) vindos de saves anteriores problemáticos
        $templates = $this->repair_unicode_recursive($templates);

        // 1. Tentar obter o template do projeto
        $project_template_id = '';
        if ($project_id) {
            $project_templates = get_option('mla_project_templates', array());
            $project_template_id = isset($project_templates[$project_id]) ? $project_templates[$project_id] : '';
        }

        // 2. Procurar o template
        $active_template = null;
        if ($project_template_id) {
            foreach ($templates as $tpl) {
                if ($tpl['id'] === $project_template_id) {
                    $active_template = $tpl;
                    break;
                }
            }
        }

        // 3. Se achou template, retornar os steps dele
        if ($active_template && !empty($active_template['steps'])) {
            $formatted_steps = array();
            foreach ($active_template['steps'] as $index => $step) {
                $formatted_steps[$index + 1] = array(
                    'key' => $step['key'],
                    'title' => $step['title'],
                    'description' => $step['description'],
                    'fields' => array(
                        array(
                            'name' => $step['key'], // Usa a chave da etapa como nome do campo único por enquanto
                            'label' => $step['title'],
                            'placeholder' => __('Sua resposta...', 'metodologia-leitor-apreciador'),
                        )
                    )
                );
            }
            return $formatted_steps;
        }

        // 4. Fallback: Comportamento Legado (Steps Fixos)
        $step_texts = isset($this->settings['step_texts']) ? $this->settings['step_texts'] : array();

        $default_steps = array(
            1 => array(
                'key' => 'tema_central',
                'title' => __('Tema Central', 'metodologia-leitor-apreciador'),
                'description' => __('Identifique e descreva de forma concisa a ideia principal que o texto busca transmitir.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'tema_central',
                        'label' => __('Tema Central', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Qual a ideia principal do texto?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            2 => array(
                'key' => 'temas_subsidiarios',
                'title' => __('Temas Subsidiários', 'metodologia-leitor-apreciador'),
                'description' => __('Aponte outros assuntos ou ideias secundárias que apoiam e complementam o tema central.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'temas_subsidiarios',
                        'label' => __('Temas Subsidiários', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Quais outros temas são abordados?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            3 => array(
                'key' => 'correlacoes_doutrinarias',
                'title' => __('Correlações Doutrinárias', 'metodologia-leitor-apreciador'),
                'description' => __('Relacione este conteúdo com outros textos, obras ou passagens evangélicas de seu conhecimento.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'correlacoes_doutrinarias',
                        'label' => __('Correlação', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Que conexões você percebe com outros textos?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            4 => array(
                'key' => 'aspectos_positivos',
                'title' => __('Aspectos Positivos', 'metodologia-leitor-apreciador'),
                'description' => __('Quais ensinamentos e pontos fortes você destaca neste texto? O que foi mais valioso para você?', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'aspectos_positivos',
                        'label' => __('Aspectos Positivos', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('O que mais te impactou?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            5 => array(
                'key' => 'duvidas',
                'title' => __('Dúvidas', 'metodologia-leitor-apreciador'),
                'description' => __('Exponha os pontos que não ficaram totalmente claros ou que geraram algum tipo de estranhamento ou dúvida.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'duvidas',
                        'label' => __('Dúvidas', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Quais pontos não ficaram claros?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            6 => array(
                'key' => 'perguntas_autores',
                'title' => __('Perguntas para os Autores', 'metodologia-leitor-apreciador'),
                'description' => __('A partir de suas dúvidas, formule perguntas profundas baseadas fielmente no texto. ATENÇÃO: Perguntas fora de contexto ou alheias ao texto serão excluídas da análise.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'perguntas_autores',
                        'label' => __('Perguntas para os Autores', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Que perguntas você faria aos autores espirituais?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
        );

        // Sobrescrever com textos personalizados (método antigo)
        foreach ($default_steps as $num => &$step) {
            $key = 'step_' . $num;
            if (isset($step_texts[$key])) {
                if (!empty($step_texts[$key]['title'])) {
                    $step['title'] = $step_texts[$key]['title'];
                }
                if (!empty($step_texts[$key]['description'])) {
                    $step['description'] = $step_texts[$key]['description'];
                }
            }
        }

        return $default_steps;
    }

    /**
     * Repara recursivamente strings que tiveram as contra-barras de escape unicode removidas.
     */
    private function repair_unicode_recursive($data)
    {
        if (is_string($data)) {
            return preg_replace_callback('/(?<!\\\\)u([0-9a-f]{4})/i', function ($matches) {
                return json_decode('"\u' . $matches[1] . '"');
            }, $data);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->repair_unicode_recursive($value);
            }
        }

        return $data;
    }

    /**
     * Verifica se o formulário progressivo está ativado.
     *
     * @return bool
     */
    public function is_progressive()
    {
        return isset($this->settings['progressive_form']) ? (bool) $this->settings['progressive_form'] : true;
    }

    /**
     * Obtém o contexto e dados para o passo de perguntas por parágrafo.
     *
     * @param int $post_id ID do post.
     * @return array Dados estruturados { enabled, json, step }.
     */
    private function get_paragraph_context($post_id)
    {
        if (!$post_id) {
            return array('enabled' => false, 'json' => '[]', 'step' => null);
        }

        $enabled = get_post_meta($post_id, '_mla_paragraph_questions_enabled', true) === '1';
        $json = '[]';
        $step = null;

        if ($enabled) {
            $step = array(
                'key' => 'perguntas_paragrafos',
                'title' => __('Perguntas por Parágrafo', 'metodologia-leitor-apreciador'),
                'description' => __('Algum conceito ou argumento despertou questionamentos? Se sim, faça suas perguntas abaixo.', 'metodologia-leitor-apreciador'),
                'fields' => array(),
                'is_conditional' => true
            );

            $raw_json = get_post_meta($post_id, '_mla_extracted_paragraphs', true);

            if ($raw_json) {
                $data = json_decode($raw_json, true);
                if (is_array($data)) {
                    $data = $this->repair_unicode_recursive($data);
                    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        return array(
            'enabled' => $enabled,
            'json' => $json, // Retorna '[]' por padrão se vazio
            'step' => $step
        );
    }
}
