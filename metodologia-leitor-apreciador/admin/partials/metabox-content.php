<?php
/**
 * Template: Conteúdo da metabox.
 *
 * @package MetodologiaLeitorApreciador
 *
 * @var WP_Post $post       Post atual.
 * @var string  $enabled    Metodologia ativada (1 ou 0).
 * @var string  $project_id ID do projeto vinculado.
 * @var string  $text_id    ID do texto no Supabase.
 * @var array   $projects   Lista de projetos disponíveis.
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}
?>

<div class="mla-metabox-content">
    <!-- Checkbox: Ativar Metodologia -->
    <p>
        <label>
            <input type="checkbox" name="mla_enabled" id="mla_enabled" value="1" <?php checked($enabled, '1'); ?>>
            <strong>
                <?php esc_html_e('Ativar Metodologia do Leitor-Apreciador', 'metodologia-leitor-apreciador'); ?>
            </strong>
        </label>
    </p>
    <p class="description">
        <?php esc_html_e('Quando ativado, o formulário será exibido ao final do conteúdo para usuários logados.', 'metodologia-leitor-apreciador'); ?>
    </p>

    <hr>

    <!-- Select: Projeto Vinculado -->
    <p>
        <label for="mla_project_id">
            <strong>
                <?php esc_html_e('Projeto Vinculado', 'metodologia-leitor-apreciador'); ?>
            </strong>
        </label>
    </p>
    <p>
        <select name="mla_project_id" id="mla_project_id" class="widefat">
            <option value="">
                <?php esc_html_e('— Nenhum projeto —', 'metodologia-leitor-apreciador'); ?>
            </option>
            <?php foreach ($projects as $id => $name): ?>
                <option value="<?php echo esc_attr($id); ?>" <?php selected($project_id, $id); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="description">
        <?php esc_html_e('Opcional. Vincular a um projeto facilita a organização e análise das respostas.', 'metodologia-leitor-apreciador'); ?>
    </p>

    <?php if (!empty($text_id)): ?>
        <hr>
        <p class="mla-text-id">
            <small>
                <strong>
                    <?php esc_html_e('ID do Texto:', 'metodologia-leitor-apreciador'); ?>
                </strong>
                <code><?php echo esc_html($text_id); ?></code>
            </small>
        </p>
    <?php endif; ?>

    <?php if (empty($projects)): ?>
        <hr>
        <p class="mla-notice">
            <span class="dashicons dashicons-info"></span>
            <?php
            printf(
                /* translators: %s: link para página de projetos */
                esc_html__('Nenhum projeto cadastrado. %s', 'metodologia-leitor-apreciador'),
                '<a href="' . esc_url(admin_url('admin.php?page=mla-projects&action=new')) . '">' . esc_html__('Criar projeto', 'metodologia-leitor-apreciador') . '</a>'
            );
            ?>
        </p>
    <?php endif; ?>
</div>

<style>
    .mla-metabox-content hr {
        margin: 15px 0;
        border: 0;
        border-top: 1px solid #ddd;
    }

    .mla-metabox-content .description {
        color: #666;
        font-style: italic;
    }

    .mla-text-id code {
        font-size: 11px;
        word-break: break-all;
    }

    .mla-notice {
        background: #f0f6fc;
        padding: 8px 10px;
        border-left: 3px solid #0073aa;
        margin: 10px 0;
    }

    .mla-notice .dashicons {
        color: #0073aa;
        margin-right: 5px;
    }
</style>