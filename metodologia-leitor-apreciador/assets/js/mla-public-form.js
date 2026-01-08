/**
 * JavaScript Público - Formulário da Metodologia Leitor-Apreciador
 *
 * Implementa:
 * - Navegação progressiva entre etapas
 * - Auto-save com debounce
 * - Retomada de preenchimento
 * - Submissão explícita
 * - Indicadores visuais
 *
 * @package MetodologiaLeitorApreciador
 */

(function ($) {
    'use strict';

    // Estado da aplicação
    var state = {
        currentStep: 1,
        totalSteps: 5,
        responseId: null,
        status: 'new',
        isDirty: false,
        isSaving: false,
        lastSavedData: null,
        autosaveTimer: null
    };

    // Cache de elementos
    var $wrapper, $form, $steps, $progressFill, $progressText, $currentStepEl, $stepTitle;
    var $btnPrev, $btnNext, $btnReview, $btnSubmit, $btnEdit;
    var $saveStatus, $summaryContent, $successMessage;

    /**
     * Inicialização
     */
    function init() {
        // Primeiro, reposicionar o container do formulário para dentro do conteúdo do LearnDash
        relocateFormContainer();

        $wrapper = $('#mla-form-wrapper');

        if (!$wrapper.length) {
            return;
        }

        // Cache de elementos
        $form = $('#mla-form');
        $steps = $form.find('.mla-step[data-step]').not('[data-step="summary"]');
        $progressFill = $wrapper.find('.mla-progress-fill');
        $progressText = $wrapper.find('.mla-progress-text');
        $currentStepEl = $wrapper.find('.mla-current-step');
        $stepTitle = $wrapper.find('.mla-step-title');

        $btnPrev = $wrapper.find('.mla-btn-prev');
        $btnNext = $wrapper.find('.mla-btn-next');
        $btnReview = $wrapper.find('.mla-btn-review');
        $btnSubmit = $wrapper.find('.mla-btn-submit');
        $btnEdit = $wrapper.find('.mla-btn-edit');

        $saveStatus = $wrapper.find('.mla-save-status');
        $summaryContent = $('#mla-summary-content');
        $successMessage = $wrapper.find('.mla-success-message');

        state.totalSteps = $steps.length;

        // Verificar se é formulário progressivo
        var isProgressive = $wrapper.data('progressive') !== false;

        if (!isProgressive) {
            showAllSteps();
        }

        // Bind de eventos
        bindEvents();

        // Carregar resposta existente
        loadExistingResponse();
    }

    /**
     * Reposicionar o container do formulário para dentro do conteúdo correto
     * Isso resolve o problema de sobreposição com sidebars do LearnDash/BuddyBoss
     */
    function relocateFormContainer() {
        var $formContainer = $('.mla-form-container-wrapper');

        if (!$formContainer.length) {
            return;
        }

        // Tentar encontrar o container de conteúdo do LearnDash
        var $targetContainer = null;

        // Lista de seletores possíveis, em ordem de prioridade
        var selectors = [
            '#learndash-page-content .learndash_content_wrap',  // LearnDash Focus Mode
            '#learndash-page-content',                          // LearnDash
            '.learndash_content_wrap',                          // LearnDash alternativo
            '.entry-content',                                   // Tema padrão
            '#primary .site-main',                              // Estrutura comum
            'article .entry-content',                           // Post/page
            '#content'                                          // Fallback geral
        ];

        for (var i = 0; i < selectors.length; i++) {
            $targetContainer = $(selectors[i]);
            if ($targetContainer.length) {
                break;
            }
        }

        if ($targetContainer && $targetContainer.length) {
            // Mover o container do formulário para dentro do target
            $formContainer.appendTo($targetContainer);
        }
    }


    /**
     * Bind de eventos
     */
    function bindEvents() {
        // Navegação
        $btnPrev.on('click', prevStep);
        $btnNext.on('click', nextStep);
        $btnReview.on('click', function () {
            goToStep(state.totalSteps);
        });
        $btnEdit.on('click', editResponse);

        // Submissão
        $form.on('submit', function (e) {
            e.preventDefault();
            submitResponse();
        });

        // Auto-save em campos
        $form.find('.mla-textarea').on('input', function () {
            state.isDirty = true;
            debounceSave();
        });

        // Auto-save periódico
        if (mlaSettings.autosaveInterval > 0) {
            setInterval(function () {
                if (state.isDirty && !state.isSaving) {
                    saveResponse();
                }
            }, mlaSettings.autosaveInterval);
        }
    }

    /**
     * Carregar resposta existente
     */
    function loadExistingResponse() {
        if (!mlaSettings.textId) {
            return;
        }

        $.ajax({
            url: mlaSettings.restUrl + 'responses/by-text/' + mlaSettings.textId,
            method: 'GET',
            headers: {
                'X-WP-Nonce': mlaSettings.nonce
            },
            success: function (response) {
                if (response.success && response.exists && response.response) {
                    var data = response.response;
                    state.responseId = data.id;
                    state.status = data.status;
                    state.lastSavedData = data.data;

                    // Preencher campos
                    fillFormData(data.data);

                    // Mostrar aviso se já submetida
                    if (data.status === 'submitted') {
                        showEditWarning();
                    }
                }
            }
        });
    }

    /**
     * Preencher formulário com dados
     */
    function fillFormData(data) {
        if (!data) return;

        var fields = ['tema_central', 'temas_secundarios', 'correlacao',
            'aspectos_positivos', 'duvidas', 'perguntas'];

        fields.forEach(function (field) {
            if (data[field]) {
                $form.find('[name="' + field + '"]').val(data[field]);
            }
        });
    }

    /**
     * Mostrar aviso de edição
     */
    function showEditWarning() {
        if (confirm(mlaSettings.i18n.editWarning + '\n\n' + mlaSettings.i18n.continueEditing)) {
            // Continuar editando
        }
    }

    /**
     * Ir para etapa específica
     */
    function goToStep(step) {
        if (step < 1) step = 1;
        if (step > state.totalSteps) step = state.totalSteps;

        state.currentStep = step;
        updateUI();
    }

    /**
     * Próxima etapa
     */
    function nextStep() {
        if (state.currentStep < state.totalSteps) {
            goToStep(state.currentStep + 1);
        } else {
            showSummary();
        }
    }

    /**
     * Etapa anterior
     */
    function prevStep() {
        if (state.currentStep > 1) {
            goToStep(state.currentStep - 1);
        }
    }

    /**
     * Atualizar UI
     */
    function updateUI() {
        // Esconder todas as etapas
        $steps.removeClass('mla-step-active');

        // Mostrar etapa atual
        $steps.filter('[data-step="' + state.currentStep + '"]').addClass('mla-step-active');

        // Atualizar barra de progresso
        var progress = (state.currentStep / state.totalSteps) * 100;
        $progressFill.css('width', progress + '%');
        $currentStepEl.text(state.currentStep);

        // Atualizar título da etapa
        var stepData = $steps.filter('[data-step="' + state.currentStep + '"]').find('.mla-step-title').text();
        $stepTitle.text(stepData);

        // Controlar visibilidade dos botões
        $btnPrev.toggle(state.currentStep > 1);
        $btnNext.toggle(state.currentStep < state.totalSteps);
        $btnSubmit.hide();
        $btnReview.hide();

        // Na última etapa, mostrar revisar
        if (state.currentStep === state.totalSteps) {
            $btnNext.text(mlaSettings.i18n ? 'Revisar →' : 'Revisar →');
        } else {
            $btnNext.html('Próximo →');
        }

        // Esconder resumo e sucesso
        $wrapper.find('.mla-step-summary').hide();
        $successMessage.hide();
        $form.show();
    }

    /**
     * Mostrar resumo
     */
    function showSummary() {
        // Esconder etapas
        $steps.removeClass('mla-step-active');

        // Gerar resumo
        var summaryHtml = '';
        var fields = [
            { name: 'tema_central', label: 'Tema Central' },
            { name: 'temas_secundarios', label: 'Temas Secundários' },
            { name: 'correlacao', label: 'Correlação Doutrinária' },
            { name: 'aspectos_positivos', label: 'Aspectos Positivos' },
            { name: 'duvidas', label: 'Dúvidas Identificadas' },
            { name: 'perguntas', label: 'Perguntas Formuladas' }
        ];

        fields.forEach(function (field) {
            var value = $form.find('[name="' + field.name + '"]').val() || '—';
            summaryHtml += '<div class="mla-summary-item">';
            summaryHtml += '<h5>' + field.label + '</h5>';
            summaryHtml += '<p>' + escapeHtml(value) + '</p>';
            summaryHtml += '</div>';
        });

        $summaryContent.html(summaryHtml);

        // Mostrar seção de resumo
        $wrapper.find('.mla-step-summary').show();

        // Atualizar botões
        $btnPrev.hide();
        $btnNext.hide();
        $btnReview.show();
        $btnSubmit.show();

        // Atualizar progresso
        $progressFill.css('width', '100%');
        $currentStepEl.text(state.totalSteps);
        $stepTitle.text('Revisão');
    }

    /**
     * Mostrar todas as etapas (modo não-progressivo)
     */
    function showAllSteps() {
        $steps.addClass('mla-step-active');
        $wrapper.find('.mla-progress-bar').hide();
        $btnPrev.hide();
        $btnNext.hide();
        $btnSubmit.show();
    }

    /**
     * Coletar dados do formulário
     */
    function collectFormData() {
        return {
            tema_central: $form.find('[name="tema_central"]').val() || '',
            temas_secundarios: $form.find('[name="temas_secundarios"]').val() || '',
            correlacao: $form.find('[name="correlacao"]').val() || '',
            aspectos_positivos: $form.find('[name="aspectos_positivos"]').val() || '',
            duvidas: $form.find('[name="duvidas"]').val() || '',
            perguntas: $form.find('[name="perguntas"]').val() || ''
        };
    }

    /**
     * Debounce para auto-save
     */
    function debounceSave() {
        clearTimeout(state.autosaveTimer);
        state.autosaveTimer = setTimeout(function () {
            saveResponse();
        }, 1000);
    }

    /**
     * Salvar resposta (rascunho)
     */
    function saveResponse() {
        if (state.isSaving) return;
        if (!mlaSettings.textId) return;

        var formData = collectFormData();

        // Verificar se houve alteração
        if (JSON.stringify(formData) === JSON.stringify(state.lastSavedData)) {
            return;
        }

        state.isSaving = true;
        showSaveStatus('saving', mlaSettings.i18n.saving);

        $.ajax({
            url: mlaSettings.restUrl + 'responses',
            method: 'POST',
            headers: {
                'X-WP-Nonce': mlaSettings.nonce,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                text_id: mlaSettings.textId,
                project_id: mlaSettings.projectId || null,
                data: formData
            }),
            success: function (response) {
                if (response.success) {
                    state.responseId = response.response.id || state.responseId;
                    state.lastSavedData = formData;
                    state.isDirty = false;
                    showSaveStatus('saved', mlaSettings.i18n.saved);
                } else {
                    showSaveStatus('error', mlaSettings.i18n.error);
                }
            },
            error: function () {
                showSaveStatus('error', mlaSettings.i18n.error);
            },
            complete: function () {
                state.isSaving = false;
            }
        });
    }

    /**
     * Submeter resposta
     */
    function submitResponse() {
        // Salvar primeiro
        saveResponse();

        if (!state.responseId) {
            alert('Aguarde o salvamento do rascunho.');
            return;
        }

        // Confirmar submissão
        if (!confirm(mlaSettings.i18n.confirmSubmit)) {
            return;
        }

        $btnSubmit.prop('disabled', true).text(mlaSettings.i18n.submitting);

        $.ajax({
            url: mlaSettings.restUrl + 'responses/' + state.responseId + '/submit',
            method: 'POST',
            headers: {
                'X-WP-Nonce': mlaSettings.nonce
            },
            success: function (response) {
                if (response.success) {
                    state.status = 'submitted';
                    showSuccess();
                } else {
                    alert(response.message || mlaSettings.i18n.error);
                    $btnSubmit.prop('disabled', false).html('✓ Submeter Apreciação');
                }
            },
            error: function () {
                alert(mlaSettings.i18n.error);
                $btnSubmit.prop('disabled', false).html('✓ Submeter Apreciação');
            }
        });
    }

    /**
     * Mostrar mensagem de sucesso
     */
    function showSuccess() {
        $form.hide();
        $wrapper.find('.mla-step-summary').hide();
        $successMessage.show();
        $wrapper.find('.mla-progress-bar').hide();
    }

    /**
     * Editar resposta após submissão
     */
    function editResponse() {
        $successMessage.hide();
        $form.show();
        $wrapper.find('.mla-progress-bar').show();
        goToStep(1);
    }

    /**
     * Mostrar status de salvamento
     */
    function showSaveStatus(type, message) {
        $saveStatus
            .removeClass('saving error')
            .addClass(type)
            .find('.mla-save-text').text(message);

        $saveStatus.show();

        if (type === 'saved') {
            setTimeout(function () {
                $saveStatus.fadeOut();
            }, 3000);
        }
    }

    /**
     * Escapar HTML
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }

    // Inicializar quando DOM estiver pronto
    $(document).ready(init);

})(jQuery);
