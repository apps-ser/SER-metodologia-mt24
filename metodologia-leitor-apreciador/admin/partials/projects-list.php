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

$current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'active';
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
    } elseif ('archived' === $message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Projeto arquivado com sucesso.', 'metodologia-leitor-apreciador') . '</p></div>';
    } elseif ('restored' === $message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Projeto restaurado com sucesso.', 'metodologia-leitor-apreciador') . '</p></div>';
    }

    // Exibir erros
    settings_errors('mla_projects');
    ?>

    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo esc_url(admin_url('admin.php?page=mla-projects&status=active')); ?>"
                class="<?php echo 'active' === $current_status ? 'current' : ''; ?>">
                <?php esc_html_e('Ativos', 'metodologia-leitor-apreciador'); ?>
            </a> |
        </li>
        <li class="archived">
            <a href="<?php echo esc_url(admin_url('admin.php?page=mla-projects&status=archived')); ?>"
                class="<?php echo 'archived' === $current_status ? 'current' : ''; ?>">
                <?php esc_html_e('Arquivados', 'metodologia-leitor-apreciador'); ?>
            </a> |
        </li>
        <li class="all_status">
            <a href="<?php echo esc_url(admin_url('admin.php?page=mla-projects&status=all')); ?>"
                class="<?php echo 'all' === $current_status ? 'current' : ''; ?>">
                <?php esc_html_e('Todos', 'metodologia-leitor-apreciador'); ?>
            </a>
        </li>
    </ul>

    <?php if (is_wp_error($projects)): ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html($projects->get_error_message()); ?>
            </p>
        </div>
    <?php elseif (empty($projects)): ?>
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Nenhum projeto encontrado.', 'metodologia-leitor-apreciador'); ?>
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
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mla-projects&action=edit&id=' . $project['id'])); ?>"
                                class="button button-small">
                                <?php esc_html_e('Editar', 'metodologia-leitor-apreciador'); ?>
                            </a>

                            <?php if ('active' === $project['status']): ?>
                                <button type="button" class="button button-small"
                                    onclick="mla_submit_action('archive', '<?php echo esc_attr($project['id']); ?>')">
                                    <?php esc_html_e('Arquivar', 'metodologia-leitor-apreciador'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="button button-small"
                                    onclick="mla_submit_action('restore', '<?php echo esc_attr($project['id']); ?>')">
                                    <?php esc_html_e('Restaurar', 'metodologia-leitor-apreciador'); ?>
                                </button>
                            <?php endif; ?>

                            <button type="button" class="button button-small button-link-delete"
                                onclick="if(confirm('<?php esc_attr_e('Tem certeza que deseja excluir permanentemente este projeto?', 'metodologia-leitor-apreciador'); ?>')) mla_submit_action('delete', '<?php echo esc_attr($project['id']); ?>')">
                                <?php esc_html_e('Excluir', 'metodologia-leitor-apreciador'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Formulário oculto para ações -->
    <form id="mla-action-form" method="post" style="display:none;">
        <?php wp_nonce_field('mla_project_action', 'mla_project_nonce'); ?>
        <input type="hidden" name="mla_action" id="mla-action-input">
        <input type="hidden" name="project_id" id="mla-id-input">
    </form>

    <script type="text/javascript">
        function mla_submit_action(action, id) {
            document.getElementById('mla-action-input').value = action;
            document.getElementById('mla-id-input').value = id;
            document.getElementById('mla-action-form').submit();
        }
    </script>
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
        width: 250px;
    }
</style>