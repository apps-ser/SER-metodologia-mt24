/**
 * JavaScript Público - Formulário da Metodologia Mateus 24
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

        // Cache de elementos base
        $form = $('#mla-form');

        // As etapas já são renderizadas pelo PHP no form-container.php
        // com base no get_steps() que já retorna o template correto.

        // Cache de elementos de navegação
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

                    // Prioriza draft_data (rascunho de edição de algo já submetido)
                    var displayData = data.draft_data || data.data;
                    state.lastSavedData = displayData;

                    // Preencher campos
                    fillFormData(displayData);

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

        $.each(data, function (key, value) {
            // Campos normais
            var $field = $form.find('[name="' + key + '"]');
            if ($field.length) {
                $field.val(value);
            }

            // Perguntas por Parágrafo
            if (key === 'perguntas_paragrafos' && typeof value === 'object') {
                $wrapper.data('saved-paragraphs-data', value);
                if (!$.isEmptyObject(value)) {
                    $wrapper.data('trigger-question-sim', true);
                }
            }
        });
    }
    /**
     * Mostrar aviso de edição
     */
    function showEditWarning() {
        showToast(mlaSettings.i18n.continueEditing, 'info');
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
        // Atualizar barra de progresso
        var progress = (state.currentStep / state.totalSteps) * 100;
        $progressFill.css('width', progress + '%');
        $currentStepEl.text(state.currentStep);

        // Atualizar classes dos steps
        $steps.removeClass('mla-step-active');
        var $currentStepContainer = $steps.filter('[data-step="' + state.currentStep + '"]');
        $currentStepContainer.addClass('mla-step-active');

        // Atualizar título da barra
        var title = $currentStepContainer.find('.mla-step-title').text();
        $stepTitle.text(title);

        // Verificar se é o step de perguntas por parágrafo
        renderParagraphQuestions($currentStepContainer);

        // Atualizar botões
        if (state.currentStep === 1) {
            $btnPrev.hide();
        } else {
            $btnPrev.show();
        }

        if (state.currentStep === state.totalSteps) {
            $btnNext.hide();
            $btnReview.show();
            $btnSubmit.show();
        } else {
            $btnNext.show();
            // $btnReview.hide(); // Mantemos o botão de revisão visível se quiser pular pro final? Não, fluxo linear.
            $btnReview.hide();
            $btnSubmit.hide();
        }

        // Scroll para o topo do formulário
        $('html, body').animate({
            scrollTop: $wrapper.offset().top - 100
        }, 500);
    }

    /**
     * Renderiza as perguntas por parágrafo se necessário
     */
    /**
     * Renderiza as perguntas por parágrafo se necessário
     */
    function renderParagraphQuestions($stepContainer) {
        var paragraphQuestionsEnabled = $wrapper.data('paragraph-questions-enabled') === true;
        var stepTitle = $stepContainer.find('.mla-step-title').text().trim();
        var currentStepKey = $stepContainer.data('key');

        // Verifica condições para renderizar
        var isTargetStep = (currentStepKey === 'perguntas_paragrafos' || stepTitle.includes('Perguntas por Parágrafo'));
        var alreadyRendered = $stepContainer.find('.mla-paragraph-questions-container').length > 0;

        if (!paragraphQuestionsEnabled || !isTargetStep || alreadyRendered) {
            return;
        }

        var paragraphs = getParagraphsData();
        if (!paragraphs.length) return;

        // Construção do HTML
        var html = '<div class="mla-paragraph-questions-container">';
        html += buildTriggerQuestionHtml();
        html += buildParagraphsListHtml(paragraphs);
        html += '</div>';

        $stepContainer.find('.mla-step-fields').append(html);

        // Inicialização do estado
        initializeParagraphState($stepContainer);
    }

    /**
     * Obtém os dados dos parágrafos do data-attribute
     */
    function getParagraphsData() {
        var paragraphs = $wrapper.data('paragraphs');
        if (typeof paragraphs === 'string') {
            try {
                paragraphs = JSON.parse(paragraphs);
            } catch (e) {
                paragraphs = [];
            }
        }
        return paragraphs || [];
    }

    /**
     * Gera HTML da pergunta gatilho
     */
    function buildTriggerQuestionHtml() {
        return '<div class="mla-field mla-trigger-question">' +
            '<label>Algum conceito ou argumento despertou questionamentos?</label>' +
            '<div class="mla-radio-group">' +
            '<label><input type="radio" name="mla_trigger_question" value="sim"> Sim</label>' +
            '<label><input type="radio" name="mla_trigger_question" value="nao" checked> Não</label>' +
            '</div>' +
            '</div>';
    }

    /**
     * Gera HTML da lista de parágrafos
     */
    function buildParagraphsListHtml(paragraphs) {
        var html = '<div class="mla-paragraphs-list" style="display:none; margin-top: 20px;">';

        $.each(paragraphs, function (i, p) {
            html += '<div class="mla-paragraph-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 4px;">';
            html += '<div class="mla-paragraph-text" style="font-style: italic; color: #555; margin-bottom: 10px; font-size: 0.9em;">"' + escapeHtml(p.content) + '"</div>';
            html += '<div class="mla-field">';
            html += '<label for="mla_p_' + p.id + '">Sua pergunta sobre este trecho:</label>';
            html += '<textarea id="mla_p_' + p.id + '" name="mla_paragraph_questions[' + p.id + ']" class="mla-textarea" rows="2" placeholder="Digite sua pergunta aqui..."></textarea>';
            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        return html;
    }

    /**
     * Inicializa estado e eventos dos parágrafos
     */
    function initializeParagraphState($stepContainer) {
        var $container = $stepContainer.find('.mla-paragraph-questions-container');
        var savedData = $wrapper.data('saved-paragraphs-data');
        var triggerSim = $wrapper.data('trigger-question-sim');

        // Restaurar dados salvos
        if (savedData) {
            $.each(savedData, function (pid, val) {
                // Suporte a formato novo (objeto) ou legado (string)
                var answerText = '';
                if (typeof val === 'object' && val !== null && val.question) {
                    answerText = val.question;
                } else if (typeof val === 'string') {
                    answerText = val;
                }
                $('#mla_p_' + pid).val(answerText);
            });
        }

        // Restaurar estado do trigger
        if (triggerSim) {
            $container.find('input[name="mla_trigger_question"][value="sim"]').prop('checked', true);
            $container.find('.mla-paragraphs-list').show();
        }

        // Bind eventos
        $container.find('input[name="mla_trigger_question"]').on('change', function () {
            if ($(this).val() === 'sim') {
                $container.find('.mla-paragraphs-list').slideDown();
            } else {
                $container.find('.mla-paragraphs-list').slideUp();
            }
        });
    }

    /**
     * Mostrar resumo
     */
    function showSummary() {
        // Esconder etapas
        $steps.removeClass('mla-step-active');

        // Gerar resumo baseado nas etapas enviadas ou no fallback legado
        var summaryHtml = '';
        var summaryFields = [];

        if (mlaSettings.steps && mlaSettings.steps.length) {
            mlaSettings.steps.forEach(function (step) {
                if (step.fields) {
                    step.fields.forEach(function (f) {
                        summaryFields.push({ name: f.name, label: f.label || step.title });
                    });
                }
            });
        } else {
            summaryFields = [
                { name: 'tema_central', label: 'Tema Central' },
                { name: 'temas_secundarios', label: 'Temas Secundários' },
                { name: 'correlacao', label: 'Correlação Doutrinária' },
                { name: 'aspectos_positivos', label: 'Aspectos Positivos' },
                { name: 'duvidas', label: 'Dúvidas Identificadas' },
                { name: 'perguntas', label: 'Perguntas Formuladas' }
            ];
        }

        summaryFields.forEach(function (field) {
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
        var data = {};

        // Campos padrão
        $form.find('textarea:not([name^="mla_paragraph_questions"])').each(function () {
            var name = $(this).attr('name');
            var value = $(this).val();
            if (name) {
                data[name] = value;
            }
        });

        // Perguntas por Parágrafo
        var paragraphQuestions = {};

        // Cache dos dados originais dos parágrafos
        var paragraphsData = getParagraphsData();
        var paragraphsMap = {};
        if (paragraphsData && paragraphsData.length) {
            paragraphsData.forEach(function (p) {
                paragraphsMap[p.id] = p.content;
            });
        }

        $form.find('textarea[name^="mla_paragraph_questions"]').each(function () {
            var val = $(this).val();
            if (val && val.trim() !== '') {
                // Extrair ID do name="mla_paragraph_questions[p1]"
                var name = $(this).attr('name');
                var match = name.match(/\[(.*?)\]/);
                if (match && match[1]) {
                    var pId = match[1];
                    // Salva objeto com pergunta e texto original
                    paragraphQuestions[pId] = {
                        question: val,
                        paragraph_text: paragraphsMap[pId] || ''
                    };
                }
            }
        });

        if (!$.isEmptyObject(paragraphQuestions)) {
            data['perguntas_paragrafos'] = paragraphQuestions;
        }

        return data;
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
        var formData = collectFormData();

        // Mostrar carregando no botão
        $btnSubmit.prop('disabled', true).text(mlaSettings.i18n.submitting);

        // Primeiro garante que o rascunho mais atual está salvo
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

                    // Agora sim, procede com a submissão
                    proceedToSubmit();
                } else {
                    showToast(mlaSettings.i18n.error, 'error');
                    $btnSubmit.prop('disabled', false).html(mlaSettings.i18n.submitButton);
                }
            },
            error: function () {
                showToast(mlaSettings.i18n.error, 'error');
                $btnSubmit.prop('disabled', false).html(mlaSettings.i18n.submitButton);
            }
        });
    }

    /**
     * Procede com a submissão final após garantir o rascunho
     */
    function proceedToSubmit() {
        showConfirm(
            mlaSettings.i18n.confirmTitle,
            mlaSettings.i18n.confirmSubmit,
            function () {
                $.ajax({
                    url: mlaSettings.restUrl + 'responses/' + state.responseId + '/submit',
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': mlaSettings.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            state.status = 'submitted';
                            showSuccess(response.learndash_completed);
                            showToast(mlaSettings.i18n.submitted, 'success');
                        } else {
                            showToast(response.message || mlaSettings.i18n.error, 'error');
                            $btnSubmit.prop('disabled', false).html(mlaSettings.i18n.submitButton);
                        }
                    },
                    error: function () {
                        showToast(mlaSettings.i18n.error, 'error');
                        $btnSubmit.prop('disabled', false).html(mlaSettings.i18n.submitButton);
                    }
                });
            },
            function () {
                // Se cancelar a confirmação, reabilitar o botão
                $btnSubmit.prop('disabled', false).html(mlaSettings.i18n.submitButton);
            }
        );
    }

    /**
     * Mostrar mensagem de sucesso
     */
    function showSuccess(learndashCompleted) {
        $form.hide();
        $wrapper.find('.mla-step-summary').hide();
        $successMessage.show();
        $wrapper.find('.mla-progress-bar').hide();

        if (learndashCompleted) {
            $successMessage.find('p').first().after('<p class="mla-ld-notice"><strong>✓ ' + mlaSettings.i18n.learndashCompletion + '</strong></p>');
        }

        // Resetar estado do botão submit para uso futuro
        $btnSubmit.prop('disabled', false).html(mlaSettings.i18n.submitButton);
    }

    /**
     * Editar resposta após submissão
     */
    function editResponse() {
        $successMessage.hide();
        $form.show();
        $wrapper.find('.mla-progress-bar').show();

        // Garantir que o botão submit esteja resetado ao voltar a editar
        $btnSubmit.prop('disabled', false).html(mlaSettings.i18n.submitButton);

        goToStep(1);
    }

    /**
     * Mostrar status de salvamento
     */
    function showSaveStatus(type, message) {
        // Se for erro, mostra Toast também
        if (type === 'error') {
            showToast(message, 'error');
        }

        $saveStatus
            .removeClass('saving error')
            .addClass(type)
            .find('.mla-save-text').text(message);

        $saveStatus.fadeIn();

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
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }

    /**
     * Mostrar Toast Notification
     */
    function showToast(message, type) {
        type = type || 'info';

        var $toastContainer = $('.mla-toast-container');
        if (!$toastContainer.length) {
            $toastContainer = $('<div class="mla-toast-container"></div>').appendTo('body');
        }

        var $toast = $('<div class="mla-toast ' + type + '">' + escapeHtml(message) + '</div>');

        $toastContainer.append($toast);

        // Remover após 4 segundos
        setTimeout(function () {
            $toast.fadeOut(300, function () {
                $(this).remove();
            });
        }, 4000);
    }

    /**
     * Mostrar Modal de Confirmação
     */
    function showConfirm(title, message, onConfirm, onCancel) {
        var $overlay = $('<div class="mla-modal-overlay"></div>');
        var $modal = $('<div class="mla-modal"></div>');

        var html = '<h4>' + escapeHtml(title) + '</h4>';
        html += '<p>' + escapeHtml(message) + '</p>';
        html += '<div class="mla-modal-actions">';
        html += '<button class="mla-btn mla-btn-secondary mla-btn-cancel">' + escapeHtml(mlaSettings.i18n.cancel) + '</button>';
        html += '<button class="mla-btn mla-btn-primary mla-btn-confirm">' + escapeHtml(mlaSettings.i18n.confirm) + '</button>';
        html += '</div>';

        $modal.html(html);
        $overlay.append($modal).appendTo('body');

        // Focar no botão confirmar
        $modal.find('.mla-btn-confirm').focus();

        // Eventos
        var $btnCancel = $modal.find('.mla-btn-cancel');
        var $btnConfirm = $modal.find('.mla-btn-confirm');

        $btnCancel.on('click', function () {
            $overlay.fadeOut(200, function () { $(this).remove(); });
            if (typeof onCancel === 'function') {
                onCancel();
            }
        });

        $btnConfirm.on('click', function () {
            $overlay.fadeOut(200, function () { $(this).remove(); });
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }

    // Inicializar quando DOM estiver pronto
    $(document).ready(init);

})(jQuery);
