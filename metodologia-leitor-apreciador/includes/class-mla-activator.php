<?php
/**
 * Classe responsável pela ativação e desativação do plugin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Activator
 *
 * Gerencia a ativação e desativação do plugin.
 */
class MLA_Activator
{

    /**
     * Executado durante a ativação do plugin.
     *
     * Configura opções padrão e flush de rewrite rules.
     *
     * @return void
     */
    public static function activate()
    {
        // Configurar opções padrão se não existirem
        if (false === get_option('mla_settings')) {
            $default_settings = array(
                'autosave_interval' => 20,
                'progressive_form' => true,
                'submission_required' => false,
                'step_texts' => self::get_default_step_texts(),
                'step_templates' => array(
                    array(
                        'id' => 'tpl_default',
                        'name' => 'Metodologia Leitor-Apreciador (Padrão)',
                        'steps' => array(
                            array(
                                'key' => 'tema_central',
                                'title' => 'Tema Central',
                                'description' => 'Identifique e descreva de forma concisa a ideia principal que o texto busca transmitir.'
                            ),
                            array(
                                'key' => 'temas_subsidiarios',
                                'title' => 'Temas Subsidiários',
                                'description' => 'Aponte outros assuntos ou ideias secundárias que apoiam e complementam o tema central.'
                            ),
                            array(
                                'key' => 'correlacoes_doutrinarias',
                                'title' => 'Correlações Doutrinárias',
                                'description' => 'Relacione este conteúdo com outros textos, obras ou passagens evangélicas de seu conhecimento.'
                            ),
                            array(
                                'key' => 'aspectos_positivos',
                                'title' => 'Aspectos Positivos',
                                'description' => 'Quais ensinamentos e pontos fortes você destaca neste texto? O que foi mais valioso para você?'
                            ),
                            array(
                                'key' => 'duvidas',
                                'title' => 'Dúvidas',
                                'description' => 'Exponha os pontos que não ficaram totalmente claros ou que geraram algum tipo de estranhamento ou dúvida.'
                            ),
                            array(
                                'key' => 'perguntas_autores',
                                'title' => 'Perguntas para os Autores',
                                'description' => 'A partir de suas dúvidas, formule perguntas profundas baseadas fielmente no texto. ATENÇÃO: Perguntas fora de contexto ou alheias ao texto serão excluídas da análise.'
                            ),
                        )
                    )
                ),
            );
            add_option('mla_settings', $default_settings);
        }

        // Flush rewrite rules para registrar endpoints REST
        flush_rewrite_rules();

        // Registrar versão instalada
        add_option('mla_version', MLA_VERSION);

        // Log de ativação
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Metodologia Leitor-Apreciador: Plugin ativado - v' . MLA_VERSION);
        }
    }

    /**
     * Executado durante a desativação do plugin.
     *
     * @return void
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Limpar transients
        delete_transient('mla_projects_cache');
        delete_transient('mla_texts_cache');

        // Log de desativação
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Metodologia Leitor-Apreciador: Plugin desativado');
        }
    }

    /**
     * Retorna os textos padrão para as etapas do formulário.
     *
     * @return array Textos orientadores por etapa.
     */
    private static function get_default_step_texts()
    {
        return array(
            'tema_central' => array(
                'title' => __('Tema Central', 'metodologia-leitor-apreciador'),
                'description' => __('Identifique e descreva de forma concisa a ideia principal que o texto busca transmitir.', 'metodologia-leitor-apreciador'),
            ),
            'temas_subsidiarios' => array(
                'title' => __('Temas Subsidiários', 'metodologia-leitor-apreciador'),
                'description' => __('Aponte outros assuntos ou ideias secundárias que apoiam e complementam o tema central.', 'metodologia-leitor-apreciador'),
            ),
            'correlacoes_doutrinarias' => array(
                'title' => __('Correlações Doutrinárias', 'metodologia-leitor-apreciador'),
                'description' => __('Relacione este conteúdo com outros textos, obras ou passagens evangélicas de seu conhecimento.', 'metodologia-leitor-apreciador'),
            ),
            'aspectos_positivos' => array(
                'title' => __('Aspectos Positivos', 'metodologia-leitor-apreciador'),
                'description' => __('Quais ensinamentos e pontos fortes você destaca neste texto? O que foi mais valioso para você?', 'metodologia-leitor-apreciador'),
            ),
            'duvidas' => array(
                'title' => __('Dúvidas', 'metodologia-leitor-apreciador'),
                'description' => __('Exponha os pontos que não ficaram totalmente claros ou que geraram algum tipo de estranhamento ou dúvida.', 'metodologia-leitor-apreciador'),
            ),
            'perguntas_autores' => array(
                'title' => __('Perguntas para os Autores', 'metodologia-leitor-apreciador'),
                'description' => __('A partir de suas dúvidas, formule perguntas profundas baseadas fielmente no texto. ATENÇÃO: Perguntas fora de contexto ou alheias ao texto serão excluídas da análise.', 'metodologia-leitor-apreciador'),
            ),
        );
    }
}
