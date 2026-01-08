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

        // Seção: Textos das Etapas
        add_settings_section(
            'mla_steps_section',
            __('Textos Orientadores das Etapas', 'metodologia-leitor-apreciador'),
            array($this, 'render_steps_section'),
            'mla-settings'
        );

        // Campos dinâmicos para cada etapa
        for ($i = 1; $i <= 5; $i++) {
            add_settings_field(
                'step_' . $i,
                sprintf(__('Etapa %d', 'metodologia-leitor-apreciador'), $i),
                array($this, 'render_step_field'),
                'mla-settings',
                'mla_steps_section',
                array('step' => $i)
            );
        }

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
    }

    /**
     * Renderiza a página de configurações.
     *
     * @return void
     */
    public function render_page()
    {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'metodologia-leitor-apreciador'));
        }

        include MLA_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * Renderiza a descrição da seção geral.
     *
     * @return void
     */
    public function render_general_section()
    {
        echo '<p>' . esc_html__('Configure o comportamento geral do formulário.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza a descrição da seção de etapas.
     *
     * @return void
     */
    public function render_steps_section()
    {
        echo '<p>' . esc_html__('Personalize os textos de orientação exibidos em cada etapa do formulário.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza a descrição da seção do Supabase.
     *
     * @return void
     */
    public function render_supabase_section()
    {
        echo '<p>' . esc_html__('Configure as credenciais do seu projeto Supabase.', 'metodologia-leitor-apreciador') . '</p>';

        if (defined('MLA_SUPABASE_URL') && defined('MLA_SUPABASE_ANON_KEY')) {
            echo '<div class="notice notice-warning inline"><p>';
            echo esc_html__('Atenção: As constantes MLA_SUPABASE_URL e MLA_SUPABASE_ANON_KEY estão definidas no wp-config.php e terão prioridade sobre estas configurações.', 'metodologia-leitor-apreciador');
            echo '</p></div>';
        }
    }

    /**
     * Renderiza o campo de URL do Supabase.
     */
    public function render_supabase_url_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['supabase_url']) ? $settings['supabase_url'] : '';
        printf(
            '<input type="url" name="%s[supabase_url]" value="%s" class="regular-text code">',
            esc_attr(self::OPTION_KEY),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Ex: https://seubrojeto.supabase.co', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza o campo de Anon Key.
     */
    public function render_supabase_anon_key_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['supabase_anon_key']) ? $settings['supabase_anon_key'] : '';
        printf(
            '<input type="password" name="%s[supabase_anon_key]" value="%s" class="regular-text code">',
            esc_attr(self::OPTION_KEY),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Chave pública (anon/public).', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza o campo de Service Key.
     */
    public function render_supabase_service_key_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['supabase_service_key']) ? $settings['supabase_service_key'] : '';
        printf(
            '<input type="password" name="%s[supabase_service_key]" value="%s" class="regular-text code">',
            esc_attr(self::OPTION_KEY),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Chave secreta (service_role). Opcional, usada apenas para operações administrativas.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza o campo de intervalo de auto-save.
     *
     * @return void
     */
    public function render_autosave_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $value = isset($settings['autosave_interval']) ? intval($settings['autosave_interval']) : 20;

        printf(
            '<input type="number" id="autosave_interval" name="%s[autosave_interval]" value="%d" min="10" max="120" class="small-text">',
            esc_attr(self::OPTION_KEY),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Intervalo em segundos para salvamento automático do rascunho. Mínimo: 10, Máximo: 120.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza o campo de formulário progressivo.
     *
     * @return void
     */
    public function render_progressive_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $checked = isset($settings['progressive_form']) ? (bool) $settings['progressive_form'] : true;

        printf(
            '<label><input type="checkbox" id="progressive_form" name="%s[progressive_form]" value="1" %s> %s</label>',
            esc_attr(self::OPTION_KEY),
            checked($checked, true, false),
            esc_html__('Exibir formulário em etapas sequenciais', 'metodologia-leitor-apreciador')
        );
        echo '<p class="description">' . esc_html__('Se desativado, todos os campos serão exibidos de uma vez.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza o campo de tipos de post permitidos.
     *
     * @return void
     */
    public function render_allowed_post_types_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $allowed_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');

        // Obter todos os tipos de post públicos
        $args = array(
            'public' => true,
        );
        $post_types = get_post_types($args, 'objects');

        echo '<fieldset>';
        foreach ($post_types as $post_type) {
            // Pular attachment
            if ('attachment' === $post_type->name) {
                continue;
            }

            printf(
                '<label style="display:block; margin-bottom: 5px;">
                    <input type="checkbox" name="%s[allowed_post_types][]" value="%s" %s> %s (%s)
                </label>',
                esc_attr(self::OPTION_KEY),
                esc_attr($post_type->name),
                in_array($post_type->name, $allowed_types) ? 'checked="checked"' : '',
                esc_html($post_type->label),
                esc_html($post_type->name)
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Selecione em quais tipos de conteúdo a metodologia poderá ser ativada.', 'metodologia-leitor-apreciador') . '</p>';
    }

    /**
     * Renderiza o campo de submissão obrigatória.
     *
     * @return void
     */
    public function render_submission_field()
    {
        $settings = get_option(self::OPTION_KEY, array());
        $checked = isset($settings['submission_required']) ? (bool) $settings['submission_required'] : false;

        printf(
            '<label><input type="checkbox" id="submission_required" name="%s[submission_required]" value="1" %s> %s</label>',
            esc_attr(self::OPTION_KEY),
            checked($checked, true, false),
            esc_html__('Exigir submissão explícita (não apenas rascunho)', 'metodologia-leitor-apreciador')
        );
    }

    /**
     * Renderiza os campos de uma etapa.
     *
     * @param array $args Argumentos do campo.
     *
     * @return void
     */
    public function render_step_field($args)
    {
        $step = $args['step'];
        $settings = get_option(self::OPTION_KEY, array());
        $steps = isset($settings['step_texts']) ? $settings['step_texts'] : array();

        $title = isset($steps['step_' . $step]['title']) ? $steps['step_' . $step]['title'] : '';
        $description = isset($steps['step_' . $step]['description']) ? $steps['step_' . $step]['description'] : '';

        echo '<div style="margin-bottom: 10px;">';
        printf(
            '<label>%s</label><br><input type="text" name="%s[step_texts][step_%d][title]" value="%s" class="regular-text">',
            esc_html__('Título:', 'metodologia-leitor-apreciador'),
            esc_attr(self::OPTION_KEY),
            esc_attr($step),
            esc_attr($title)
        );
        echo '</div>';

        echo '<div>';
        printf(
            '<label>%s</label><br><textarea name="%s[step_texts][step_%d][description]" rows="2" class="large-text">%s</textarea>',
            esc_html__('Descrição:', 'metodologia-leitor-apreciador'),
            esc_attr(self::OPTION_KEY),
            esc_attr($step),
            esc_textarea($description)
        );
        echo '</div>';
    }

    /**
     * Sanitiza as configurações antes de salvar.
     *
     * @param array $input Dados de entrada.
     *
     * @return array Dados sanitizados.
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Auto-save interval
        if (isset($input['autosave_interval'])) {
            $sanitized['autosave_interval'] = max(10, min(120, intval($input['autosave_interval'])));
        }

        // Progressive form
        $sanitized['progressive_form'] = isset($input['progressive_form']) ? true : false;

        // Allowed Post Types
        if (isset($input['allowed_post_types']) && is_array($input['allowed_post_types'])) {
            $sanitized['allowed_post_types'] = array_map('sanitize_text_field', $input['allowed_post_types']);
        } else {
            $sanitized['allowed_post_types'] = array('post', 'page');
        }

        // Submission required
        $sanitized['submission_required'] = isset($input['submission_required']) ? true : false;

        // Step texts
        if (isset($input['step_texts']) && is_array($input['step_texts'])) {
            $sanitized['step_texts'] = array();

            for ($i = 1; $i <= 5; $i++) {
                $step_key = 'step_' . $i;

                if (isset($input['step_texts'][$step_key])) {
                    $sanitized['step_texts'][$step_key] = array(
                        'title' => sanitize_text_field($input['step_texts'][$step_key]['title']),
                        'description' => sanitize_textarea_field($input['step_texts'][$step_key]['description']),
                    );
                }
            }
        }

        // Supabase Settings
        if (isset($input['supabase_url'])) {
            $sanitized['supabase_url'] = esc_url_raw($input['supabase_url']);
        }

        if (isset($input['supabase_anon_key'])) {
            $sanitized['supabase_anon_key'] = sanitize_text_field($input['supabase_anon_key']);
        }

        if (isset($input['supabase_service_key'])) {
            $sanitized['supabase_service_key'] = sanitize_text_field($input['supabase_service_key']);
        }

        return $sanitized;
    }
}
