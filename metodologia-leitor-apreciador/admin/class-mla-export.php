<?php
/**
 * Classe responsável pela exportação de dados.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Export
 *
 * Gerencia a exportação de respostas em CSV e JSON.
 */
class MLA_Export
{

    /**
     * Serviço de respostas.
     *
     * @var MLA_Responses_Service
     */
    private $responses_service;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->responses_service = new MLA_Responses_Service();

        // Hooks para exportação
        add_action('admin_init', array($this, 'handle_export_request'));
    }

    /**
     * Processa requisições de exportação.
     *
     * @return void
     */
    public function handle_export_request()
    {
        if (!isset($_GET['mla_export'])) {
            return;
        }

        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'mla_export_nonce')) {
            wp_die(esc_html__('Verificação de segurança falhou.', 'metodologia-leitor-apreciador'));
        }

        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão para exportar dados.', 'metodologia-leitor-apreciador'));
        }

        $format = sanitize_text_field(wp_unslash($_GET['mla_export']));

        // Filtros
        $filters = array(
            'project_id' => isset($_GET['project_id']) ? sanitize_text_field(wp_unslash($_GET['project_id'])) : '',
            'text_id' => isset($_GET['text_id']) ? sanitize_text_field(wp_unslash($_GET['text_id'])) : '',
            'status' => isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '',
        );

        switch ($format) {
            case 'csv':
                $this->export_csv($filters);
                break;
            case 'json':
                $this->export_json($filters);
                break;
            default:
                wp_die(esc_html__('Formato de exportação inválido.', 'metodologia-leitor-apreciador'));
        }
    }

    /**
     * Exporta dados em formato CSV.
     *
     * @param array $filters Filtros de exportação.
     *
     * @return void
     */
    private function export_csv($filters)
    {
        $csv = $this->responses_service->export($filters, 'csv');

        if (is_wp_error($csv)) {
            wp_die(esc_html($csv->get_error_message()));
        }

        $filename = 'mla-respostas-' . gmdate('Y-m-d-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM para UTF-8 no Excel
        echo "\xEF\xBB\xBF";
        echo $csv;

        exit;
    }

    /**
     * Exporta dados em formato JSON.
     *
     * @param array $filters Filtros de exportação.
     *
     * @return void
     */
    private function export_json($filters)
    {
        $json = $this->responses_service->export($filters, 'json');

        if (is_wp_error($json)) {
            wp_die(esc_html($json->get_error_message()));
        }

        $filename = 'mla-respostas-' . gmdate('Y-m-d-His') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $json;

        exit;
    }

    /**
     * Gera URL de exportação com nonce.
     *
     * @param string $format  Formato (csv ou json).
     * @param array  $filters Filtros opcionais.
     *
     * @return string URL de exportação.
     */
    public static function get_export_url($format, $filters = array())
    {
        $args = array_merge(
            array(
                'mla_export' => $format,
                '_wpnonce' => wp_create_nonce('mla_export_nonce'),
            ),
            $filters
        );

        return add_query_arg($args, admin_url('admin.php'));
    }
}

// Inicializar exportação
new MLA_Export();
