<?php
/**
 * Template: Informações de Debug
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}

// Verificar permissões
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'metodologia-leitor-apreciador'));
}

// Obter todos os projetos
$projects_service = new MLA_Projects_Service();
$projects = $projects_service->get_all();

// Obter tipos de post permitidos das configurações
$settings = get_option('mla_settings', array());
$allowed_post_types = isset($settings['allowed_post_types']) ? $settings['allowed_post_types'] : array('post', 'page');

// Obter todos os posts/páginas com MLA ativo
$args = array(
    'post_type' => $allowed_post_types,
    'posts_per_page' => 100,
    'meta_query' => array(
        array(
            'key' => '_mla_enabled',
            'value' => '1',
            'compare' => '=',
        ),
    ),
);

$query = new WP_Query($args);
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('Debug - Metodologia Leitor-Apreciador', 'metodologia-leitor-apreciador'); ?>
    </h1>
    <hr class="wp-header-end">

    <div
        style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-left: 4px solid #2271b1;">
        <h2>📊 Resumo</h2>
        <ul>
            <li><strong>Total de Projetos:</strong>
                <?php echo is_wp_error($projects) ? 0 : count($projects); ?>
            </li>
            <li><strong>Posts/Páginas com MLA Ativo:</strong>
                <?php echo $query->found_posts; ?>
            </li>
        </ul>
    </div>

    <?php if (!is_wp_error($projects) && !empty($projects)): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>📁 Projetos Cadastrados</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID do Projeto</th>
                        <th>Nome</th>
                        <th>Textos Associados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <?php
                        // Contar textos associados a este projeto
                        $text_args = array(
                            'post_type' => $allowed_post_types,
                            'posts_per_page' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => '_mla_enabled',
                                    'value' => '1',
                                    'compare' => '=',
                                ),
                                array(
                                    'key' => '_mla_project_id',
                                    'value' => $project['id'],
                                    'compare' => '=',
                                ),
                            ),
                        );
                        $text_query = new WP_Query($text_args);
                        ?>
                        <tr>
                            <td><code><?php echo esc_html($project['id']); ?></code></td>
                            <td>
                                <?php echo esc_html($project['name']); ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo $text_query->found_posts; ?>
                                </strong> texto(s)
                                <?php if ($text_query->have_posts()): ?>
                                    <ul style="margin-top: 10px;">
                                        <?php while ($text_query->have_posts()):
                                            $text_query->the_post(); ?>
                                            <li>
                                                <?php the_title(); ?>
                                                <small>(ID:
                                                    <?php the_ID(); ?>)
                                                </small>
                                            </li>
                                        <?php endwhile; ?>
                                        <?php wp_reset_postdata(); ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($query->have_posts()): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>📄 Todos os Posts/Páginas com MLA Ativo</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Post ID</th>
                        <th>Título</th>
                        <th>Text ID</th>
                        <th>Project ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($query->have_posts()):
                        $query->the_post(); ?>
                        <?php
                        $post_id = get_the_ID();
                        $text_id = get_post_meta($post_id, '_mla_text_id', true);
                        $project_id = get_post_meta($post_id, '_mla_project_id', true);
                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($post_id); ?>
                            </td>
                            <td>
                                <?php the_title(); ?>
                            </td>
                            <td><code><?php echo esc_html($text_id ?: 'N/A'); ?></code></td>
                            <td><code><?php echo esc_html($project_id ?: 'N/A'); ?></code></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="notice notice-warning">
            <p>
                <?php esc_html_e('Nenhum post ou página tem a Metodologia Leitor-Apreciador ativada.', 'metodologia-leitor-apreciador'); ?>
            </p>
        </div>
    <?php endif; ?>

    <div
        style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-left: 4px solid #dc3232;">
        <h2>⚠️ Solução de Problemas</h2>
        <p><strong>Se o filtro de textos não está funcionando:</strong></p>
        <ol>
            <li>Certifique-se de que existe pelo menos um post/página com MLA ativado</li>
            <li>Verifique se o post/página tem um Projeto associado</li>
            <li>Verifique se o post/página tem um Text ID gerado</li>
            <li>Se um texto não aparece na lista de um projeto, edite o post e salve novamente</li>
        </ol>
    </div>
</div>