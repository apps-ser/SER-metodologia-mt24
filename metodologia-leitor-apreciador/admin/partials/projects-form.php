<?php
/**
 * Template: Formulário de projeto.
 *
 * @package MetodologiaLeitorApreciador
 *
 * @var string     $action     Ação atual (new ou edit).
 * @var string     $project_id ID do projeto (se editando).
 * @var array|null $project    Dados do projeto (se editando).
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

$is_edit = 'edit' === $action && $project;
?>

<div class="wrap">
    <h1>
        <?php
        if ($is_edit) {
            esc_html_e('Editar Projeto', 'metodologia-leitor-apreciador');
        } else {
            esc_html_e('Novo Projeto', 'metodologia-leitor-apreciador');
        }
        ?>
    </h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=mla-projects')); ?>" class="page-title-action">
        <?php esc_html_e('← Voltar para lista', 'metodologia-leitor-apreciador'); ?>
    </a>
    <hr class="wp-header-end">

    <?php settings_errors('mla_projects'); ?>

    <form method="post" action="">
        <?php wp_nonce_field('mla_project_action', 'mla_project_nonce'); ?>

        <input type="hidden" name="mla_action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">

        <?php if ($is_edit): ?>
            <input type="hidden" name="project_id" value="<?php echo esc_attr($project_id); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tbody>
                <!-- Nome -->
                <tr>
                    <th scope="row">
                        <label for="name">
                            <?php esc_html_e('Nome do Projeto', 'metodologia-leitor-apreciador'); ?> <span
                                class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="name" id="name" class="regular-text"
                            value="<?php echo $is_edit ? esc_attr($project['name']) : ''; ?>" required>
                        <p class="description">
                            <?php esc_html_e('Ex: Projeto Mateus 24, Estudo Sermão do Monte', 'metodologia-leitor-apreciador'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Descrição -->
                <tr>
                    <th scope="row">
                        <label for="description">
                            <?php esc_html_e('Descrição', 'metodologia-leitor-apreciador'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea name="description" id="description" rows="4"
                            class="large-text"><?php echo $is_edit ? esc_textarea($project['description']) : ''; ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Descrição opcional do projeto e seus objetivos.', 'metodologia-leitor-apreciador'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Status -->
                <tr>
                    <th scope="row">
                        <label for="status">
                            <?php esc_html_e('Status', 'metodologia-leitor-apreciador'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="active" <?php selected($is_edit ? $project['status'] : 'active', 'active'); ?>>
                                <?php esc_html_e('Ativo', 'metodologia-leitor-apreciador'); ?>
                            </option>
                            <option value="archived" <?php selected($is_edit ? $project['status'] : '', 'archived'); ?>>
                                <?php esc_html_e('Arquivado', 'metodologia-leitor-apreciador'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Projetos arquivados não aparecem na lista de seleção.', 'metodologia-leitor-apreciador'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Template -->
                <tr>
                    <th scope="row">
                        <label for="template_id">
                            <?php esc_html_e('Modelo de Etapas', 'metodologia-leitor-apreciador'); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        $settings = get_option('mla_settings', array());
                        $templates = isset($settings['step_templates']) ? $settings['step_templates'] : array();
                        $project_templates = get_option('mla_project_templates', array());
                        $current_template = ($is_edit && isset($project_templates[$project_id])) ? $project_templates[$project_id] : '';
                        ?>
                        <select name="template_id" id="template_id">
                            <option value="">
                                <?php esc_html_e('— Selecionar Modelo —', 'metodologia-leitor-apreciador'); ?></option>
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?php echo esc_attr($tpl['id']); ?>" <?php selected($current_template, $tpl['id']); ?>>
                                    <?php echo esc_html($tpl['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Define quais etapas e textos de ajuda aparecerão para este projeto.', 'metodologia-leitor-apreciador'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php
                if ($is_edit) {
                    esc_html_e('Salvar Alterações', 'metodologia-leitor-apreciador');
                } else {
                    esc_html_e('Criar Projeto', 'metodologia-leitor-apreciador');
                }
                ?>
            </button>

            <?php if ($is_edit): ?>
                <button type="submit" name="mla_action" value="delete" class="button button-link-delete"
                    onclick="return confirm('<?php esc_attr_e('Tem certeza que deseja excluir este projeto? Esta ação não pode ser desfeita.', 'metodologia-leitor-apreciador'); ?>');">
                    <?php esc_html_e('Excluir Projeto', 'metodologia-leitor-apreciador'); ?>
                </button>
            <?php endif; ?>
        </p>
    </form>
</div>

<style>
    .required {
        color: #d63638;
    }

    .button-link-delete {
        color: #d63638 !important;
        margin-left: 15px !important;
    }

    .button-link-delete:hover {
        color: #a00 !important;
    }
</style>