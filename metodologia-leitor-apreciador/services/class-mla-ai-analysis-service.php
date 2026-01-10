<?php
/**
 * Serviço para persistência de análises de IA no Supabase.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_AI_Analysis_Service
 *
 * Gerencia o armazenamento de resultados de IA no Supabase.
 */
class MLA_AI_Analysis_Service
{

    /**
     * Nome da tabela no Supabase.
     */
    const TABLE_NAME = 'ai_analyses';

    /**
     * Cliente Supabase.
     *
     * @var MLA_Supabase_Client
     */
    private $client;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->client = MLA_Supabase_Client::get_instance();
    }

    /**
     * Salva uma nova análise.
     *
     * @param string $text_id ID do texto (UUID do Supabase).
     * @param string $content Conteúdo da análise (Markdown).
     * @param string $model   Modelo de IA utilizado.
     *
     * @return array|WP_Error Resultado da criação ou erro.
     */
    public function save($text_id, $content, $model)
    {
        $data = array(
            'text_id' => $text_id,
            'content' => $content,
            'model' => $model,
            'created_at' => current_time('c'),
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MLA Save Analysis attempt: text_id=' . $text_id . ', model=' . $model);
        }

        $result = $this->client->post(self::TABLE_NAME, $data, true);

        if (is_wp_error($result)) {
            error_log('MLA Save Analysis FAILED: ' . $result->get_error_message());
        } else {
            error_log('MLA Save Analysis SUCCESS');
        }

        return $result;
    }

    /**
     * Obtém todas as análises de um texto específico.
     *
     * @param string $text_id ID do texto.
     *
     * @return array|WP_Error Lista de análises ou erro.
     */
    public function get_all_by_text($text_id)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MLA Fetch Analysis attempt: text_id=' . $text_id);
        }

        $result = $this->client->get(self::TABLE_NAME, array(
            'text_id' => 'eq.' . $text_id,
            'order' => 'created_at.desc'
        ), true);

        if (is_wp_error($result)) {
            error_log('MLA Fetch Analysis FAILED: ' . $result->get_error_message());
        } else {
            error_log('MLA Fetch Analysis SUCCESS: Found ' . (is_array($result) ? count($result) : 0) . ' records');
        }

        return $result;
    }

    /**
     * Atualiza uma análise existente.
     *
     * @param string $id   ID da análise (UUID do Supabase).
     * @param array  $data Dados a serem atualizados (ex: array('content' => '...')).
     *
     * @return array|WP_Error Resultado ou erro.
     */
    public function update($id, $data)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MLA Update Analysis attempt: id=' . $id);
        }

        $result = $this->client->patch(
            self::TABLE_NAME,
            array('id' => 'eq.' . $id),
            $data,
            true
        );

        if (is_wp_error($result)) {
            error_log('MLA Update Analysis FAILED: ' . $result->get_error_message());
        } else {
            error_log('MLA Update Analysis SUCCESS');
        }

        return $result;
    }
}
