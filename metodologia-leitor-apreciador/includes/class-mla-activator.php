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
            'step_1' => array(
                'title' => __('Compreensão Geral', 'metodologia-leitor-apreciador'),
                'description' => __('Identifique o tema central e os temas secundários abordados no texto. Reflita sobre a mensagem principal que o autor busca transmitir.', 'metodologia-leitor-apreciador'),
            ),
            'step_2' => array(
                'title' => __('Conexões Doutrinárias', 'metodologia-leitor-apreciador'),
                'description' => __('Relacione o conteúdo com outros textos, doutrinas ou passagens evangélicas que você conhece. Quais conexões você percebe?', 'metodologia-leitor-apreciador'),
            ),
            'step_3' => array(
                'title' => __('Avaliação do Texto', 'metodologia-leitor-apreciador'),
                'description' => __('Quais aspectos positivos você identifica no texto? O que mais lhe chamou a atenção de forma construtiva?', 'metodologia-leitor-apreciador'),
            ),
            'step_4' => array(
                'title' => __('Investigação Crítica', 'metodologia-leitor-apreciador'),
                'description' => __('Registre suas dúvidas. Há pontos que você gostaria de compreender melhor ou que geraram questionamentos?', 'metodologia-leitor-apreciador'),
            ),
            'step_5' => array(
                'title' => __('Formulação Consciente', 'metodologia-leitor-apreciador'),
                'description' => __('A partir das dúvidas identificadas, formule perguntas claras e objetivas que poderiam ser dirigidas aos autores espirituais.', 'metodologia-leitor-apreciador'),
            ),
        );
    }
}
