<?php
/**
 * Template: Detalhe da resposta.
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}

$data = isset($response['data']) ? $response['data'] : array();
?>

<div class="wrap">
    <h1>
        <?php esc_html_e('Detalhes da Resposta', 'metodologia-leitor-apreciador'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mla-responses')); ?>" class="page-title-action">←
        <?php esc_html_e('Voltar', 'metodologia-leitor-apreciador'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="mla-detail-grid">
        <!-- Informações -->
        <div class="mla-detail-card">
            <h3>
                <?php esc_html_e('Informações', 'metodologia-leitor-apreciador'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Usuário', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <td>
                        <?php echo esc_html($user ? $user->display_name : '#' . $response['wp_user_id']); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Email', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <td>
                        <?php echo esc_html($response['wp_user_email']); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Status', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <td><span class="mla-badge mla-<?php echo esc_attr($response['status']); ?>">
                            <?php echo esc_html($response['status']); ?>
                        </span></td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Versão', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <td>v
                        <?php echo esc_html($response['version']); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Criado em', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <td>
                        <?php echo esc_html(wp_date('d/m/Y H:i', strtotime($response['created_at']))); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Atualizado em', 'metodologia-leitor-apreciador'); ?>
                    </th>
                    <td>
                        <?php echo esc_html(wp_date('d/m/Y H:i', strtotime($response['updated_at']))); ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Respostas -->
        <div class="mla-detail-card mla-responses-content">
            <h3>
                <?php esc_html_e('Conteúdo da Apreciação', 'metodologia-leitor-apreciador'); ?>
            </h3>

            <div class="mla-field">
                <h4>
                    <?php esc_html_e('Tema Central', 'metodologia-leitor-apreciador'); ?>
                </h4>
                <div class="mla-field-content">
                    <?php echo wp_kses_post(nl2br(isset($data['tema_central']) ? $data['tema_central'] : '')); ?>
                </div>
            </div>

            <div class="mla-field">
                <h4>
                    <?php esc_html_e('Temas Secundários', 'metodologia-leitor-apreciador'); ?>
                </h4>
                <div class="mla-field-content">
                    <?php echo wp_kses_post(nl2br(isset($data['temas_secundarios']) ? $data['temas_secundarios'] : '')); ?>
                </div>
            </div>

            <div class="mla-field">
                <h4>
                    <?php esc_html_e('Correlação Doutrinária', 'metodologia-leitor-apreciador'); ?>
                </h4>
                <div class="mla-field-content">
                    <?php echo wp_kses_post(nl2br(isset($data['correlacao']) ? $data['correlacao'] : '')); ?>
                </div>
            </div>

            <div class="mla-field">
                <h4>
                    <?php esc_html_e('Aspectos Positivos', 'metodologia-leitor-apreciador'); ?>
                </h4>
                <div class="mla-field-content">
                    <?php echo wp_kses_post(nl2br(isset($data['aspectos_positivos']) ? $data['aspectos_positivos'] : '')); ?>
                </div>
            </div>

            <div class="mla-field">
                <h4>
                    <?php esc_html_e('Dúvidas Identificadas', 'metodologia-leitor-apreciador'); ?>
                </h4>
                <div class="mla-field-content">
                    <?php echo wp_kses_post(nl2br(isset($data['duvidas']) ? $data['duvidas'] : '')); ?>
                </div>
            </div>

            <div class="mla-field">
                <h4>
                    <?php esc_html_e('Perguntas Formuladas', 'metodologia-leitor-apreciador'); ?>
                </h4>
                <div class="mla-field-content">
                    <?php echo wp_kses_post(nl2br(isset($data['perguntas']) ? $data['perguntas'] : '')); ?>
                </div>
            </div>
        </div>

        <!-- Histórico -->
        <?php if (!empty($history) && !is_wp_error($history)): ?>
            <div class="mla-detail-card">
                <h3>
                    <?php esc_html_e('Histórico de Versões', 'metodologia-leitor-apreciador'); ?>
                </h3>
                <ul>
                    <?php foreach ($history as $h): ?>
                        <li>v
                            <?php echo esc_html($h['version']); ?> -
                            <?php echo esc_html(wp_date('d/m/Y H:i', strtotime($h['submitted_at']))); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .mla-detail-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 20px;
        margin-top: 20px
    }

    .mla-detail-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 15px;
        border-radius: 4px
    }

    .mla-detail-card h3 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px
    }

    .mla-field {
        margin-bottom: 20px
    }

    .mla-field h4 {
        margin: 0 0 8px;
        color: #1d2327
    }

    .mla-field-content {
        background: #f6f7f7;
        padding: 12px;
        border-radius: 4px;
        min-height: 40px
    }

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

    @media(max-width:782px) {
        .mla-detail-grid {
            grid-template-columns: 1fr
        }
    }
</style>