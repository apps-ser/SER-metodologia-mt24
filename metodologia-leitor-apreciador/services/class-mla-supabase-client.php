<?php
/**
 * Cliente HTTP para integração com Supabase.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Supabase_Client
 *
 * Gerencia todas as requisições HTTP para a API REST do Supabase.
 */
class MLA_Supabase_Client
{

    /**
     * URL base do Supabase.
     *
     * @var string
     */
    private $base_url;

    /**
     * Chave anônima do Supabase (para operações públicas).
     *
     * @var string
     */
    private $anon_key;

    /**
     * Chave de serviço do Supabase (para operações admin).
     *
     * @var string
     */
    private $service_key;

    /**
     * Instância singleton.
     *
     * @var MLA_Supabase_Client
     */
    private static $instance = null;

    /**
     * Obtém a instância singleton do cliente.
     *
     * @return MLA_Supabase_Client
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor privado para singleton.
     */
    private function __construct()
    {
        $this->base_url = defined('MLA_SUPABASE_URL') ? MLA_SUPABASE_URL : '';
        $this->anon_key = defined('MLA_SUPABASE_ANON_KEY') ? MLA_SUPABASE_ANON_KEY : '';
        $this->service_key = defined('MLA_SUPABASE_SERVICE_KEY') ? MLA_SUPABASE_SERVICE_KEY : '';
    }

    /**
     * Verifica se o cliente está configurado corretamente.
     *
     * @return bool True se configurado.
     */
    public function is_configured()
    {
        return !empty($this->base_url) && !empty($this->anon_key);
    }

    /**
     * Obtém os headers padrão para requisições.
     *
     * @param bool $use_service_key Usar chave de serviço (admin).
     *
     * @return array Headers HTTP.
     */
    private function get_headers($use_service_key = false)
    {
        $api_key = $use_service_key && !empty($this->service_key)
            ? $this->service_key
            : $this->anon_key;

        return array(
            'apikey' => $api_key,
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        );
    }

    /**
     * Monta a URL completa para uma tabela.
     *
     * @param string $table  Nome da tabela.
     * @param array  $params Parâmetros de query (filtros).
     *
     * @return string URL completa.
     */
    private function build_url($table, $params = array())
    {
        $url = trailingslashit($this->base_url) . 'rest/v1/' . $table;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Executa uma requisição GET (SELECT).
     *
     * @param string $table   Nome da tabela.
     * @param array  $filters Filtros no formato PostgREST.
     * @param bool   $admin   Usar chave de serviço.
     *
     * @return array|WP_Error Dados ou erro.
     */
    public function get($table, $filters = array(), $admin = false)
    {
        if (!$this->is_configured()) {
            return new WP_Error('mla_supabase_not_configured', __('Supabase não está configurado.', 'metodologia-leitor-apreciador'));
        }

        $url = $this->build_url($table, $filters);

        $response = wp_remote_get($url, array(
            'headers' => $this->get_headers($admin),
            'timeout' => 30,
        ));

        return $this->handle_response($response);
    }

    /**
     * Executa uma requisição POST (INSERT).
     *
     * @param string $table Nome da tabela.
     * @param array  $data  Dados a inserir.
     * @param bool   $admin Usar chave de serviço.
     *
     * @return array|WP_Error Dados inseridos ou erro.
     */
    public function post($table, $data, $admin = false)
    {
        if (!$this->is_configured()) {
            return new WP_Error('mla_supabase_not_configured', __('Supabase não está configurado.', 'metodologia-leitor-apreciador'));
        }

        $url = $this->build_url($table);

        $response = wp_remote_post($url, array(
            'headers' => $this->get_headers($admin),
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ));

        return $this->handle_response($response);
    }

    /**
     * Executa uma requisição PATCH (UPDATE).
     *
     * @param string $table   Nome da tabela.
     * @param array  $filters Filtros para identificar registros (ex: ['id' => 'eq.xxx']).
     * @param array  $data    Dados a atualizar.
     * @param bool   $admin   Usar chave de serviço.
     *
     * @return array|WP_Error Dados atualizados ou erro.
     */
    public function patch($table, $filters, $data, $admin = false)
    {
        if (!$this->is_configured()) {
            return new WP_Error('mla_supabase_not_configured', __('Supabase não está configurado.', 'metodologia-leitor-apreciador'));
        }

        $url = $this->build_url($table, $filters);

        $response = wp_remote_request($url, array(
            'method' => 'PATCH',
            'headers' => $this->get_headers($admin),
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ));

        return $this->handle_response($response);
    }

    /**
     * Executa uma requisição DELETE.
     *
     * @param string $table   Nome da tabela.
     * @param array  $filters Filtros para identificar registros.
     * @param bool   $admin   Usar chave de serviço.
     *
     * @return bool|WP_Error True em sucesso ou erro.
     */
    public function delete($table, $filters, $admin = false)
    {
        if (!$this->is_configured()) {
            return new WP_Error('mla_supabase_not_configured', __('Supabase não está configurado.', 'metodologia-leitor-apreciador'));
        }

        $url = $this->build_url($table, $filters);

        $response = wp_remote_request($url, array(
            'method' => 'DELETE',
            'headers' => $this->get_headers($admin),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            $this->log_error('DELETE ' . $table, $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 200 && $code < 300) {
            return true;
        }

        $body = wp_remote_retrieve_body($response);
        $error = json_decode($body, true);

        $this->log_error('DELETE ' . $table, $body);

        return new WP_Error(
            'mla_supabase_error',
            isset($error['message']) ? $error['message'] : __('Erro ao deletar registro.', 'metodologia-leitor-apreciador')
        );
    }

    /**
     * Trata a resposta da requisição HTTP.
     *
     * @param array|WP_Error $response Resposta do wp_remote_*.
     *
     * @return array|WP_Error Dados ou erro.
     */
    private function handle_response($response)
    {
        if (is_wp_error($response)) {
            $this->log_error('HTTP Request', $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code >= 200 && $code < 300) {
            $data = json_decode($body, true);
            return is_array($data) ? $data : array();
        }

        $error = json_decode($body, true);
        $this->log_error('HTTP ' . $code, $body);

        return new WP_Error(
            'mla_supabase_error',
            isset($error['message']) ? $error['message'] : __('Erro na comunicação com Supabase.', 'metodologia-leitor-apreciador'),
            array('status' => $code)
        );
    }

    /**
     * Registra erros no log do WordPress.
     *
     * @param string $context Contexto do erro.
     * @param string $message Mensagem de erro.
     *
     * @return void
     */
    private function log_error($context, $message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'MLA Supabase Error [%s]: %s',
                $context,
                $message
            ));
        }
    }

    /**
     * Executa um upsert (INSERT ou UPDATE).
     *
     * @param string $table           Nome da tabela.
     * @param array  $data            Dados a inserir/atualizar.
     * @param string $conflict_column Coluna para detectar conflito.
     * @param bool   $admin           Usar chave de serviço.
     *
     * @return array|WP_Error Dados ou erro.
     */
    public function upsert($table, $data, $conflict_column = 'id', $admin = false)
    {
        if (!$this->is_configured()) {
            return new WP_Error('mla_supabase_not_configured', __('Supabase não está configurado.', 'metodologia-leitor-apreciador'));
        }

        $url = $this->build_url($table);

        $headers = $this->get_headers($admin);
        $headers['Prefer'] = 'resolution=merge-duplicates,return=representation';

        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ));

        return $this->handle_response($response);
    }
}
