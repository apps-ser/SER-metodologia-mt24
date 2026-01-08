<?php
/**
 * Template: Página de configurações.
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('Configurações', 'metodologia-leitor-apreciador'); ?>
    </h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('mla_settings_group');
        do_settings_sections('mla-settings');
        submit_button();
        ?>
    </form>
</div>