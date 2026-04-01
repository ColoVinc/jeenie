<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Connettore per Google Gemini API — con Function Calling
 */
class ChatPress_Gemini extends ChatPress_API_Connector {

    private $model    = 'gemini-2.5-flash-lite';
    private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Genera testo semplice (senza tool use) — usato per metabox e test
     */
    public function generate( string $prompt, array $options = [] ): array {
        if ( empty( $this->api_key ) ) {
            return $this->format_error( 'API key Gemini non configurata.' );
        }

        $model = $options['model'] ?? $this->model;
        $url   = $this->api_base . $model . ':generateContent?key=' . $this->api_key;

        $body = [
            'contents' => [
                [ 'role' => 'user', 'parts' => [ [ 'text' => $prompt ] ] ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens']  ?? 2048,
                'temperature'     => $options['temperature'] ?? 0.7,
            ],
        ];

        $response = $this->http_post( $url, $body );

        if ( ! $response['success'] ) {
            ChatPress_Logger::log( 'gemini', 0, 0, 'error', $response['error'] );
            return $this->format_error( $response['error'], $response['code'] );
        }

        $data = $response['data'];
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ( empty( $text ) ) {
            $error = 'Risposta vuota da Gemini.';
            ChatPress_Logger::log( 'gemini', 0, 0, 'error', $error );
            return $this->format_error( $error );
        }

        $pt = $data['usageMetadata']['promptTokenCount']     ?? 0;
        $ct = $data['usageMetadata']['candidatesTokenCount'] ?? 0;
        ChatPress_Logger::log( 'gemini', $pt, $ct, 'success' );

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

        $model = $options['model'] ?? $this->model;
        $url   = $this->api_base . $model . ':generateContent?key=' . $this->api_key;

        $contents   = $history;
        $contents[] = [
            'role'  => 'user',
            'parts' => [ [ 'text' => $message ] ],
        ];

        $system_text  = ChatPress_Admin::get_site_context();
        $system_text .= "\n\nSei un assistente AI integrato nel pannello di amministrazione WordPress. ";
        $system_text .= "Puoi eseguire azioni reali sul sito usando i tool disponibili. ";
        $system_text .= "Quando l'utente chiede di creare, modificare, eliminare o recuperare contenuti, usa sempre i tool appropriati. ";
        $system_text .= "Dopo aver eseguito un'azione, conferma cosa hai fatto in modo chiaro e conciso. ";
        $system_text .= "Rispondi sempre in italiano.";

        $body = [
            'system_instruction' => [
                'parts' => [ [ 'text' => $system_text ] ]
            ],
            'contents'         => $contents,
            'tools'            => [
                [ 'function_declarations' => ChatPress_Tools::get_declarations() ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens']  ?? 1024,
                'temperature'     => $options['temperature'] ?? 0.4,
            ],
        ];

        // ── PRIMO TURNO ───────────────────────────────────────────────
        $response  = $this->http_post( $url, $body );

        if ( ! $response['success'] ) {
            ChatPress_Logger::log( 'gemini', 0, 0, 'error', $response['error'] );
            return $this->format_error( $response['error'], $response['code'] );
        }

        $data      = $response['data'];
        $candidate = $data['candidates'][0] ?? [];
        $parts     = $candidate['content']['parts'] ?? [];
        $pt        = $data['usageMetadata']['promptTokenCount']     ?? 0;
        $ct        = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

        // Cerca function call nella risposta
        $function_call = null;
        foreach ( $parts as $part ) {
            if ( isset( $part['functionCall'] ) ) {
                $function_call = $part['functionCall'];
                break;
            }
        }

        // Nessun tool call: risposta testuale diretta
        if ( ! $function_call ) {
            $text = $parts[0]['text'] ?? '';
            ChatPress_Logger::log( 'gemini', $pt, $ct, 'success' );
            return $this->format_response( $text, $pt, $ct );
        }

        // ── ESEGUI IL TOOL ────────────────────────────────────────────
        $tool_name   = $function_call['name'];
        $tool_args   = $function_call['args'] ?? [];
        $tool_result = ChatPress_Tools::execute( $tool_name, $tool_args );

        // ── SECONDO TURNO: rimanda risultato a Gemini ─────────────────
        $contents[] = [
            'role'  => 'model',
            'parts' => [ [ 'functionCall' => $function_call ] ],
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

        $body['contents'] = $contents;
        $response2        = $this->http_post( $url, $body );

        if ( ! $response2['success'] ) {
            $fallback = $tool_result['message'] ?? ( $tool_result['error'] ?? 'Operazione completata.' );
            ChatPress_Logger::log( 'gemini', $pt, $ct, 'success' );
            return array_merge(
                $this->format_response( $fallback, $pt, $ct ),
                [ 'action_taken' => [ 'tool' => $tool_name, 'result' => $tool_result ] ]
            );
        }

        $data2  = $response2['data'];
        $parts2 = $data2['candidates'][0]['content']['parts'] ?? [];
        $text2  = $parts2[0]['text'] ?? ( $tool_result['message'] ?? 'Operazione completata.' );
        $pt2    = $data2['usageMetadata']['promptTokenCount']     ?? 0;
        $ct2    = $data2['usageMetadata']['candidatesTokenCount'] ?? 0;

        ChatPress_Logger::log( 'gemini', $pt + $pt2, $ct + $ct2, 'success' );

        return array_merge(
            $this->format_response( $text2, $pt + $pt2, $ct + $ct2 ),
            [ 'action_taken' => [ 'tool' => $tool_name, 'result' => $tool_result ] ]
        );
    }

    public static function get_models(): array {
        return [
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (consigliato — 1.000 req/giorno gratis)',
            'gemini-2.5-flash'      => 'Gemini 2.5 Flash (250 req/giorno gratis)',
            'gemini-2.5-pro'        => 'Gemini 2.5 Pro (100 req/giorno gratis, più lento)',
        ];
    }
}
