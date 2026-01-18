<?php
/**
 * Template: Mensagem de login obrigatório.
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="mla-login-required">
    <div class="mla-login-box">
        <span class="mla-login-icon">🔒</span>
        <h3>
            <?php esc_html_e('Participação no Estudo', 'metodologia-leitor-apreciador'); ?>
        </h3>
        <p>
            <?php esc_html_e('Para participar da Metodologia Mateus 24, é necessário estar conectado à sua conta.', 'metodologia-leitor-apreciador'); ?>
        </p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mla-login-button">
            <?php esc_html_e('Fazer Login', 'metodologia-leitor-apreciador'); ?>
        </a>
        <?php if (get_option('users_can_register')): ?>
            <p class="mla-register-link">
                <?php esc_html_e('Não tem conta?', 'metodologia-leitor-apreciador'); ?>
                <a href="<?php echo esc_url(wp_registration_url()); ?>">
                    <?php esc_html_e('Cadastre-se', 'metodologia-leitor-apreciador'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>