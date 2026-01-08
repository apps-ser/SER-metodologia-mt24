jQuery(document).ready(function ($) {
    const $editor = $('#mla-templates-editor');
    const $textarea = $('#mla_step_templates_json');
    if (!$editor.length || !$textarea.length) return;

    // Estado inicial
    let templates = [];
    try {
        templates = JSON.parse($textarea.val() || '[]');
    } catch (e) {
        console.error('Erro ao fazer parse dos templates:', e);
        templates = [];
    }

    // Função de renderização principal
    function render() {
        $editor.empty();

        templates.forEach((tpl, tplIndex) => {
            const $tplItem = $('<div class="mla-template-item postbox"></div>');

            // Header do Template
            const $header = $('<div class="postbox-header"></div>');
            $header.append($('<h2 class="hndle"></h2>').text(tpl.name || 'Novo Modelo'));

            // Ações do Template (Remover)
            const $actions = $('<div class="handle-actions"></div>');
            const $btnRemoveTpl = $('<button type="button" class="button-link text-error">Remover Modelo</button>');
            $btnRemoveTpl.on('click', () => {
                if (confirm(mlaSettingsData.i18n.confirmRemove)) {
                    templates.splice(tplIndex, 1);
                    update();
                }
            });
            $actions.append($btnRemoveTpl);
            $header.append($actions);
            $tplItem.append($header);

            // Corpo do Template
            const $content = $('<div class="inside"></div>');

            // Campo Nome do Template
            const $nameRow = $('<div class="mla-form-row"></div>');
            $nameRow.append('<label>Nome do Modelo:</label>');
            const $nameInput = $('<input type="text" class="regular-text" value="' + (tpl.name || '') + '">');
            $nameInput.on('input', (e) => {
                tpl.name = $(e.target).val();
                $header.find('h2').text(tpl.name);
                serialize(); // Salva sem renderizar tudo de novo se possível (mas aqui input pode perder foco se renderizar)
            });
            $nameRow.append($nameInput);
            $content.append($nameRow);

            $content.append('<hr>');
            $content.append('<h3>Etapas</h3>');

            // Lista de Etapas
            const $stepsList = $('<div class="mla-steps-list"></div>');

            (tpl.steps || []).forEach((step, stepIndex) => {
                const $stepItem = $('<div class="mla-step-item"></div>');

                // Header da Etapa (Drag Handle + Title Perview)
                $stepItem.append('<span class="dashicons dashicons-move drag-handle"></span>');

                const $inputs = $('<div class="step-inputs"></div>');

                // Linha 1: Chave e Título
                const $row1 = $('<div class="step-input-row"></div>');

                // Chave
                $row1.append('<div><label>' + mlaSettingsData.i18n.stepKey + '</label><input type="text" class="small-text code step-key" value="' + (step.key || '') + '"></div>');

                // Título
                $row1.append('<div><label>' + mlaSettingsData.i18n.stepTitle + '</label><input type="text" class="regular-text step-title" value="' + (step.title || '') + '"></div>');

                $inputs.append($row1);

                // Linha 2: Descrição
                const $row2 = $('<div class="step-input-row"></div>');
                $row2.append('<div><label>' + mlaSettingsData.i18n.stepDesc + '</label><textarea class="large-text step-desc" rows="2">' + (step.description || '') + '</textarea></div>');
                $inputs.append($row2);

                $stepItem.append($inputs);

                // Botão Remover Etapa
                const $btnRemoveStep = $('<button type="button" class="button button-small button-link-delete"><span class="dashicons dashicons-trash"></span></button>');
                $btnRemoveStep.on('click', () => {
                    if (confirm(mlaSettingsData.i18n.confirmRemove)) {
                        tpl.steps.splice(stepIndex, 1);
                        update();
                    }
                });
                $stepItem.append($btnRemoveStep);

                // Bind events inputs
                $stepItem.find('.step-key').on('change', (e) => {
                    step.key = $(e.target).val();
                    serialize();
                });
                $stepItem.find('.step-title').on('input', (e) => {
                    step.title = $(e.target).val();
                    serialize();
                });
                $stepItem.find('.step-desc').on('input', (e) => {
                    step.description = $(e.target).val();
                    serialize();
                });

                $stepsList.append($stepItem);
            });

            // Sortable
            $stepsList.sortable({
                handle: '.drag-handle',
                update: function () {
                    // Reconstroi array de steps baseado na DOM
                    const newSteps = [];
                    $(this).find('.mla-step-item').each(function () {
                        newSteps.push({
                            key: $(this).find('.step-key').val(),
                            title: $(this).find('.step-title').val(),
                            description: $(this).find('.step-desc').val()
                        });
                    });
                    tpl.steps = newSteps;
                    serialize();
                }
            });

            $content.append($stepsList);

            // Botão Adicionar Etapa
            const $btnAddStep = $('<button type="button" class="button">' + mlaSettingsData.i18n.addStep + '</button>');
            $btnAddStep.on('click', () => {
                const newIdx = (tpl.steps || []).length + 1;
                if (!tpl.steps) tpl.steps = [];
                tpl.steps.push({
                    key: 'step_' + newIdx,
                    title: 'Nova Etapa ' + newIdx,
                    description: ''
                });
                update();
            });
            $content.append($btnAddStep);

            $tplItem.append($content);
            $editor.append($tplItem);
        });

        // Botão Novo Template (Geral)
        const $btnNewTpl = $('<button type="button" class="button button-primary button-large">Criar Novo Modelo</button>');
        $btnNewTpl.on('click', () => {
            templates.push({
                id: 'tpl_' + Date.now(),
                name: 'Novo Modelo',
                steps: []
            });
            update();
        });
        $editor.append('<br>');
        $editor.append($btnNewTpl);
    }

    function serialize() {
        $textarea.val(JSON.stringify(templates));
    }

    function update() {
        if (templates.length === 0) {
            // Se deletar tudo, deixar um array vazio
        }
        serialize();
        render();
    }

    // Inicializar
    render();
});
