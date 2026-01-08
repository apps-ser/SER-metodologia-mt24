<?php
/**
 * Template: Lista de projetos.
 *
 * @package MetodologiaLeitorApreciador
 *
 * @var string $message  Mensagem de sucesso.
 * @var array  $projects Lista de projetos.
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Projetos', 'metodologia-leitor-apreciador'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mla-projects&action=new')); ?>" class="page-title-action">
        <?php esc_html_e('Adicionar Novo', 'metodologia-leitor-apreciador'); ?>
    </a>
    <hr class="wp-header-end">

    <?php
    // Mensagens de sucesso
    if ('created' === $message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Projeto criado com sucesso.', 'metodologia-leitor-apreciador') . '</p></div>';
    } elseif ('updated' === $message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Projeto atualizado com sucesso.', 'metodologia-leitor-apreciador') . '</p></div>';
    } elseif ('deleted' === $message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Projeto excluído com sucesso.', 'metodologia-leitor-apreciador') . '</p></div>';
    }

    // Exibir erros
    settings_errors('mla_projects');
    ?>

    <?php if (is_wp_error($projects)): ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html($projects->get_error_message()); ?>
            </p>
        </div>
    <?php elseif (empty($projects)): ?>
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Nenhum projeto cadastrado. Crie o primeiro projeto para começar.', 'metodologia-leitor-apreciador'); ?>
            </p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-name">
                        <?php esc_html_e('Nome', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-description">
                        <?php esc_html_e('Descrição', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-status">
                        <?php esc_html_e('Status', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-created">
                        <?php esc_html_e('Criado em', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-actions">
                        <?php esc_html_e('Ações', 'metodologia-leitor-apreciador'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td class="column-name">
                            <strong>
                                <a
                                    href="<?php echo esc_url(admin_url('admin.php?page=mla-projects&action=edit&id=' . $project['id'])); ?>">
                                    <?php echo esc_html($project['name']); ?>
                                </a>
                            </strong>
                        </td>
                        <td class="column-description">
                            <?php echo esc_html(wp_trim_words($project['description'], 15)); ?>
                        </td>
                        <td class="column-status">
                            <?php if ('active' === $project['status']): ?>
                                <span class="mla-status-badge mla-status-active">
                                    <?php esc_html_e('Ativo', 'metodologia-leitor-apreciador'); ?>
                                </span>
                            <?php else: ?>
                                <span class="mla-status-badge mla-status-archived">
                                    <?php esc_html_e('Arquivado', 'metodologia-leitor-apreciador'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="column-created">
                            <?php
                            $date = strtotime($project['created_at']);
                            echo esc_html(wp_date(get_option('date_format'), $date));
                            ?>
                        </td>
                        <td class="column-actions">
                            <a
                                href="<?php echo esc_url(admin_url('admin.php?page=mla-projects&action=edit&id=' . $project['id'])); ?>">
                                <?php esc_html_e('Editar', 'metodologia-leitor-apreciador'); ?>
                            </a>
                            |
                            <a
                                href="<?php echo esc_url(admin_url('admin.php?page=mla-responses&project_id=' . $project['id'])); ?>">
                                <?php esc_html_e('Ver Respostas', 'metodologia-leitor-apreciador'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
    .mla-status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
    }

    .mla-status-active {
        background: #d4edda;
        color: #155724;
    }

    .mla-status-archived {
        background: #e2e3e5;
        color: #383d41;
    }

    .column-status {
        width: 100px;
    }

    .column-created {
        width: 120px;
    }

    .column-actions {
        width: 150px;
    }
</style>