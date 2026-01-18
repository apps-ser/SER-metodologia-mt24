<?php
/**
 * Serviço para gerenciamento de respostas no Supabase.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Responses_Service
 *
 * Gerencia operações CRUD de respostas com versionamento no Supabase.
 */
class MLA_Responses_Service
{

    /**
     * Nome da tabela de respostas no Supabase.
     */
    const TABLE_NAME = 'responses';

    /**
     * Nome da tabela de histórico no Supabase.
     */
    const HISTORY_TABLE = 'response_history';

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
     * Lista respostas com filtros.
     *
     * @param array $args Argumentos (project_id, text_id, wp_user_id, status, order, limit, offset).
     *
     * @return array|WP_Error Lista de respostas ou erro.
     */
    public function get_all($args = array())
    {
        $defaults = array(
            'project_id' => '',
            'text_id' => '',
            'wp_user_id' => '',
            'status' => '',
            'order' => 'updated_at.desc',
            'limit' => 50,
            'offset' => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $filters = array(
            'order' => $args['order'],
            'limit' => $args['limit'],
            'offset' => $args['offset'],
        );

        if (!empty($args['project_id'])) {
            $filters['project_id'] = 'eq.' . $args['project_id'];
        }

        if (!empty($args['text_id'])) {
            $filters['text_id'] = 'eq.' . $args['text_id'];
        }

        if (!empty($args['wp_user_id'])) {
            $filters['wp_user_id'] = 'eq.' . intval($args['wp_user_id']);
        }

        if (!empty($args['status'])) {
            $filters['status'] = 'eq.' . $args['status'];
        }

        return $this->client->get(self::TABLE_NAME, $filters, true);
    }

    /**
     * Obtém uma resposta pelo ID.
     *
     * @param string $id UUID da resposta.
     *
     * @return array|null|WP_Error Resposta ou null se não encontrada.
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
     * Obtém a resposta de um usuário para um texto específico.
     *
     * @param int    $wp_user_id ID do usuário WordPress.
     * @param string $text_id    UUID do texto.
     *
     * @return array|null|WP_Error Resposta ou null se não encontrada.
     */
    public function get_user_response($wp_user_id, $text_id)
    {
        $result = $this->client->get(self::TABLE_NAME, array(
            'wp_user_id' => 'eq.' . intval($wp_user_id),
            'text_id' => 'eq.' . $text_id,
        ), true); // Usa service key (admin) para ignorar RLS

        if (is_wp_error($result)) {
            return $result;
        }

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Salva uma resposta (cria ou atualiza como rascunho).
     *
     * @param array $data Dados da resposta.
     *
     * @return array|WP_Error Resposta salva ou erro.
     */
    public function save_draft($data)
    {
        $sanitized = $this->sanitize_data($data);
        $sanitized['updated_at'] = current_time('c');

        // Verificar se já existe resposta
        $existing = $this->get_user_response($sanitized['wp_user_id'], $sanitized['text_id']);

        if (is_wp_error($existing)) {
            return $existing;
        }

        if ($existing) {
            $update_data = array(
                'updated_at' => $sanitized['updated_at'],
            );

            // Mesclar dados existentes para evitar perda de campos não enviados (ex: etapas não renderizadas)
            $existing_data = isset($existing['data']) ? $existing['data'] : array();
            $new_data = $sanitized['data'];

            // Filtra nulos/vazios do novo payload antes de mesclar para não sobrescrever com nada
            $filtered_new_data = array_filter($new_data, function ($val) {
                return !empty($val);
            });

            $merged_data = array_merge($existing_data, $filtered_new_data);

            if ('submitted' === $existing['status']) {
                $update_data['draft_data'] = $merged_data;
            } else {
                $update_data['data'] = $merged_data;
            }

            $result = $this->client->patch(self::TABLE_NAME, array(
                'id' => 'eq.' . $existing['id'],
            ), $update_data, true);

            return is_array($result) && isset($result[0]) ? $result[0] : $result;
        } else {
            // Criar novo rascunho
            $sanitized['status'] = 'draft';
            $sanitized['version'] = 1;
            $result = $this->client->post(self::TABLE_NAME, $sanitized, true);
            return is_array($result) && isset($result[0]) ? $result[0] : $result;
        }
    }

    /**
     * Submete uma resposta (finaliza e versiona).
     *
     * @param string $id UUID da resposta.
     *
     * @return array|WP_Error Resposta submetida ou erro.
     */
    public function submit($id)
    {
        $existing = $this->get_by_id($id);

        if (is_wp_error($existing)) {
            return $existing;
        }

        if (!$existing) {
            return new WP_Error('mla_not_found', __('Resposta não encontrada.', 'metodologia-leitor-apreciador'));
        }

        $update_data = array(
            'status' => 'submitted',
            'updated_at' => current_time('c'),
        );

        // Se já foi submetida antes, incrementar versão e salvar histórico
        if ('submitted' === $existing['status']) {
            // Salvar versão anterior no histórico
            $this->save_to_history($existing);

            // Incrementar versão
            $new_version = intval($existing['version']) + 1;

            // Se houver um rascunho pendente (draft_data), ele se torna a versão oficial
            if (!empty($existing['draft_data'])) {
                $update_data['data'] = $existing['draft_data'];
                $update_data['draft_data'] = null; // Limpa o rascunho após submeter
            }
        } else {
            $new_version = 1;
        }

        $update_data['version'] = $new_version;

        // Atualizar para submetida
        $result = $this->client->patch(self::TABLE_NAME, array(
            'id' => 'eq.' . $id,
        ), $update_data, true);

        return is_array($result) && isset($result[0]) ? $result[0] : $result;
    }

    /**
     * Salva uma versão no histórico.
     *
     * @param array $response Resposta a ser salva no histórico.
     *
     * @return array|WP_Error Registro do histórico ou erro.
     */
    private function save_to_history($response)
    {
        $history_data = array(
            'response_id' => $response['id'],
            'version' => $response['version'],
            'data' => $response['data'],
            'submitted_at' => current_time('c'),
        );

        return $this->client->post(self::HISTORY_TABLE, $history_data, true);
    }

    /**
     * Obtém o histórico de versões de uma resposta.
     *
     * @param string $response_id UUID da resposta.
     *
     * @return array|WP_Error Lista de versões ou erro.
     */
    public function get_history($response_id)
    {
        return $this->client->get(self::HISTORY_TABLE, array(
            'response_id' => 'eq.' . $response_id,
            'order' => 'version.desc',
        ), true);
    }

    /**
     * Conta respostas por critérios.
     *
     * @param array $args Filtros.
     *
     * @return int Contagem.
     */
    public function count($args = array())
    {
        $filters = array(
            'select' => 'id',
        );

        if (!empty($args['project_id'])) {
            $filters['project_id'] = 'eq.' . $args['project_id'];
        }

        if (!empty($args['text_id'])) {
            $filters['text_id'] = 'eq.' . $args['text_id'];
        }

        if (!empty($args['status'])) {
            $filters['status'] = 'eq.' . $args['status'];
        }

        $result = $this->client->get(self::TABLE_NAME, $filters, true);

        if (is_wp_error($result)) {
            return 0;
        }

        return count($result);
    }

    /**
     * Sanitiza dados da resposta.
     *
     * @param array $data Dados brutos.
     *
     * @return array Dados sanitizados.
     */
    private function sanitize_data($data)
    {
        $sanitized = array();

        if (isset($data['text_id'])) {
            $sanitized['text_id'] = sanitize_text_field($data['text_id']);
        }

        if (isset($data['wp_user_id'])) {
            $sanitized['wp_user_id'] = intval($data['wp_user_id']);
        }

        if (isset($data['wp_user_email'])) {
            $sanitized['wp_user_email'] = sanitize_email($data['wp_user_email']);
        }

        if (isset($data['project_id'])) {
            $sanitized['project_id'] = !empty($data['project_id'])
                ? sanitize_text_field($data['project_id'])
                : null;
        }

        if (isset($data['data']) && is_array($data['data'])) {
            $sanitized['data'] = array(
                'tema_central' => isset($data['data']['tema_central'])
                    ? wp_kses_post($data['data']['tema_central']) : '',
                'temas_secundarios' => isset($data['data']['temas_secundarios'])
                    ? wp_kses_post($data['data']['temas_secundarios']) : '',
                'correlacao' => isset($data['data']['correlacao'])
                    ? wp_kses_post($data['data']['correlacao']) : '',
                'aspectos_positivos' => isset($data['data']['aspectos_positivos'])
                    ? wp_kses_post($data['data']['aspectos_positivos']) : '',
                'duvidas' => isset($data['data']['duvidas'])
                    ? wp_kses_post($data['data']['duvidas']) : '',
                'perguntas' => isset($data['data']['perguntas'])
                    ? wp_kses_post($data['data']['perguntas']) : '',
                'perguntas_paragrafos' => isset($data['data']['perguntas_paragrafos'])
                    ? $this->sanitize_paragraphs_data($data['data']['perguntas_paragrafos']) : array(),
            );
        }

        return $sanitized;
    }

    /**
     * Sanitiza os dados das perguntas por parágrafo.
     *
     * @param array $paragraphs_data Dados dos parágrafos.
     * @return array Dados sanitizados.
     */
    private function sanitize_paragraphs_data($paragraphs_data)
    {
        if (!is_array($paragraphs_data)) {
            return array();
        }

        $sanitized = array();
        foreach ($paragraphs_data as $id => $item) {
            $key = sanitize_text_field($id);

            // Suporta formato novo (array) ou antigo (string)
            if (is_array($item)) {
                $sanitized[$key] = array(
                    'question' => isset($item['question']) ? wp_kses_post($item['question']) : '',
                    'paragraph_text' => isset($item['paragraph_text']) ? wp_kses_post($item['paragraph_text']) : '',
                );
            } else {
                // Mantém compatibilidade com formato antigo (apenas a pergunta)
                $sanitized[$key] = wp_kses_post($item);
            }
        }
        return $sanitized;
    }

    /**
     * Exporta respostas em formato estruturado.
     *
     * @param array  $args   Filtros.
     * @param string $format Formato ('array', 'csv', 'json').
     *
     * @return array|string Dados exportados.
     */
    public function export($args = array(), $format = 'array')
    {
        $args['limit'] = 1000; // Limite maior para exportação
        $responses = $this->get_all($args);

        if (is_wp_error($responses)) {
            return $responses;
        }

        if ('array' === $format) {
            return $responses;
        }

        if ('json' === $format) {
            return wp_json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if ('csv' === $format) {
            return $this->to_csv($responses);
        }

        return $responses;
    }

    /**
     * Converte respostas para formato CSV.
     *
     * @param array $responses Lista de respostas.
     *
     * @return string CSV formatado.
     */
    private function to_csv($responses)
    {
        if (empty($responses)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Cabeçalhos
        fputcsv($output, array(
            'ID',
            'Usuario ID',
            'Usuario Email',
            'Texto ID',
            'Projeto ID',
            'Status',
            'Versao',
            'Tema Central',
            'Temas Secundarios',
            'Correlacao',
            'Aspectos Positivos',
            'Duvidas',
            'Perguntas',
            'Criado Em',
            'Atualizado Em',
            'Perguntas por Parágrafo',
        ));

        // Dados
        foreach ($responses as $response) {
            $data = isset($response['data']) ? $response['data'] : array();

            // Formatar perguntas por parágrafo para string
            $perguntas_paragrafos_str = '';
            if (isset($data['perguntas_paragrafos']) && is_array($data['perguntas_paragrafos'])) {
                foreach ($data['perguntas_paragrafos'] as $pid => $p_resp) {
                    if (!empty($p_resp)) {
                        // Verifica se é formato novo (array) ou antigo (string)
                        if (is_array($p_resp)) {
                            $question = isset($p_resp['question']) ? $p_resp['question'] : '';
                            $p_text = isset($p_resp['paragraph_text']) ? $p_resp['paragraph_text'] : '';

                            if (!empty($question)) {
                                $perguntas_paragrafos_str .= "[$pid] (Texto: $p_text)\nPergunta: $question\n\n";
                            }
                        } else {
                            $perguntas_paragrafos_str .= "[$pid]: $p_resp\n";
                        }
                    }
                }
            }

            fputcsv($output, array(
                $response['id'],
                $response['wp_user_id'],
                isset($response['wp_user_email']) ? $response['wp_user_email'] : '',
                $response['text_id'],
                isset($response['project_id']) ? $response['project_id'] : '',
                $response['status'],
                $response['version'],
                isset($data['tema_central']) ? $data['tema_central'] : '',
                isset($data['temas_secundarios']) ? $data['temas_secundarios'] : '',
                isset($data['correlacao']) ? $data['correlacao'] : '',
                isset($data['aspectos_positivos']) ? $data['aspectos_positivos'] : '',
                isset($data['duvidas']) ? $data['duvidas'] : '',
                isset($data['perguntas']) ? $data['perguntas'] : '',
                $response['created_at'],
                $response['updated_at'],
                $perguntas_paragrafos_str,
            ));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
