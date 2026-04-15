<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Connettore per Google Gemini API — con Function Calling
 */
class SiteGenie_Gemini extends SiteGenie_API_Connector {

    private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Costruisce l'URL dell'API e gli header con la chiave nell'header HTTP
     */
    private function build_request( string $model ): array {
        return [
            'url'     => $this->api_base . $model . ':generateContent',
            'headers' => [ 'x-goog-api-key' => $this->api_key ],
        ];
    }

    /**
     * Genera testo semplice (senza tool use) — usato per metabox e test
     */
    public function generate( string $prompt, array $options = [] ): array {
        if ( empty( $this->api_key ) ) {
            return $this->format_error( 'API key Gemini non configurata.' );
        }

        $model   = $options['model'] ?? $this->model;
        $request = $this->build_request( $model );

        $body = [
            'contents' => [
                [ 'role' => 'user', 'parts' => [ [ 'text' => $prompt ] ] ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens']  ?? 2048,
                'temperature'     => $options['temperature'] ?? 0.7,
            ],
        ];

        $response = $this->http_post( $request['url'], $body, $request['headers'] );

        if ( ! $response['success'] ) {
            SiteGenie_Logger::log( 'gemini', 0, 0, 'error', $response['error'] );
            return $this->format_error( $response['error'], $response['code'] );
        }

        $data = $response['data'];
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ( empty( $text ) ) {
            $error = 'Risposta vuota da Gemini.';
            SiteGenie_Logger::log( 'gemini', 0, 0, 'error', $error );
            return $this->format_error( $error );
        }

        $pt = $data['usageMetadata']['promptTokenCount']     ?? 0;
        $ct = $data['usageMetadata']['candidatesTokenCount'] ?? 0;
        SiteGenie_Logger::log( 'gemini', $pt, $ct, 'success' );

        return $this->format_response( $text, $pt, $ct );
    }

    /**
     * Genera una risposta con function calling (agentic chat).
     *
     * Flusso:
     *  1. Manda il messaggio + tool declarations a Gemini
     *  2. Se Gemini risponde con una functionCall esegui il tool PHP
     *  3. Rimanda il risultato a Gemini
     *  4. Gemini produce la risposta finale in linguaggio naturale
     */
    public function generate_with_tools( array $history, string $message, array $options = [] ): array {
        if ( empty( $this->api_key ) ) {
            return $this->format_error( 'API key Gemini non configurata.' );
        }

        $model   = $options['model'] ?? $this->model;
        $request = $this->build_request( $model );

        $contents   = $history;
        $contents[] = [
            'role'  => 'user',
            'parts' => [ [ 'text' => $message ] ],
        ];

        $system_text  = SiteGenie_Admin::get_site_context();
        $system_text .= "\n\nSei un assistente AI integrato nel pannello di amministrazione WordPress. ";
        $system_text .= "Puoi eseguire azioni reali sul sito usando i tool disponibili. ";
        $system_text .= "Quando l'utente chiede di creare, modificare, eliminare o recuperare contenuti, usa SEMPRE i tool appropriati. NON chiedere mai all'utente di eseguire comandi o tool. ";
        $system_text .= "REGOLA FONDAMENTALE: quando l'utente menziona un Custom Post Type (qualsiasi tipo diverso da 'post' e 'page'), devi IMMEDIATAMENTE chiamare il tool get_custom_post_types per scoprire i CPT e campi ACF, poi chiamare create_custom_post o update_custom_post. Fallo tu autonomamente, senza chiedere nulla all'utente. ";
        $system_text .= "Dopo aver eseguito un'azione, conferma cosa hai fatto in modo chiaro e conciso. ";
        $system_text .= "Rispondi sempre in italiano.";

        $body = [
            'system_instruction' => [
                'parts' => [ [ 'text' => $system_text ] ]
            ],
            'contents'         => $contents,
            'tools'            => [
                [ 'function_declarations' => SiteGenie_Tools::get_declarations() ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens']  ?? 1024,
                'temperature'     => $options['temperature'] ?? 0.4,
            ],
        ];

        // ── LOOP TOOL CALLING (supporta più turni) ─────────────────
        $total_pt = 0;
        $total_ct = 0;
        $last_action = null;
        $max_turns = 5;

        for ( $turn = 0; $turn < $max_turns; $turn++ ) {
            $body['contents'] = $contents;
            $response = $this->http_post( $request['url'], $body, $request['headers'] );

            if ( ! $response['success'] ) {
                SiteGenie_Logger::log( 'gemini', $total_pt, $total_ct, 'error', $response['error'] );
                return $this->format_error( $response['error'], $response['code'] );
            }

            $data  = $response['data'];
            $parts = $data['candidates'][0]['content']['parts'] ?? [];
            $total_pt += $data['usageMetadata']['promptTokenCount']     ?? 0;
            $total_ct += $data['usageMetadata']['candidatesTokenCount'] ?? 0;

            // Cerca function call
            $function_call = null;
            foreach ( $parts as $part ) {
                if ( isset( $part['functionCall'] ) ) {
                    $function_call = $part['functionCall'];
                    break;
                }
            }

            // Nessun tool call: risposta testuale finale
            if ( ! $function_call ) {
                $text = $parts[0]['text'] ?? 'Operazione completata.';
                SiteGenie_Logger::log( 'gemini', $total_pt, $total_ct, 'success' );
                $result = $this->format_response( $text, $total_pt, $total_ct );
                if ( $last_action ) $result['action_taken'] = $last_action;
                return $result;
            }

            // Esegui il tool
            $tool_name   = $function_call['name'];
            $tool_args   = $function_call['args'] ?? [];
            $tool_result = SiteGenie_Tools::execute( $tool_name, $tool_args );

            // Tieni traccia dell'ultima azione "mutativa"
            if ( in_array( $tool_name, [ 'create_post', 'update_post', 'delete_post', 'create_custom_post', 'update_custom_post' ] ) ) {
                $last_action = [ 'tool' => $tool_name, 'result' => $tool_result ];
            }

            // Aggiungi alla conversazione e continua il loop
            $fc_part = [ 'functionCall' => [ 'name' => $tool_name, 'args' => (object) $tool_args ] ];
            $contents[] = [
                'role'  => 'model',
                'parts' => [ $fc_part ],
            ];
            $contents[] = [
                'role'  => 'user',
                'parts' => [
                    [
                        'functionResponse' => [
                            'name'     => $tool_name,
                            'response' => $tool_result,
                        ]
                    ]
                ],
            ];
        }

        // Fallback se raggiunto il limite di turni
        $fallback = $last_action['result']['message'] ?? 'Operazione completata.';
        SiteGenie_Logger::log( 'gemini', $total_pt, $total_ct, 'success' );
        $result = $this->format_response( $fallback, $total_pt, $total_ct );
        if ( $last_action ) $result['action_taken'] = $last_action;
        return $result;
    }

    public static function get_models(): array {
        return [
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (consigliato — 1.000 req/giorno gratis)',
            'gemini-2.5-flash'      => 'Gemini 2.5 Flash (250 req/giorno gratis)',
            'gemini-2.5-pro'        => 'Gemini 2.5 Pro (100 req/giorno gratis, più lento)',
        ];
    }
}
