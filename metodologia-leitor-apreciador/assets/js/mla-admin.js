/**
 * JavaScript Admin - Metodologia Mateus 24
 *
 * @package MetodologiaLeitorApreciador
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Confirmação de exclusão
        $('.button-link-delete').on('click', function () {
            return confirm(mlaAdmin.i18n.confirmDelete);
        });

        // Auto-dismiss notices
        setTimeout(function () {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);

        const $projectFilter = $('#mla-filter-project');
        const $textFilter = $('#mla-filter-text');
        const $aiAnalyzeBtn = $('#mla-analyze-ia');
        const $viewHistoryBtn = $('#mla-view-history');

        // Toggle AI buttons based on text selection
        function toggleAIButtons() {
            const textId = $textFilter.val();
            if (textId && textId !== '') {
                // Enable buttons
                $aiAnalyzeBtn.prop('disabled', false).removeAttr('disabled').attr('data-text-id', textId);
                $viewHistoryBtn.prop('disabled', false).removeAttr('disabled').attr('data-text-id', textId);
                $viewHistoryBtn.removeClass('disabled').css({ 'pointer-events': 'auto', 'opacity': '1' });
            } else {
                // Disable buttons
                $aiAnalyzeBtn.prop('disabled', true).attr('disabled', 'disabled').attr('data-text-id', '');
                $viewHistoryBtn.prop('disabled', true).attr('disabled', 'disabled').attr('data-text-id', '');
                $viewHistoryBtn.addClass('disabled').css({ 'pointer-events': 'none', 'opacity': '0.5' });
            }
        }

        // Initial check
        toggleAIButtons();

        // Listener for Project change to update Text filter
        $projectFilter.on('change', function () {
            const projectId = $(this).val();

            // Show loading state in text filter
            $textFilter.prop('disabled', true).find('option:not(:first)').remove();
            $textFilter.find('option:first').text('Carregando...');

            $.post(mlaAdmin.ajaxUrl, {
                action: 'mla_get_texts_by_project',
                project_id: projectId,
                nonce: mlaAdmin.nonce
            }, function (response) {
                if (response.success) {
                    $textFilter.find('option:first').text('Todos os textos');
                    $.each(response.data.texts, function (id, title) {
                        $textFilter.append($('<option>', { value: id, text: title }));
                    });
                } else {
                    $textFilter.find('option:first').text('Erro ao carregar');
                }
            }).fail(function () {
                $textFilter.find('option:first').text('Erro ao carregar');
            }).always(function () {
                $textFilter.prop('disabled', false);
                toggleAIButtons();
            });
        });

        // Listener for Text change
        $textFilter.on('change', toggleAIButtons);

        // Histórico de Análises
        // Histórico de Análises (Manager)
        let easyMDE = null;
        let currentAnalyses = [];
        let currentAnalysisId = null;

        $('#mla-view-history').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);

            if ($btn.hasClass('disabled') || $btn.prop('disabled')) return;

            const textId = $btn.attr('data-text-id');
            const $modal = $('#mla-history-modal');
            const $listView = $('#mla-history-list-view');
            const $listContent = $('#mla-history-list-content');
            const $editorView = $('#mla-history-editor-view');

            if (!textId) return;

            // Reset views
            $listView.show();
            $editorView.hide();

            $modal.dialog({
                modal: true,
                width: 900,
                height: 700,
                buttons: {}
            });

            // Show loading state
            $listContent.html('<div class="mla-loading-history" style="padding:20px; text-align:center;">' +
                '<span class="spinner is-active" style="float:none; margin:0 10px 0 0;"></span>' +
                'Carregando histórico...' +
                '</div>');

            // AJAX call to get history
            $.post(mlaAdmin.ajaxUrl, {
                action: 'mla_get_analysis_history',
                text_id: textId,
                nonce: mlaAdmin.nonce
            }, function (response) {
                if (response && response.success && response.data && response.data.analyses) {
                    currentAnalyses = response.data.analyses;
                    renderHistoryList(currentAnalyses);
                } else {
                    const msg = (response && response.data && response.data.message) ? response.data.message : 'Erro ao carregar histórico.';
                    console.error('MLA: Erro ao carregar histórico:', response);
                    $listContent.html('<div class="notice notice-error"><p>' + msg + '</p></div>');
                }
            }).fail(function (xhr, status, error) {
                console.error('MLA: Falha na requisição AJAX:', status, error);
                $listContent.html('<div class="notice notice-error"><p>Erro de conexão ao servidor. Verifique os logs se o problema persistir.</p></div>');
            });
        });

        // Render History List
        function renderHistoryList(analyses) {
            const $listContent = $('#mla-history-list-content');

            if (!analyses || analyses.length === 0) {
                $listContent.html('<div class="mla-empty-state"><div class="mla-empty-icon"><span class="dashicons dashicons-search"></span></div><h2>Nenhuma análise encontrada</h2><p>Ainda não existem análises de IA para este texto.</p></div>');
                return;
            }

            let html = '<table class="wp-list-table widefat fixed striped hovered">';
            html += '<thead><tr><th>Data/Hora</th><th>Modelo</th><th>Ações</th></tr></thead>';
            html += '<tbody>';

            analyses.forEach(function (analysis, index) {
                if (!analysis) return;

                const date = analysis.created_at ? new Date(analysis.created_at) : new Date();
                const dateStr = date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

                let model = 'AI';
                if (analysis.model && typeof analysis.model === 'string') {
                    model = analysis.model.split('/').pop().toUpperCase();
                }

                html += '<tr>';
                html += '<td>' + dateStr + '</td>';
                html += '<td><span class="mla-model-badge">' + model + '</span></td>';
                html += '<td><button type="button" class="button button-small mla-view-analysis-btn" data-index="' + index + '">Abrir / Editar</button></td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $listContent.html(html);
        }

        // Open Editor Handler (Delegate)
        $(document).on('click', '.mla-view-analysis-btn', function () {
            const index = $(this).data('index');
            const analysis = currentAnalyses[index];
            currentAnalysisId = analysis.id;

            // Switch views
            $('#mla-history-list-view').hide();
            $('#mla-history-editor-view').show();

            // Set Header Info
            const date = new Date(analysis.created_at);
            const dateStr = date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            $('#mla-editor-date').text(dateStr);
            $('#mla-editor-model').text(analysis.model.split('/').pop().toUpperCase());

            // Init EasyMDE if not already done
            const textarea = document.getElementById('mla-analysis-editor');
            if (!easyMDE) {
                easyMDE = new EasyMDE({
                    element: textarea,
                    spellChecker: false,
                    autosave: { enabled: false },
                    toolbar: ["bold", "italic", "heading", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "|", "guide"]
                });
            }

            // Set content
            easyMDE.value(analysis.content);

            // Refresh editor to ensure proper rendering after showing
            setTimeout(function () {
                easyMDE.codemirror.refresh();
            }, 100);
        });

        // Back Handler
        $('#mla-editor-back').on('click', function () {
            $('#mla-history-editor-view').hide();
            $('#mla-history-list-view').show();
        });

        // Helper to show status message within modal
        function showStatus(msg, type) {
            const $status = $('#mla-editor-status');
            $status.html('<div class="notice notice-' + type + ' inline is-dismissible" style="margin: 10px 0;"><p>' + msg + '</p></div>').fadeIn();

            // Auto-hide after 5 seconds if success
            if (type === 'success') {
                setTimeout(function () { $status.fadeOut(); }, 5000);
            }
        }

        // Save Handler
        $('#mla-editor-save').on('click', function () {
            const $btn = $(this);
            const newContent = easyMDE.value();

            $btn.prop('disabled', true).text(mlaAdmin.i18n.saving);
            $('#mla-editor-status').hide().empty(); // Clear previous status

            $.post(mlaAdmin.ajaxUrl, {
                action: 'mla_save_analysis',
                analysis_id: currentAnalysisId,
                content: newContent,
                nonce: mlaAdmin.nonce
            }, function (response) {
                if (response.success) {
                    $btn.text(mlaAdmin.i18n.saved);
                    showStatus(response.data.message || 'Salvo com sucesso!', 'success');
                    setTimeout(function () { $btn.text('Salvar Alterações').prop('disabled', false); }, 2000);

                    // Update local cache
                    const analysis = currentAnalyses.find(a => a.id === currentAnalysisId);
                    if (analysis) analysis.content = newContent;

                } else {
                    showStatus(response.data.message || mlaAdmin.i18n.error, 'error');
                    $btn.text('Erro ao Salvar').prop('disabled', false);
                }
            }).fail(function (xhr) {
                const errorText = (xhr.responseText && xhr.responseText.includes('message')) ? JSON.parse(xhr.responseText).message : mlaAdmin.i18n.error;
                showStatus(errorText, 'error');
                $btn.text('Erro de Conexão').prop('disabled', false);
            });
        });

        // Export Handler
        $('#mla-editor-export').on('click', function () {
            const content = easyMDE.value();
            const blob = new Blob([content], { type: 'text/markdown;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const dateStr = new Date().toISOString().slice(0, 10);

            a.href = url;
            a.download = 'analise-ia-' + dateStr + '.md';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });

        // Análise por IA
        $('#mla-analyze-ia').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const textId = $btn.data('text-id');
            const $modal = $('#mla-ai-modal');
            const $content = $('#mla-ai-result-content');

            if (!textId) return;

            // Disable button and show loading state
            $btn.prop('disabled', true).addClass('updating-message');

            // Open Modal immediately to show "Processing"
            $modal.dialog({
                modal: true,
                width: 800,
                height: 600,
                buttons: {
                    "Fechar": function () {
                        $(this).dialog("close");
                    }
                },
                close: function () {
                    $btn.prop('disabled', false).removeClass('updating-message');
                }
            });

            // Reset modal content with detailed processing message
            $content.html('<div class="mla-ai-processing">' +
                '<p><strong>' + mlaAdmin.i18n.processingAI + '</strong></p>' +
                '<p>' + mlaAdmin.i18n.dontCloseTip + '</p>' +
                '<div class="mla-progress-bar"><div class="mla-progress-value" style="width: 100%;"></div></div>' +
                '</div>');

            // AJAX call
            $.post(mlaAdmin.ajaxUrl, {
                action: 'mla_analyze_responses',
                text_id: textId,
                nonce: mlaAdmin.nonce
            }, function (response) {
                if (response.success) {
                    const historyUrl = mlaAdmin.adminUrl + 'admin.php?page=mla-responses&view=analyses&text_id=' + encodeURIComponent(textId);
                    $content.html('<div class="mla-ai-result">' +
                        formatMarkdown(response.data.content) +
                        '</div>' +
                        '<div class="mla-modal-actions">' +
                        '<a href="' + historyUrl + '" class="button button-primary">' +
                        '<span class="dashicons dashicons-backup" style="margin-top:4px; margin-right:5px;"></span>' +
                        'Ver no Histórico Completo</a>' +
                        '</div>');
                } else {
                    const errorMsg = response.data && response.data.message ? response.data.message : 'Erro desconhecido';
                    $content.html('<div class="notice notice-error"><p><strong>Erro na Análise:</strong> ' + errorMsg + '</p></div>');
                }
            }).fail(function (xhr) {
                let failMsg = mlaAdmin.i18n.errorAI;
                if (xhr.status === 504) failMsg += ' (Timeout da requisição)';
                $content.html('<div class="notice notice-error"><p>' + failMsg + '</p></div>');
            }).always(function () {
                $btn.prop('disabled', false).removeClass('updating-message');
            });
        });

        function formatMarkdown(text) {
            if (!text) return '';

            // Basic Markdown Parser
            let html = text
                .replace(/^# (.*$)/gm, '<h1>$1</h1>')
                .replace(/^## (.*$)/gm, '<h2>$1</h2>')
                .replace(/^### (.*$)/gm, '<h3>$1</h3>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/^\* (.*$)/gm, '<li>$1</li>')
                .replace(/^- (.*$)/gm, '<li>$1</li>')
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>');

            // Wrap lists
            html = html.replace(/(<li>.*<\/li>)/gms, '<ul>$1</ul>');

            return '<p>' + html + '</p>';
        }
    });

})(jQuery);
