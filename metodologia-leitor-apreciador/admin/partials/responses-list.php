<?php
/**
 * Template: Lista de respostas.
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('Respostas', 'metodologia-leitor-apreciador'); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="mla-responses">
            <div class="alignleft actions">
                <select name="project_id" id="mla-filter-project">
                    <option value="">
                        <?php esc_html_e('Todos os projetos', 'metodologia-leitor-apreciador'); ?>
                    </option>
                    <?php foreach ($projects as $id => $name): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($filters['project_id'], $id); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="text_id" id="mla-filter-text">
                    <option value="">
                        <?php esc_html_e('Todos os textos', 'metodologia-leitor-apreciador'); ?>
                    </option>
                    <?php foreach ($texts as $id => $name): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($filters['text_id'], $id); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">
                        <?php esc_html_e('Todos os status', 'metodologia-leitor-apreciador'); ?>
                    </option>
                    <option value="draft" <?php selected($filters['status'], 'draft'); ?>>
                        <?php esc_html_e('Rascunho', 'metodologia-leitor-apreciador'); ?>
                    </option>
                    <option value="submitted" <?php selected($filters['status'], 'submitted'); ?>>
                        <?php esc_html_e('Submetida', 'metodologia-leitor-apreciador'); ?>
                    </option>
                </select>
                <button type="submit" class="button">
                    <?php esc_html_e('Filtrar', 'metodologia-leitor-apreciador'); ?>
                </button>
            </div>
            <div class="alignright">
                <?php
                $has_text = !empty($filters['text_id']);
                $history_url = $has_text ? admin_url('admin.php?page=mla-responses&view=analyses&text_id=' . $filters['text_id']) : '#';
                ?>
                <button type="button" id="mla-analyze-ia" class="button button-primary"
                    data-text-id="<?php echo esc_attr($filters['text_id']); ?>" <?php echo $has_text ? '' : 'disabled="disabled"'; ?>>
                    <span class="dashicons dashicons-admin-appearance" style="margin-top:4px;"></span>
                    <?php esc_html_e('Analisar com IA', 'metodologia-leitor-apreciador'); ?>
                </button>
                <button type="button" id="mla-view-history" class="button <?php echo $has_text ? '' : 'disabled'; ?>"
                    data-text-id="<?php echo esc_attr($filters['text_id']); ?>" <?php echo $has_text ? '' : 'disabled="disabled"'; ?>>
                    <span class="dashicons dashicons-backup" style="margin-top:4px;"></span>
                    <?php esc_html_e('Ver Histórico', 'metodologia-leitor-apreciador'); ?>
                </button>
                |
                <a href="<?php echo esc_url(MLA_Export::get_export_url('csv', $filters)); ?>" class="button">
                    <?php esc_html_e('Exportar CSV', 'metodologia-leitor-apreciador'); ?>
                </a>
                <a href="<?php echo esc_url(MLA_Export::get_export_url('json', $filters)); ?>" class="button">
                    <?php esc_html_e('Exportar JSON', 'metodologia-leitor-apreciador'); ?>
                </a>
            </div>
        </form>
        <br class="clear">
    </div>

    <!-- Modal para Resultado da IA -->
    <div id="mla-ai-modal" style="display:none;"
        title="<?php esc_attr_e('Análise por IA', 'metodologia-leitor-apreciador'); ?>">
        <div id="mla-ai-result-content">
            <p><em><?php esc_html_e('Processando análise... isso pode levar cerca de um minuto.', 'metodologia-leitor-apreciador'); ?></em>
            </p>
            <div class="mla-progress-bar">
                <div class="mla-progress-value"></div>
            </div>
        </div>
    </div>

    <!-- Modal para Histórico de Análises -->
    <div id="mla-history-modal" style="display:none;"
        title="<?php esc_attr_e('Gerenciador de Análises', 'metodologia-leitor-apreciador'); ?>">
        <!-- View 1: Lista de Análises -->
        <div id="mla-history-list-view">
            <div id="mla-history-list-content">
                <div class="mla-loading-history">
                    <span class="spinner is-active" style="float:none; margin:0 10px 0 0;"></span>
                    <?php esc_html_e('Carregando histórico...', 'metodologia-leitor-apreciador'); ?>
                </div>
            </div>
        </div>

        <!-- View 2: Editor de Análise -->
        <div id="mla-history-editor-view" style="display:none;">
            <div id="mla-editor-status"></div>
            <div class="mla-editor-header">
                <button type="button" class="button button-secondary" id="mla-editor-back">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php esc_html_e('Voltar', 'metodologia-leitor-apreciador'); ?>
                </button>
                <div class="mla-editor-meta">
                    <span id="mla-editor-date"></span>
                    <span id="mla-editor-model" class="mla-badge"></span>
                </div>
                <div class="mla-editor-actions">
                    <button type="button" class="button button-secondary" id="mla-editor-export">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Exportar', 'metodologia-leitor-apreciador'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="mla-editor-save">
                        <?php esc_html_e('Salvar Alterações', 'metodologia-leitor-apreciador'); ?>
                    </button>
                </div>
            </div>
            <div class="mla-editor-body">
                <textarea id="mla-analysis-editor"></textarea>
            </div>
        </div>
    </div>

    <?php if (is_wp_error($responses)): ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html($responses->get_error_message()); ?>
            </p>
        </div>
    <?php elseif (empty($responses)): ?>
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Nenhuma resposta encontrada.', 'metodologia-leitor-apreciador'); ?>
            </p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>
                        <?php esc_html_e('Usuário', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Status', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Versão', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Atualizado', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th>
                        <?php esc_html_e('Ações', 'metodologia-leitor-apreciador'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responses as $response): ?>
                    <?php $user = get_user_by('id', $response['wp_user_id']); ?>
                    <tr>
                        <td>
                            <?php echo esc_html($user ? $user->display_name : '#' . $response['wp_user_id']); ?>
                        </td>
                        <td><span class="mla-badge mla-<?php echo esc_attr($response['status']); ?>">
                                <?php echo esc_html($response['status']); ?>
                            </span></td>
                        <td>v
                            <?php echo esc_html($response['version']); ?>
                        </td>
                        <td>
                            <?php echo esc_html(wp_date('d/m/Y H:i', strtotime($response['updated_at']))); ?>
                        </td>
                        <td><a
                                href="<?php echo esc_url(admin_url('admin.php?page=mla-responses&view=detail&id=' . $response['id'])); ?>">
                                <?php esc_html_e('Ver', 'metodologia-leitor-apreciador'); ?>
                            </a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<style>
    .mla-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px
    }

    .mla-submitted {
        background: #d4edda;
        color: #155724
    }

    .mla-draft {
        background: #fff3cd;
        color: #856404
    }

    /* Estilos do Modal e Progress Bar */
    #mla-ai-result-content {
        padding: 15px;
        line-height: 1.6;
    }

    #mla-ai-result-content h1,
    #mla-ai-result-content h2 {
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .mla-progress-bar {
        height: 10px;
        background: #eee;
        border-radius: 5px;
        overflow: hidden;
        margin-top: 20px;
    }

    .mla-progress-value {
        height: 100%;
        background: #2271b1;
        width: 0%;
        transition: width 30s linear;
    }

    #mla-ai-result-content pre {
        white-space: pre-wrap;
        background: #f6f7f7;
        padding: 10px;
        border: 1px solid #dcdcde;
    }
</style>