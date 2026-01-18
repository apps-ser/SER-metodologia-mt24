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

$current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'active';
$texts_controller = new MLA_Texts();
$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('Textos com Metodologia Ativa', 'metodologia-leitor-apreciador'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php
    if ('archived' === $message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Texto arquivado com sucesso.', 'metodologia-leitor-apreciador') . '</p></div>';
    } elseif ('restored' === $message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Texto restaurado com sucesso.', 'metodologia-leitor-apreciador') . '</p></div>';
    }
    ?>

    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo esc_url(add_query_arg(array('status' => 'active', 'paged' => 1), remove_query_arg('message'))); ?>"
                class="<?php echo 'active' === $current_status ? 'current' : ''; ?>">
                <?php esc_html_e('Ativos', 'metodologia-leitor-apreciador'); ?>
            </a> |
        </li>
        <li class="archived">
            <a href="<?php echo esc_url(add_query_arg(array('status' => 'archived', 'paged' => 1), remove_query_arg('message'))); ?>"
                class="<?php echo 'archived' === $current_status ? 'current' : ''; ?>">
                <?php esc_html_e('Arquivados', 'metodologia-leitor-apreciador'); ?>
            </a> |
        </li>
        <li class="all_status">
            <a href="<?php echo esc_url(add_query_arg(array('status' => 'all', 'paged' => 1), remove_query_arg('message'))); ?>"
                class="<?php echo 'all' === $current_status ? 'current' : ''; ?>">
                <?php esc_html_e('Todos', 'metodologia-leitor-apreciador'); ?>
            </a>
        </li>
    </ul>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="mla-texts">
            <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">

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
                <?php esc_html_e('Nenhum texto encontrado com os filtros selecionados.', 'metodologia-leitor-apreciador'); ?>
                <?php if ('active' === $current_status): ?>
                    <?php esc_html_e('Para ativar a metodologia em um texto, edite um post ou página e marque a opção na metabox "Metodologia Mateus 24".', 'metodologia-leitor-apreciador'); ?>
                <?php endif; ?>
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
                    // Fallback se não tiver meta, tentar buscar pelo ID via controller (pode ser lento em loop, melhor confiar no meta syncado)
            
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
                            <a href="<?php echo esc_url(get_edit_post_link($text->ID)); ?>" class="button button-small">
                                <?php esc_html_e('Editar', 'metodologia-leitor-apreciador'); ?>
                            </a>
                            <?php if ($text_id): ?>
                                <?php if ('active' === $current_status): ?>
                                    <button type="button" class="button button-small"
                                        onclick="mla_submit_action('archive', '<?php echo esc_attr($text_id); ?>')">
                                        <?php esc_html_e('Arquivar', 'metodologia-leitor-apreciador'); ?>
                                    </button>
                                <?php elseif ('archived' === $current_status): ?>
                                    <button type="button" class="button button-small"
                                        onclick="mla_submit_action('restore', '<?php echo esc_attr($text_id); ?>')">
                                        <?php esc_html_e('Restaurar', 'metodologia-leitor-apreciador'); ?>
                                    </button>
                                <?php else: ?>
                                    <!-- Modo 'Todos': Botão condicional seria ideal mas status do loop é misto. Simplificando: mostrar ambos ou checar a origem? 
                                    Como estamos listando WP posts, e o status 'archived' está no Supabase, precisariamos do status individial aqui.
                                    Para simplificar, no filtro 'Todos', não mostramos botões de arquivo/restauro ou assumimos ativo.
                                    Melhor: adicionar lógica para saber status individual.
                                    -->
                                <?php endif; ?>

                                <a href="<?php echo esc_url(admin_url('admin.php?page=mla-responses&text_id=' . $text_id)); ?>"
                                    class="button button-small">
                                    <?php esc_html_e('Respostas', 'metodologia-leitor-apreciador'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Formulário oculto para ações -->
    <form id="mla-action-form" method="post" style="display:none;">
        <?php wp_nonce_field('mla_text_action', 'mla_text_nonce'); ?>
        <input type="hidden" name="mla_action" id="mla-action-input">
        <input type="hidden" name="text_id" id="mla-id-input">
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
        width: 250px;
    }
</style>