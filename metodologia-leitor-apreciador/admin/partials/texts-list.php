<?php
/**
 * Template: Lista de textos.
 *
 * @package MetodologiaLeitorApreciador
 *
 * @var string $filter_project ID do projeto filtrado.
 * @var array  $texts          Lista de posts com metodologia ativa.
 * @var array  $projects       Lista de projetos para filtro.
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

$texts_controller = new MLA_Texts();
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('Textos com Metodologia Ativa', 'metodologia-leitor-apreciador'); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="mla-texts">

            <div class="alignleft actions">
                <select name="project_id">
                    <option value="">
                        <?php esc_html_e('Todos os projetos', 'metodologia-leitor-apreciador'); ?>
                    </option>
                    <?php foreach ($projects as $id => $name): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($filter_project, $id); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button">
                    <?php esc_html_e('Filtrar', 'metodologia-leitor-apreciador'); ?>
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($texts)): ?>
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Nenhum texto com a metodologia ativa encontrado.', 'metodologia-leitor-apreciador'); ?>
                <?php esc_html_e('Para ativar a metodologia em um texto, edite um post ou página e marque a opção na metabox "Metodologia Mateus 24".', 'metodologia-leitor-apreciador'); ?>
            </p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-title">
                        <?php esc_html_e('Título', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-type">
                        <?php esc_html_e('Tipo', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-project">
                        <?php esc_html_e('Projeto', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-responses">
                        <?php esc_html_e('Respostas', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-date">
                        <?php esc_html_e('Data', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <th scope="col" class="column-actions">
                        <?php esc_html_e('Ações', 'metodologia-leitor-apreciador'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($texts as $text): ?>
                    <?php
                    $project_id = get_post_meta($text->ID, '_mla_project_id', true);
                    $project_name = isset($projects[$project_id]) ? $projects[$project_id] : '—';
                    $text_id = get_post_meta($text->ID, '_mla_text_id', true);
                    $response_count = $texts_controller->get_response_count($text->ID);
                    ?>
                    <tr>
                        <td class="column-title">
                            <strong>
                                <a href="<?php echo esc_url(get_edit_post_link($text->ID)); ?>">
                                    <?php echo esc_html($text->post_title); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo esc_url(get_permalink($text->ID)); ?>" target="_blank">
                                        <?php esc_html_e('Ver', 'metodologia-leitor-apreciador'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="column-type">
                            <?php
                            $post_type = get_post_type_object($text->post_type);
                            echo esc_html($post_type->labels->singular_name);
                            ?>
                        </td>
                        <td class="column-project">
                            <?php echo esc_html($project_name); ?>
                        </td>
                        <td class="column-responses">
                            <?php if ($response_count > 0 && $text_id): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=mla-responses&text_id=' . $text_id)); ?>">
                                    <?php echo esc_html($response_count); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html($response_count); ?>
                            <?php endif; ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(get_the_date('', $text)); ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo esc_url(get_edit_post_link($text->ID)); ?>">
                                <?php esc_html_e('Editar', 'metodologia-leitor-apreciador'); ?>
                            </a>
                            <?php if ($text_id): ?>
                                |
                                <a href="<?php echo esc_url(admin_url('admin.php?page=mla-responses&text_id=' . $text_id)); ?>">
                                    <?php esc_html_e('Ver Respostas', 'metodologia-leitor-apreciador'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
    .column-type {
        width: 80px;
    }

    .column-project {
        width: 150px;
    }

    .column-responses {
        width: 80px;
        text-align: center;
    }

    .column-date {
        width: 100px;
    }

    .column-actions {
        width: 150px;
    }
</style>