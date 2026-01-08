<?php
/**
 * Classe responsável pela internacionalização do plugin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_I18n
 *
 * Carrega e gerencia a tradução do plugin.
 */
class MLA_I18n
{

    /**
     * Carrega o text domain do plugin para tradução.
     *
     * @return void
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'metodologia-leitor-apreciador',
            false,
            dirname(MLA_PLUGIN_BASENAME) . '/languages/'
        );
    }
}
