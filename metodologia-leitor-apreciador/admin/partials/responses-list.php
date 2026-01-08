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
                <select name="project_id">
                    <option value="">
                        <?php esc_html_e('Todos os projetos', 'metodologia-leitor-apreciador'); ?>
                    </option>
                    <?php foreach ($projects as $id => $name): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($filters['project_id'], $id); ?>>
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
</style>