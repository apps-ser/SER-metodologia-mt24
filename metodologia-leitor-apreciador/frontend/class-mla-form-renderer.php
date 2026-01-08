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
                'key' => 'step_1',
                'title' => __('Compreensão Geral', 'metodologia-leitor-apreciador'),
                'description' => __('Identifique o tema central e os temas secundários abordados no texto.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'tema_central',
                        'label' => __('Tema Central', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Qual é a mensagem principal do texto?', 'metodologia-leitor-apreciador'),
                    ),
                    array(
                        'name' => 'temas_secundarios',
                        'label' => __('Temas Secundários', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Quais outros temas são abordados?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            2 => array(
                'key' => 'step_2',
                'title' => __('Conexões Doutrinárias', 'metodologia-leitor-apreciador'),
                'description' => __('Relacione o conteúdo com outros textos e doutrinas.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'correlacao',
                        'label' => __('Correlação', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Que conexões você percebe com outros textos?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            3 => array(
                'key' => 'step_3',
                'title' => __('Avaliação do Texto', 'metodologia-leitor-apreciador'),
                'description' => __('Quais aspectos positivos você identifica?', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'aspectos_positivos',
                        'label' => __('Aspectos Positivos', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('O que mais chamou sua atenção positivamente?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            4 => array(
                'key' => 'step_4',
                'title' => __('Investigação Crítica', 'metodologia-leitor-apreciador'),
                'description' => __('Registre suas dúvidas sobre o texto.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'duvidas',
                        'label' => __('Dúvidas Identificadas', 'metodologia-leitor-apreciador'),
                        'placeholder' => __('Quais pontos geraram questionamentos?', 'metodologia-leitor-apreciador'),
                    ),
                ),
            ),
            5 => array(
                'key' => 'step_5',
                'title' => __('Formulação Consciente', 'metodologia-leitor-apreciador'),
                'description' => __('Formule perguntas claras a partir das dúvidas.', 'metodologia-leitor-apreciador'),
                'fields' => array(
                    array(
                        'name' => 'perguntas',
                        'label' => __('Perguntas Formuladas', 'metodologia-leitor-apreciador'),
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
     * Verifica se o formulário progressivo está ativado.
     *
     * @return bool
     */
    public function is_progressive()
    {
        return isset($this->settings['progressive_form']) ? (bool) $this->settings['progressive_form'] : true;
    }
}
