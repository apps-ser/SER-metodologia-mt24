<?php
/**
 * Serviço para gerenciamento de projetos no Supabase.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Projects_Service
 *
 * Gerencia operações CRUD de projetos no Supabase.
 */
class MLA_Projects_Service
{

    /**
     * Nome da tabela no Supabase.
     */
    const TABLE_NAME = 'projects';

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
     * Lista todos os projetos.
     *
     * @param array $args Argumentos opcionais (status, order, limit).
     *
     * @return array|WP_Error Lista de projetos ou erro.
     */
    public function get_all($args = array())
    {
        $defaults = array(
            'status' => '',
            'order' => 'created_at.desc',
            'limit' => 100,
        );
        $args = wp_parse_args($args, $defaults);

        $filters = array(
            'order' => $args['order'],
            'limit' => $args['limit'],
        );

        if (!empty($args['status'])) {
            $filters['status'] = 'eq.' . $args['status'];
        }

        return $this->client->get(self::TABLE_NAME, $filters, true);
    }

    /**
     * Obtém um projeto pelo ID.
     *
     * @param string $id UUID do projeto.
     *
     * @return array|null|WP_Error Projeto ou null se não encontrado.
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
     * Cria um novo projeto.
     *
     * @param array $data Dados do projeto (name, description, status).
     *
     * @return array|WP_Error Projeto criado ou erro.
     */
    public function create($data)
    {
        $sanitized = $this->sanitize_data($data);

        if (empty($sanitized['name'])) {
            return new WP_Error('mla_missing_name', __('Nome do projeto é obrigatório.', 'metodologia-leitor-apreciador'));
        }

        $result = $this->client->post(self::TABLE_NAME, $sanitized, true);

        if (!is_wp_error($result)) {
            // Limpar cache
            delete_transient('mla_projects_cache');
        }

        return is_array($result) && isset($result[0]) ? $result[0] : $result;
    }

    /**
     * Atualiza um projeto existente.
     *
     * @param string $id   UUID do projeto.
     * @param array  $data Dados a atualizar.
     *
     * @return array|WP_Error Projeto atualizado ou erro.
     */
    public function update($id, $data)
    {
        $sanitized = $this->sanitize_data($data);
        $sanitized['updated_at'] = current_time('c');

        $result = $this->client->patch(self::TABLE_NAME, array(
            'id' => 'eq.' . $id,
        ), $sanitized, true);

        if (!is_wp_error($result)) {
            delete_transient('mla_projects_cache');
        }

        return is_array($result) && isset($result[0]) ? $result[0] : $result;
    }

    /**
     * Deleta um projeto.
     *
     * @param string $id UUID do projeto.
     *
     * @return bool|WP_Error True em sucesso ou erro.
     */
    public function delete($id)
    {
        $result = $this->client->delete(self::TABLE_NAME, array(
            'id' => 'eq.' . $id,
        ), true);

        if (!is_wp_error($result)) {
            delete_transient('mla_projects_cache');
        }

        return $result;
    }

    /**
     * Obtém projetos para uso em select (id => name).
     *
     * @return array Lista de projetos para select.
     */
    public function get_for_select()
    {
        $cached = get_transient('mla_projects_cache');

        if (false !== $cached) {
            return $cached;
        }

        $projects = $this->get_all(array('status' => 'active'));

        if (is_wp_error($projects)) {
            return array();
        }

        $options = array();
        foreach ($projects as $project) {
            $options[$project['id']] = $project['name'];
        }

        set_transient('mla_projects_cache', $options, HOUR_IN_SECONDS);

        return $options;
    }

    /**
     * Sanitiza dados do projeto.
     *
     * @param array $data Dados brutos.
     *
     * @return array Dados sanitizados.
     */
    private function sanitize_data($data)
    {
        $sanitized = array();

        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($data['description']);
        }

        if (isset($data['status'])) {
            $sanitized['status'] = in_array($data['status'], array('active', 'archived'), true)
                ? $data['status']
                : 'active';
        }

        return $sanitized;
    }
}
