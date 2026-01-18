<?php
/**
 * Template: Container do formulário.
 *
 * @package MetodologiaLeitorApreciador
 *
 * @var array $steps Etapas do formulário.
 */

if (!defined('WPINC')) {
    die;
}

$total_steps = count($steps);
$is_progressive = $this->is_progressive();
?>

<div id="mla-form-wrapper" class="mla-form-wrapper" data-progressive="<?php echo $is_progressive ? 'true' : 'false'; ?>"
    data-paragraph-questions-enabled="<?php echo isset($paragraph_questions_enabled) && $paragraph_questions_enabled ? 'true' : 'false'; ?>"
    data-paragraphs="<?php echo esc_attr(isset($paragraphs_json) ? $paragraphs_json : '[]'); ?>">
    <!-- Header -->
    <div class="mla-form-header">
        <h3 class="mla-form-title">
            <span class="mla-icon">📝</span>
            <?php esc_html_e('Metodologia Mateus 24', 'metodologia-leitor-apreciador'); ?>
        </h3>
        <p class="mla-form-intro">
            <?php esc_html_e('Sua reflexão sobre este texto contribui para a construção coletiva do conhecimento.', 'metodologia-leitor-apreciador'); ?>
        </p>
    </div>

    <!-- Barra de Progresso -->
    <?php if ($is_progressive): ?>
        <div class="mla-progress-bar">
            <div class="mla-progress-track">
                <div class="mla-progress-fill" style="width: <?php echo esc_attr((1 / $total_steps) * 100); ?>%"></div>
            </div>
            <div class="mla-progress-text">
                <span class="mla-current-step">1</span> /
                <?php echo esc_html($total_steps); ?> —
                <span class="mla-step-title">
                    <?php echo esc_html($steps[1]['title']); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status de salvamento -->
    <div class="mla-save-status" style="display: none;">
        <span class="mla-save-icon">✓</span>
        <span class="mla-save-text">
            <?php esc_html_e('Rascunho salvo automaticamente', 'metodologia-leitor-apreciador'); ?>
        </span>
    </div>

    <!-- Formulário -->
    <form id="mla-form" class="mla-form" novalidate>
        <?php foreach ($steps as $num => $step): ?>
            <div class="mla-step <?php echo 1 === $num ? 'mla-step-active' : ''; ?>"
                data-step="<?php echo esc_attr($num); ?>"
                data-key="<?php echo esc_attr(isset($step['key']) ? $step['key'] : ''); ?>">
                <div class="mla-step-header">
                    <h4 class="mla-step-title">
                        <?php echo esc_html($step['title']); ?>
                    </h4>
                    <p class="mla-step-description">
                        <?php echo esc_html($step['description']); ?>
                    </p>
                </div>
                <div class="mla-step-fields">
                    <?php foreach ($step['fields'] as $field): ?>
                        <div class="mla-field">
                            <label for="mla-<?php echo esc_attr($field['name']); ?>">
                                <?php echo esc_html($field['label']); ?>
                            </label>
                            <textarea id="mla-<?php echo esc_attr($field['name']); ?>"
                                name="<?php echo esc_attr($field['name']); ?>"
                                placeholder="<?php echo esc_attr($field['placeholder']); ?>" rows="5"
                                class="mla-textarea"></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Resumo (antes da submissão) -->
        <div class="mla-step mla-step-summary" data-step="summary" style="display: none;">
            <div class="mla-step-header">
                <h4 class="mla-step-title">
                    <?php esc_html_e('Revisão da Apreciação', 'metodologia-leitor-apreciador'); ?>
                </h4>
                <p class="mla-step-description">
                    <?php esc_html_e('Revise suas respostas antes de submeter.', 'metodologia-leitor-apreciador'); ?>
                </p>
            </div>
            <div class="mla-summary-content" id="mla-summary-content"></div>
            <div class="mla-submit-notice">
                <p>
                    <?php esc_html_e('Suas respostas poderão ser analisadas de forma coletiva e comparativa dentro do projeto.', 'metodologia-leitor-apreciador'); ?>
                </p>
            </div>
        </div>

        <!-- Navegação -->
        <div class="mla-form-navigation">
            <button type="button" class="mla-btn mla-btn-secondary mla-btn-prev" style="display: none;">
                ←
                <?php esc_html_e('Anterior', 'metodologia-leitor-apreciador'); ?>
            </button>
            <div class="mla-nav-spacer"></div>
            <button type="button" class="mla-btn mla-btn-primary mla-btn-next">
                <?php esc_html_e('Próximo', 'metodologia-leitor-apreciador'); ?> →
            </button>
            <button type="button" class="mla-btn mla-btn-secondary mla-btn-review" style="display: none;">
                ←
                <?php esc_html_e('Voltar e Revisar', 'metodologia-leitor-apreciador'); ?>
            </button>
            <button type="submit" class="mla-btn mla-btn-success mla-btn-submit" style="display: none;">
                ✓
                <?php esc_html_e('Submeter Apreciação', 'metodologia-leitor-apreciador'); ?>
            </button>
        </div>
    </form>

    <!-- Mensagem de sucesso -->
    <div class="mla-success-message" style="display: none;">
        <div class="mla-success-icon">✓</div>
        <h3>
            <?php esc_html_e('Apreciação Submetida!', 'metodologia-leitor-apreciador'); ?>
        </h3>
        <p>
            <?php esc_html_e('Sua reflexão foi registrada. Você pode retornar e aprofundar quando desejar.', 'metodologia-leitor-apreciador'); ?>
        </p>
        <button type="button" class="mla-btn mla-btn-secondary mla-btn-edit">
            <?php esc_html_e('Editar Respostas', 'metodologia-leitor-apreciador'); ?>
        </button>
    </div>
</div>