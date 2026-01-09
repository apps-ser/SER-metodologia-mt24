<?php
/**
 * Template: Histórico de análises de IA (Visual Premium)
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap mla-analyses-page">
    <div class="mla-header">
        <div class="mla-header-content">
            <h1 class="wp-heading-inline">
                <?php echo esc_html($text_title ?: __('Histórico de Análises', 'metodologia-leitor-apreciador')); ?>
            </h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=mla-responses&text_id=' . $text_id)); ?>"
                class="mla-back-button">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php esc_html_e('Voltar para Respostas', 'metodologia-leitor-apreciador'); ?>
            </a>
        </div>
    </div>

    <div class="mla-container">
        <?php if (is_wp_error($analyses)): ?>
            <div class="mla-notice mla-notice-error">
                <div class="mla-notice-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="mla-notice-content">
                    <h3><?php esc_html_e('Ocorreu um erro', 'metodologia-leitor-apreciador'); ?></h3>
                    <p><?php echo esc_html($analyses->get_error_message()); ?></p>
                </div>
            </div>
        <?php elseif (empty($analyses)): ?>
            <div class="mla-empty-state">
                <div class="mla-empty-icon">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <h2><?php esc_html_e('Nenhuma análise encontrada', 'metodologia-leitor-apreciador'); ?></h2>
                <p><?php esc_html_e('Ainda não existem análises de IA para este texto. Execute uma nova análise na página de respostas.', 'metodologia-leitor-apreciador'); ?>
                </p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=mla-responses&text_id=' . $text_id)); ?>"
                    class="button button-primary">
                    <?php esc_html_e('Ir para Respostas', 'metodologia-leitor-apreciador'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="mla-timeline">
                <?php foreach ($analyses as $index => $analysis): ?>
                    <div class="mla-card <?php echo $index === 0 ? 'mla-card-latest' : ''; ?>">
                        <div class="mla-card-header">
                            <div class="mla-card-meta">
                                <span class="mla-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo esc_html(wp_date('d \d\e F \d\e Y', strtotime($analysis['created_at']))); ?>
                                </span>
                                <span class="mla-time">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html(wp_date('H:i', strtotime($analysis['created_at']))); ?>
                                </span>
                            </div>
                            <div class="mla-model-badge">
                                <?php
                                $model_name = $analysis['model'];
                                if (strpos($model_name, '/') !== false) {
                                    $parts = explode('/', $model_name);
                                    $model_name = end($parts);
                                }
                                echo esc_html(strtoupper($model_name));
                                ?>
                            </div>
                        </div>
                        <div class="mla-card-body">
                            <div class="mla-ai-content mla-markdown-render">
                                <?php echo wp_kses_post(nl2br(esc_html($analysis['content']))); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .mla-analyses-page {
        margin-top: 20px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }

    .mla-header {
        margin-bottom: 30px;
        background: #fff;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .mla-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .mla-header h1 {
        margin: 0 !important;
        font-weight: 700 !important;
        color: #1e293b;
        font-size: 24px !important;
    }

    .mla-back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #64748b;
        font-weight: 500;
        transition: color 0.2s;
    }

    .mla-back-button:hover {
        color: #2271b1;
    }

    .mla-container {
        padding-bottom: 50px;
    }

    /* Cards */
    .mla-card {
        background: #fff;
        border-radius: 12px;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .mla-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .mla-card-latest {
        border-left: 5px solid #2271b1;
    }

    .mla-card-header {
        padding: 16px 24px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .mla-card-meta {
        display: flex;
        gap: 20px;
        color: #64748b;
        font-size: 13px;
    }

    .mla-card-meta .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
        margin-right: 4px;
    }

    .mla-model-badge {
        background: #e2e8f0;
        color: #475569;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
    }

    .mla-card-body {
        padding: 24px;
    }

    .mla-ai-content {
        line-height: 1.7;
        color: #334155;
        font-size: 15px;
        white-space: pre-wrap;
    }

    /* Empty State */
    .mla-empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 12px;
        border: 2px dashed #cbd5e1;
    }

    .mla-empty-icon {
        color: #94a3b8;
        margin-bottom: 20px;
    }

    .mla-empty-icon .dashicons {
        font-size: 64px;
        width: 64px;
        height: 64px;
    }

    .mla-empty-state h2 {
        color: #1e293b;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .mla-empty-state p {
        color: #64748b;
        margin-bottom: 24px;
    }

    /* Notices */
    .mla-notice {
        display: flex;
        gap: 16px;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 24px;
    }

    .mla-notice-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .mla-notice-icon .dashicons {
        font-size: 32px;
        width: 32px;
        height: 32px;
    }

    .mla-notice-content h3 {
        margin: 0 0 8px 0 !important;
        font-size: 18px !important;
        color: inherit;
    }

    .mla-notice-content p {
        margin: 0 !important;
    }
</style>