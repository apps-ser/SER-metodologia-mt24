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

        return $this->client->post(self::TABLE_NAME, $data, true);
    }

    /**
     * Obtém a análise mais recente de um texto.
     *
     * @param string $text_id ID do texto.
     *
     * @return array|null|WP_Error Análise ou null se não encontrada.
     */
    public function get_latest_by_text($text_id)
    {
        $result = $this->client->get(self::TABLE_NAME, array(
            'text_id' => 'eq.' . $text_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ), true);

        if (is_wp_error($result)) {
            return $result;
        }

        return !empty($result[0]) ? $result[0] : null;
    }
}
