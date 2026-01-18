<?php
/**
 * Classe responsável pelas configurações do plugin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Settings
 *
 * Gerencia a página de configurações do plugin.
 */
class MLA_Settings
{

    /**
     * Chave da opção no banco de dados.
     */
    const OPTION_KEY = 'mla_settings';

    /**
     * Construtor.
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enfileira scripts do admin para o editor de templates.
     */
    public function enqueue_admin_scripts($hook)
    {
        if ('leitor-apreciador_page_mla-settings' !== $hook) {
            return;
        }

        wp_enqueue_script('mla-settings-js', MLA_PLUGIN_URL . 'assets/js/mla-settings.js', array('jquery', 'jquery-ui-sortable'), MLA_VERSION, true);
        wp_localize_script('mla-settings-js', 'mlaSettingsData', array(
            'i18n' => array(
                'addStep' => __('Adicionar Etapa', 'metodologia-leitor-apreciador'),
                'removeStep' => __('Remover', 'metodologia-leitor-apreciador'),
                'stepTitle' => __('Título da Etapa', 'metodologia-leitor-apreciador'),
                'stepDesc' => __('Descrição', 'metodologia-leitor-apreciador'),
                'stepKey' => __('Chave (ID único)', 'metodologia-leitor-apreciador'),
                'confirmRemove' => __('Tem certeza que deseja remover?', 'metodologia-leitor-apreciador'),
            )
        ));
    }

    /**
     * Registra as configurações.
     *
     * @return void
     */
    public function register_settings()
    {
        register_setting(
            'mla_settings_group',
            self::OPTION_KEY,
            array($this, 'sanitize_settings')
        );

        // Seção: Geral
        add_settings_section(
            'mla_general_section',
            __('Configurações Gerais', 'metodologia-leitor-apreciador'),
            array($this, 'render_general_section'),
            'mla-settings'
        );

        add_settings_field(
            'autosave_interval',
            __('Intervalo de Auto-save (segundos)', 'metodologia-leitor-apreciador'),
            array($this, 'render_autosave_field'),
            'mla-settings',
            'mla_general_section'
        );

        add_settings_field(
            'progressive_form',
            __('Formulário Progressivo', 'metodologia-leitor-apreciador'),
            array($this, 'render_progressive_field'),
            'mla-settings',
            'mla_general_section'
        );

        add_settings_field(
            'allowed_post_types',
            __('Tipos de Post Permitidos', 'metodologia-leitor-apreciador'),
            array($this, 'render_allowed_post_types_field'),
            'mla-settings',
            'mla_general_section'
        );

        add_settings_field(
            'submission_required',
            __('Submissão Obrigatória', 'metodologia-leitor-apreciador'),
            array($this, 'render_submission_field'),
            'mla-settings',
            'mla_general_section'
        );

        // Seção: Templates de Etapas
        add_settings_section(
            'mla_templates_section',
            __('Modelos de Etapas (Templates)', 'metodologia-leitor-apreciador'),
            array($this, 'render_templates_section'),
            'mla-settings'
        );

        add_settings_field(
            'step_templates',
            __('Gerenciar Templates', 'metodologia-leitor-apreciador'),
            array($this, 'render_templates_editor'),
            'mla-settings',
            'mla_templates_section'
        );

        // Seção: Supabase
        add_settings_section(
            'mla_supabase_section',
            __('Integração Supabase', 'metodologia-leitor-apreciador'),
            array($this, 'render_supabase_section'),
            'mla-settings'
        );

        add_settings_field(
            'supabase_url',
            __('URL do Projeto', 'metodologia-leitor-apreciador'),
            array($this, 'render_supabase_url_field'),
            'mla-settings',
            'mla_supabase_section'
        );

        add_settings_field(
            'supabase_anon_key',
            __('Anon Public Key', 'metodologia-leitor-apreciador'),
            array($this, 'render_supabase_anon_key_field'),
            'mla-settings',
            'mla_supabase_section'
        );

        add_settings_field(
            'supabase_service_key',
            __('Service Role Key', 'metodologia-leitor-apreciador'),
            array($this, 'render_supabase_service_key_field'),
            'mla-settings',
            'mla_supabase_section'
        );

        // Seção: Inteligência Artificial (OpenRouter)
        add_settings_section(
            'mla_ai_section',
            __('Inteligência Artificial (OpenRouter.ai)', 'metodologia-leitor-apreciador'),
            array($this, 'render_ai_section'),
            'mla-settings'
        );

        add_settings_field(
            'openrouter_api_key',
            __('Chave da API OpenRouter', 'metodologia-leitor-apreciador'),
            array($this, 'render_openrouter_api_key_field'),
            'mla-settings',
            'mla_ai_section'
        );

        add_settings_field(
            'openrouter_model',
            __('Modelo de IA', 'metodologia-leitor-apreciador'),
            array($this, 'render_openrouter_model_field'),
            'mla-settings',
            'mla_ai_section'
        );

        add_settings_field(
            'ai_system_prompt',
            __('Prompt de Processamento (Sistema)', 'metodologia-leitor-apreciador'),
            array($this, 'render_ai_system_prompt_field'),
            'mla-settings',
            'mla_ai_section'
        );

        add_settings_field(
            'methodology_explanation',
            __('Explicação da Metodologia', 'metodologia-leitor-apreciador'),
            array($this, 'render_methodology_explanation_field'),
            'mla-settings',
            'mla_ai_section'
        );
    }

    /**
     * Renderiza a página de configurações.
     */
    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'metodologia-leitor-apreciador'));
        }
        include MLA_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    public function render_general_section()
    {
        echo '<p>' . esc_html__('Configure o comportamento geral do formulário.', 'metodologia-leitor-apreciador') . '</p>';
    }

    public function render_templates_section()
    {
        echo '<p>' . esc_html__('Crie modelos de etapas personalizados para usar em seus projetos.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza o editor de templates (Interface Rica).
     */
    public function render_templates_editor()
    {
        $settings = get_option(self::OPTION_KEY, array());

        // Migração: Se não houver templates, criar um Default com os dados antigos
        $templates = isset($settings['step_templates']) ? $settings['step_templates'] : array();
        if (empty($templates) && isset($settings['step_texts'])) {
            $default_steps = array();
            $keys = array('tema_central', 'temas_secundarios', 'correlacao', 'aspectos_positivos', 'duvidas', 'perguntas'); // Chaves legadas esperadas, mas o antigo era step_1..5

            // Mapear steps antigos
            $i = 0;
            foreach ($settings['step_texts'] as $key => $val) {
                // Tenta adivinhar a chave legada baseada na ordem, ou gera uma nova
                $stepKey = isset($keys[$i]) ? $keys[$i] : 'step_' . ($i + 1);
                $default_steps[] = array(
                    'key' => $stepKey,
                    'title' => $val['title'],
                    'description' => $val['description']
                );
                $i++;
            }

            if (!empty($default_steps)) {
                $templates[] = array(
                    'id' => 'default',
                    'name' => 'Modelo Padrão (Migrado)',
                    'steps' => $default_steps
                );
            }
        }

        // Reparo: Tentar consertar caracteres unicode corrompidos (ex: u00fa -> ú) vindos de saves anteriores problemáticos
        $templates = $this->repair_unicode_recursive($templates);

        // Se ainda estiver vazio, cria um básico fundamentado na metodologia
        if (empty($templates)) {
            $templates[] = array(
                'id' => 'tpl_default',
                'name' => 'Metodologia Mateus 24 (Padrão)',
                'steps' => array(
                    array(
                        'key' => 'tema_central',
                        'title' => 'Tema Central',
                        'description' => 'Identifique e descreva de forma concisa a ideia principal que o texto busca transmitir.',
                        'fields' => array(
                            array(
                                'name' => 'tema_central',
                                'label' => __('Tema Central', 'metodologia-leitor-apreciador'),
                                'placeholder' => __('Qual a ideia principal do texto?', 'metodologia-leitor-apreciador'),
                            ),
                        ),
                    ),
                    array(
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
                    array(
                        'key' => 'correlacoes_doutrinarias',
                        'title' => __('Correlações Doutrinárias', 'metodologia-leitor-apreciador'),
                        'description' => __('Relacione este conteúdo com outros textos e doutrinas.', 'metodologia-leitor-apreciador'),
                        'fields' => array(
                            array(
                                'name' => 'correlacoes_doutrinarias',
                                'label' => __('Correlação', 'metodologia-leitor-apreciador'),
                                'placeholder' => __('Que conexões você percebe com outros textos?', 'metodologia-leitor-apreciador'),
                            ),
                        ),
                    ),
                    array(
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
                    array(
                        'key' => 'duvidas',
                        'title' => 'Dúvidas',
                        'description' => 'Exponha os pontos que não ficaram totalmente claros ou que geraram algum tipo de estranhamento ou dúvida.',
                        'fields' => array(
                            array(
                                'name' => 'duvidas',
                                'label' => __('Dúvidas', 'metodologia-leitor-apreciador'),
                                'placeholder' => __('Quais pontos não ficaram claros?', 'metodologia-leitor-apreciador'),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'perguntas_autores',
                        'title' => 'Perguntas para os Autores',
                        'description' => 'A partir de suas dúvidas, formule perguntas profundas baseadas fielmente no texto. ATENÇÃO: Perguntas fora de contexto ou alheias ao texto serão excluídas da análise.'
                    ),
                )
            );
        }

        // Field oculto que armazenará o JSON
        printf(
            '<textarea id="mla_step_templates_json" name="%s[step_templates]" style="display:none;">%s</textarea>',
            esc_attr(self::OPTION_KEY),
            esc_textarea(json_encode($templates, JSON_UNESCAPED_UNICODE))
        );

        // Container para o React/JS App
        echo '<div id="mla-templates-editor"></div>';
        echo '<p class="description">' . __('Gerencie seus templates acima. Clique em "Salvar Alterações" no final da página para persistir.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Repara recursivamente strings que tiveram as contra-barras de escape unicode removidas.
     */
    private function repair_unicode_recursive($data)
    {
        if (is_string($data)) {
            return preg_replace_callback('/u([0-9a-f]{4})/i', function ($matches) {
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

    public function render_supabase_section()
    {
        echo '<p>' . esc_html__('Configure as credenciais do seu projeto Supabase.', 'metodologia-leitor-apreciador') . '</p>';
        if (defined('MLA_SUPABASE_URL') && defined('MLA_SUPABASE_ANON_KEY')) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('Constantes definidas no wp-config.php têm prioridade.', 'metodologia-leitor-apreciador') . '</p></div>';
        }
    }

    public function render_supabase_url_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['supabase_url']) ? $settings['supabase_url'] : '';
        printf('<input type="url" name="%s[supabase_url]" value="%s" class="regular-text code">', esc_attr(self::OPTION_KEY), esc_attr($value));
    }

    public function render_supabase_anon_key_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['supabase_anon_key']) ? $settings['supabase_anon_key'] : '';
        printf('<input type="password" name="%s[supabase_anon_key]" value="%s" class="regular-text code">', esc_attr(self::OPTION_KEY), esc_attr($value));
    }

    public function render_supabase_service_key_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['supabase_service_key']) ? $settings['supabase_service_key'] : '';
        printf('<input type="password" name="%s[supabase_service_key]" value="%s" class="regular-text code">', esc_attr(self::OPTION_KEY), esc_attr($value));
    }

    public function render_ai_section()
    {
        echo '<p>' . esc_html__('Configure a integração com o OpenRouter para realizar análises automáticas das respostas.', 'metodologia-leitor-apreciador') . '</p>';
    }

    public function render_openrouter_api_key_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['openrouter_api_key']) ? $settings['openrouter_api_key'] : '';
        printf('<input type="password" name="%s[openrouter_api_key]" value="%s" class="regular-text code">', esc_attr(self::OPTION_KEY), esc_attr($value));
        echo '<p class="description">' . __('Obtenha sua chave em <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai</a>.', 'metodologia-leitor-apreciador') . '</p>';
    }

    public function render_openrouter_model_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['openrouter_model']) ? $settings['openrouter_model'] : 'openai/gpt-4o-mini';

        $ai_service = new MLA_AI_Service();
        $models = $ai_service->get_available_models();

        if (is_wp_error($models)) {
            printf('<p class="error">%s</p>', esc_html($models->get_error_message()));
            // Fallback para modelos básicos se a API falhar
            $models = array(
                'openai/gpt-4o' => 'GPT-4o (OpenAI)',
                'openai/gpt-4o-mini' => 'GPT-4o Mini (OpenAI)',
                'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet (Anthropic)',
                'google/gemini-flash-1.5' => 'Gemini 1.5 Flash (Google)',
            );
        }

        printf('<select name="%s[openrouter_model]" class="regular-text">', esc_attr(self::OPTION_KEY));
        foreach ($models as $id => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($id), selected($value, $id, false), esc_html($label));
        }
        echo '</select>';
        echo '<p class="description">' . __('Os modelos são carregados dinamicamente do OpenRouter. Use o botão de salvar para atualizar a lista se necessário.', 'metodologia-leitor-apreciador') . '</p>';
    }

    public function render_ai_system_prompt_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $default_prompt = "Você é um analista especializado na Metodologia Mateus 24. 
Sua tarefa é analisar um JSON contendo respostas de vários leitores sobre um texto específico.

Objetivos:
1. Agrupar Perguntas: Identifique perguntas que expressam a mesma dúvida ou ideia. Crie uma pergunta única, clara e profunda para cada grupo, fundindo as nuances. Cite o nome de todos os leitores que contribuíram para aquele grupo de ideias.
2. Perguntas Únicas: Mantenha perguntas que não possuem similares.
3. Síntese do Conteúdo: Gere um texto que sintetize e resuma as impressões, sentimentos e observações dos leitores sobre o texto original. Preserve informações valiosas e observações específicas de cada leitor, citando-os quando as ideias forem mencionadas.

Formato de Saída (Markdown):
# Análise das Respostas (IA)

## Perguntas para os Autores
(Lista de perguntas agrupadas e únicas com os nomes dos leitores)

## Síntese das Percepções
(Texto consolidado identificando as contribuindo das contribuições dos leitores)";

        $value = isset($settings['ai_system_prompt']) ? $settings['ai_system_prompt'] : $default_prompt;
        printf('<textarea name="%s[ai_system_prompt]" rows="15" class="large-text code">%s</textarea>', esc_attr(self::OPTION_KEY), esc_textarea($value));
        echo '<p class="description">' . __('Este prompt define como a IA deve processar as respostas. Use como base para customizar o resultado.', 'metodologia-leitor-apreciador') . '</p>';
    }

    public function render_methodology_explanation_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['methodology_explanation']) ? $settings['methodology_explanation'] : '';

        wp_editor($value, 'mla_methodology_explanation', array(
            'textarea_name' => self::OPTION_KEY . '[methodology_explanation]',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'tinymce' => true,
            'quicktags' => true
        ));

        echo '<p class="description">' . __('Descreva a Metodologia Mateus 24. Este texto será enviado à IA como contexto para ajudar na análise.', 'metodologia-leitor-apreciador') . '</p>';
    }

    public function render_autosave_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['autosave_interval']) ? intval($settings['autosave_interval']) : 20;
        printf('<input type="number" name="%s[autosave_interval]" value="%d" min="10" max="120" class="small-text">', esc_attr(self::OPTION_KEY), esc_attr($value));
    }

    public function render_progressive_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $checked = isset($settings['progressive_form']) ? (bool) $settings['progressive_form'] : true;
        printf('<label><input type="checkbox" name="%s[progressive_form]" value="1" %s> %s</label>', esc_attr(self::OPTION_KEY), checked($checked, true, false), esc_html__('Exibir formulário em etapas sequenciais', 'metodologia-leitor-apreciador'));
    }

    public function render_allowed_post_types_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $allowed_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');
        $post_types = get_post_types(array('public' => true), 'objects');

        echo '<fieldset>';
        foreach ($post_types as $post_type) {
            if ('attachment' === $post_type->name)
                continue;
            printf(
                '<label style="display:block; margin-bottom: 5px;"><input type="checkbox" name="%s[allowed_post_types][]" value="%s" %s> %s (%s)</label>',
                esc_attr(self::OPTION_KEY),
                esc_attr($post_type->name),
                in_array($post_type->name, $allowed_types) ? 'checked="checked"' : '',
                esc_html($post_type->label),
                esc_html($post_type->name)
            );
        }
        echo '</fieldset>';
    }

    public function render_submission_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $checked = isset($settings['submission_required']) ? (bool) $settings['submission_required'] : false;
        printf('<label><input type="checkbox" name="%s[submission_required]" value="1" %s> %s</label>', esc_attr(self::OPTION_KEY), checked($checked, true, false), esc_html__('Exigir submissão explícita', 'metodologia-leitor-apreciador'));
    }

    public function sanitize_settings($input)
    {
        $sanitized = array();
        $settings = get_option(self::OPTION_KEY, array());

        if (isset($input['autosave_interval']))
            $sanitized['autosave_interval'] = max(10, min(120, intval($input['autosave_interval'])));
        $sanitized['progressive_form'] = isset($input['progressive_form']) ? true : false;

        if (isset($input['allowed_post_types']) && is_array($input['allowed_post_types'])) {
            $sanitized['allowed_post_types'] = array_map('sanitize_text_field', $input['allowed_post_types']);
        } else {
            $sanitized['allowed_post_types'] = array('post', 'page');
        }

        $sanitized['submission_required'] = isset($input['submission_required']) ? true : false;

        // Templates (JSON decode -> sanitize -> encode)
        if (isset($input['step_templates'])) {
            $templates = json_decode(stripslashes($input['step_templates']), true);
            if (is_array($templates)) {
                $clean_templates = array();
                foreach ($templates as $tpl) {
                    if (empty($tpl['name']))
                        continue; // Skip empty names

                    $clean_steps = array();
                    if (isset($tpl['steps']) && is_array($tpl['steps'])) {
                        foreach ($tpl['steps'] as $step) {
                            $clean_steps[] = array(
                                'key' => sanitize_key($step['key']), // Keys must be safe
                                'title' => sanitize_text_field($step['title']),
                                'description' => sanitize_textarea_field($step['description'])
                            );
                        }
                    }

                    $clean_templates[] = array(
                        'id' => sanitize_key($tpl['id']),
                        'name' => sanitize_text_field($tpl['name']),
                        'steps' => $clean_steps
                    );
                }
                $sanitized['step_templates'] = $clean_templates;
            }
        }

        if (isset($input['supabase_url']))
            $sanitized['supabase_url'] = esc_url_raw($input['supabase_url']);
        if (isset($input['supabase_anon_key']))
            $sanitized['supabase_anon_key'] = sanitize_text_field($input['supabase_anon_key']);
        if (isset($input['supabase_service_key']))
            $sanitized['supabase_service_key'] = sanitize_text_field($input['supabase_service_key']);

        // IA
        if (isset($input['openrouter_api_key'])) {
            $old_key = isset($settings['openrouter_api_key']) ? $settings['openrouter_api_key'] : '';
            $sanitized['openrouter_api_key'] = sanitize_text_field($input['openrouter_api_key']);

            // Se a chave mudou, limpa o cache de modelos
            if ($sanitized['openrouter_api_key'] !== $old_key) {
                delete_transient('mla_openrouter_models_cache');
            }
        }

        if (isset($input['openrouter_model'])) {
            $sanitized['openrouter_model'] = sanitize_text_field($input['openrouter_model']);
        }

        if (isset($input['ai_system_prompt'])) {
            $sanitized['ai_system_prompt'] = sanitize_textarea_field($input['ai_system_prompt']);
        }

        if (isset($input['methodology_explanation'])) {
            $sanitized['methodology_explanation'] = wp_kses_post($input['methodology_explanation']);
        }

        return $sanitized;
    }
}
