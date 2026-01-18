<?php
/**
 * Serviço de integração com a API do OpenRouter para análise por IA.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_AI_Service
 *
 * Lida com chamadas à API do OpenRouter e processamento de prompts.
 */
class MLA_AI_Service
{

    /**
     * URL da API do OpenRouter.
     */
    const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * URL para listagem de modelos.
     */
    const API_MODELS_URL = 'https://openrouter.ai/api/v1/models';

    /**
     * Obtém a chave da API das configurações.
     */
    private function get_api_key()
    {
        $settings = get_option('mla_settings', array());
        return isset($settings['openrouter_api_key']) ? $settings['openrouter_api_key'] : '';
    }

    /**
     * Obtém o modelo configurado.
     */
    private function get_model()
    {
        $settings = get_option('mla_settings', array());
        return isset($settings['openrouter_model']) ? $settings['openrouter_model'] : 'openai/gpt-4o-mini';
    }

    /**
     * Obtém o prompt do sistema configurado.
     */
    private function get_system_prompt()
    {
        $settings = get_option('mla_settings', array());
        $default_prompt = "Você é um analista especializado na Metodologia Mateus 24. 
Sua tarefa é analisar um JSON contendo respostas de vários leitores sobre um texto específico.

Objetivos:
1. Agrupar Perguntas: Identifique perguntas que expressam a mesma dúvida ou ideia. Crie uma pergunta única, clara e profunda para cada grupo, fundindo as nuances. Cite o nome de todos os leitores que contribuíram para aquele grupo de ideias.
2. Perguntas Únicas: Mantenha perguntas que não possuem similares.
3. Síntese do Conteúdo: Gere um texto que sintetize e resuma as impressões, sentimentos e observações dos leitores sobre o texto original. Preserve informações valiosas e observações específicas de cada leitor, citando-os quando as ideias forem mencionadas.

Formato de Saída (Markdown):
# Análise das Respostas (IA)

## Perguntas para os Autores
(Lista de perguntas agrupadas e únicas com os nomes dos leitores)

## Síntese das Percepções
(Texto consolidado identificando as contribuições dos leitores)";

        return isset($settings['ai_system_prompt']) ? $settings['ai_system_prompt'] : $default_prompt;
    }

    /**
     * Realiza a análise das respostas via IA.
     *
     * @param array $responses_json Array de respostas em formato JSON.
     *
     * @return string|WP_Error Resultado da análise ou erro.
     */
    public function analyze_responses($responses_json, $context = array())
    {
        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            return new WP_Error('mla_ai_missing_key', __('Chave da API do OpenRouter não configurada.', 'metodologia-leitor-apreciador'));
        }

        $model = $this->get_model();
        $system_prompt = $this->get_system_prompt();

        $responses_toon = MLA_Toon::encode($responses_json);
        $user_content = "Aqui estão as respostas dos leitores para análise (formato TOON para economia de tokens):\n\n" . $responses_toon;

        if (!empty($context)) {
            $context_str = "\n\n--- CONTEXTO ADICIONAL ---\n";
            if (!empty($context['methodology'])) {
                $context_str .= "\nMETODOLOGIA:\n" . wp_strip_all_tags($context['methodology']) . "\n";
            }
            if (!empty($context['original_text'])) {
                $context_str .= "\nTEXTO ORIGINAL ANALISADO PELOS LEITORES:\n" . wp_strip_all_tags($context['original_text']) . "\n";
            }
            if (!empty($context['is_partial'])) {
                $context_str .= "\nNOTA: Esta é uma análise parcial de um lote de respostas. Concentre-se em extrair o máximo de valor deste grupo específico.\n";
            }
            $user_content = $context_str . "\n" . $user_content;
        }

        return $this->call_api($model, $system_prompt, $user_content);
    }

    /**
     * Consolida múltiplas análises parciais em uma única análise final.
     *
     * @param array $partial_results Array de strings (análises parciais).
     * @param array $context Contexto adicional.
     *
     * @return string|WP_Error
     */
    public function consolidate_partial_analyses($partial_results, $context = array())
    {
        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            return new WP_Error('mla_ai_missing_key', __('Chave da API do OpenRouter não configurada.', 'metodologia-leitor-apreciador'));
        }

        $model = $this->get_model();

        $system_prompt = "Você é o Consolidador mestre da Metodologia Mateus 24. 
Sua tarefa é receber várias análises parciais (feitas em lotes) e fundi-las em uma única Análise Final Coerente e Profunda.

Objetivos de Consolidação:
1. DE-DUPLICAÇÃO E RE-GRUPAMENTO: Analise todas as perguntas sugeridas em todos os lotes. Identifique temas recorrentes entre os lotes e crie grupos de perguntas ainda mais robustos e profundos. Não repita perguntas similares.
2. SÍNTESE GLOBAL: Una as sínteses de cada lote em um único texto fluido que represente a voz de todos os leitores. Evite redundâncias entre os lotes.
3. PRESERVAÇÃO DE CITAÇÕES: Mantenha as citações aos leitores originais em todo o texto final.

Formato de Saída: Mantenha rigorosamente o formato Markdown solicitado anteriormente (# Análise Final, ## Perguntas, ## Síntese).";

        $user_content = "Aqui estão as análises parciais obtidas dos diferentes lotes de leitores:\n\n";
        foreach ($partial_results as $i => $result) {
            $user_content .= "### LOTE " . ($i + 1) . ":\n" . $result . "\n\n---\n\n";
        }

        if (!empty($context['methodology'])) {
            $user_content .= "\n\nRELEMBRE A METODOLOGIA:\n" . wp_strip_all_tags($context['methodology']);
        }

        return $this->call_api($model, $system_prompt, $user_content);
    }

    /**
     * Faz a chamada real à API para evitar repetição de código.
     */
    private function call_api($model, $system_prompt, $user_content)
    {
        $api_key = $this->get_api_key();

        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $user_content
                )
            )
        );

        $response = wp_remote_post(self::API_URL, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => get_site_url(),
                'X-Title' => 'Metodologia Mateus 24 WP Plugin'
            ),
            'body' => json_encode($body),
            'timeout' => 90 // Aumentado para lidar com conteúdo maior
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (200 !== $status_code) {
            $error_msg = isset($result['error']['message']) ? $result['error']['message'] : __('Erro desconhecido na API do OpenRouter.', 'metodologia-leitor-apreciador');
            return new WP_Error('mla_ai_api_error', $error_msg);
        }

        return isset($result['choices'][0]['message']['content']) ? $result['choices'][0]['message']['content'] : '';
    }

    /**
     * Obtém a lista de modelos disponíveis no OpenRouter.
     *
     * @return array|WP_Error Lista de modelos [id => name] ou erro.
     */
    public function get_available_models()
    {
        $cache_key = 'mla_openrouter_models_cache';
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $response = wp_remote_get(self::API_MODELS_URL, array(
            'timeout' => 15,
            'headers' => array(
                'HTTP-Referer' => get_site_url(),
                'X-Title' => 'Metodologia Mateus 24 WP Plugin'
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (200 !== $status_code || !isset($data['data'])) {
            return new WP_Error('mla_ai_models_error', __('Erro ao buscar modelos do OpenRouter.', 'metodologia-leitor-apreciador'));
        }

        $models = array();
        foreach ($data['data'] as $model) {
            $models[$model['id']] = isset($model['name']) ? $model['name'] : $model['id'];
        }

        // Ordenar por nome
        asort($models);

        set_transient($cache_key, $models, DAY_IN_SECONDS);

        return $models;
    }
}
