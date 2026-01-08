<?php
/**
 * Serviço para gerenciamento de textos no Supabase.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Texts_Service
 *
 * Gerencia operações CRUD de textos (posts com metodologia ativa) no Supabase.
 */
class MLA_Texts_Service
{

    /**
     * Nome da tabela no Supabase.
     */
    const TABLE_NAME = 'texts';

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
     * Lista todos os textos.
     *
     * @param array $args Argumentos opcionais (project_id, order, limit).
     *
     * @return array|WP_Error Lista de textos ou erro.
     */
    public function get_all($args = array())
    {
        $defaults = array(
            'project_id' => '',
            'order' => 'created_at.desc',
            'limit' => 100,
        );
        $args = wp_parse_args($args, $defaults);

        $filters = array(
            'order' => $args['order'],
            'limit' => $args['limit'],
        );

        if (!empty($args['project_id'])) {
            $filters['project_id'] = 'eq.' . $args['project_id'];
        }

        return $this->client->get(self::TABLE_NAME, $filters, true);
    }

    /**
     * Obtém um texto pelo ID.
     *
     * @param string $id UUID do texto.
     *
     * @return array|null|WP_Error Texto ou null se não encontrado.
     */
    public function get_by_id($id)
    {
        $result = $this->client->get(self::TABLE_NAME, array(
            'id' => 'eq.' . $id,
        ), true);

        if (is_wp_error($result)) {
            return $result;
        }

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Obtém um texto pelo ID do post WordPress.
     *
     * @param int $wp_post_id ID do post no WordPress.
     *
     * @return array|null|WP_Error Texto ou null se não encontrado.
     */
    public function get_by_wp_post_id($wp_post_id)
    {
        $result = $this->client->get(self::TABLE_NAME, array(
            'wp_post_id' => 'eq.' . intval($wp_post_id),
        ), true);

        if (is_wp_error($result)) {
            return $result;
        }

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Cria ou atualiza um texto (sincroniza com post WordPress).
     *
     * @param int    $wp_post_id ID do post no WordPress.
     * @param string $title      Título do post.
     * @param string $project_id UUID do projeto (opcional).
     *
     * @return array|WP_Error Texto criado/atualizado ou erro.
     */
    public function sync($wp_post_id, $title, $project_id = null)
    {
        $existing = $this->get_by_wp_post_id($wp_post_id);

        if (is_wp_error($existing)) {
            return $existing;
        }

        $data = array(
            'wp_post_id' => intval($wp_post_id),
            'title' => sanitize_text_field($title),
            'project_id' => !empty($project_id) ? $project_id : null,
        );

        if ($existing) {
            // Atualizar
            return $this->client->patch(self::TABLE_NAME, array(
                'id' => 'eq.' . $existing['id'],
            ), $data, true);
        } else {
            // Criar
            $result = $this->client->post(self::TABLE_NAME, $data, true);
            return is_array($result) && isset($result[0]) ? $result[0] : $result;
        }
    }

    /**
     * Deleta um texto pelo ID do post WordPress.
     *
     * @param int $wp_post_id ID do post no WordPress.
     *
     * @return bool|WP_Error True em sucesso ou erro.
     */
    public function delete_by_wp_post_id($wp_post_id)
    {
        return $this->client->delete(self::TABLE_NAME, array(
            'wp_post_id' => 'eq.' . intval($wp_post_id),
        ), true);
    }

    /**
     * Conta respostas por texto.
     *
     * @param string $text_id UUID do texto.
     *
     * @return int Número de respostas.
     */
    public function count_responses($text_id)
    {
        $responses = $this->client->get('responses', array(
            'text_id' => 'eq.' . $text_id,
            'select' => 'id',
        ), true);

        if (is_wp_error($responses)) {
            return 0;
        }

        return count($responses);
    }
}
