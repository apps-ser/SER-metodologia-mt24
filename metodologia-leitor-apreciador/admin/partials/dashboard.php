<?php
/**
 * Template: Dashboard do plugin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap mla-dashboard">
    <h1>
        <?php esc_html_e('Leitor-Apreciador - Dashboard', 'metodologia-leitor-apreciador'); ?>
    </h1>

    <div class="mla-dashboard-cards">
        <!-- Card: Projetos -->
        <div class="mla-card">
            <div class="mla-card-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <div class="mla-card-content">
                <h2>
                    <?php echo esc_html($projects_count); ?>
                </h2>
                <p>
                    <?php esc_html_e('Projetos', 'metodologia-leitor-apreciador'); ?>
                </p>
            </div>
            <div class="mla-card-action">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mla-projects')); ?>" class="button">
                    <?php esc_html_e('Gerenciar', 'metodologia-leitor-apreciador'); ?>
                </a>
            </div>
        </div>

        <!-- Card: Textos -->
        <div class="mla-card">
            <div class="mla-card-icon">
                <span class="dashicons dashicons-media-text"></span>
            </div>
            <div class="mla-card-content">
                <h2>
                    <?php echo esc_html($texts_count); ?>
                </h2>
                <p>
                    <?php esc_html_e('Textos com Metodologia', 'metodologia-leitor-apreciador'); ?>
                </p>
            </div>
            <div class="mla-card-action">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mla-texts')); ?>" class="button">
                    <?php esc_html_e('Visualizar', 'metodologia-leitor-apreciador'); ?>
                </a>
            </div>
        </div>

        <!-- Card: Respostas -->
        <div class="mla-card">
            <div class="mla-card-icon">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="mla-card-content">
                <h2>
                    <?php echo esc_html($responses_count); ?>
                </h2>
                <p>
                    <?php esc_html_e('Total de Respostas', 'metodologia-leitor-apreciador'); ?>
                </p>
            </div>
            <div class="mla-card-action">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mla-responses')); ?>" class="button">
                    <?php esc_html_e('Analisar', 'metodologia-leitor-apreciador'); ?>
                </a>
            </div>
        </div>

        <!-- Card: Submetidas -->
        <div class="mla-card mla-card-success">
            <div class="mla-card-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="mla-card-content">
                <h2>
                    <?php echo esc_html($submitted_count); ?>
                </h2>
                <p>
                    <?php esc_html_e('Respostas Submetidas', 'metodologia-leitor-apreciador'); ?>
                </p>
            </div>
        </div>

        <!-- Card: Rascunhos -->
        <div class="mla-card mla-card-warning">
            <div class="mla-card-icon">
                <span class="dashicons dashicons-edit"></span>
            </div>
            <div class="mla-card-content">
                <h2>
                    <?php echo esc_html($draft_count); ?>
                </h2>
                <p>
                    <?php esc_html_e('Rascunhos', 'metodologia-leitor-apreciador'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Informações do Plugin -->
    <div class="mla-info-box">
        <h3>
            <?php esc_html_e('Sobre a Metodologia do Leitor-Apreciador', 'metodologia-leitor-apreciador'); ?>
        </h3>
        <p>
            <?php esc_html_e('Este plugin implementa a Metodologia do Leitor-Apreciador, permitindo que leitores respondam a formulários reflexivos estruturados ao final de textos selecionados.', 'metodologia-leitor-apreciador'); ?>
        </p>
        <p>
            <?php esc_html_e('As respostas são armazenadas de forma segura no Supabase, com versionamento automático e histórico completo de edições.', 'metodologia-leitor-apreciador'); ?>
        </p>
    </div>

    <!-- Status do Supabase -->
    <div class="mla-status-box">
        <h3>
            <?php esc_html_e('Status da Integração', 'metodologia-leitor-apreciador'); ?>
        </h3>
        <?php
        $settings = get_option('mla_settings', array());
        $has_constants = defined('MLA_SUPABASE_URL') && defined('MLA_SUPABASE_ANON_KEY');
        $has_settings = !empty($settings['supabase_url']) && !empty($settings['supabase_anon_key']);

        if ($has_constants || $has_settings): ?>
            <p class="mla-status-ok">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Supabase configurado e pronto para uso.', 'metodologia-leitor-apreciador'); ?>
            </p>
        <?php else: ?>
            <p class="mla-status-warning">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e('Supabase não está configurado.', 'metodologia-leitor-apreciador'); ?>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=mla-settings')); ?>" class="button">
                <?php esc_html_e('Configurar Integração', 'metodologia-leitor-apreciador'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>