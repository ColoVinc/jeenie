<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Connettore per Google Gemini API
 */
class ChatPress_Gemini extends ChatPress_API_Connector {

    // Modello di default — Gemini 2.0 Flash (gratuito e veloce)
    private $model = 'gemini-2.0-flash';
    private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Genera testo tramite Gemini
     *
     * @param string $prompt  Il testo della richiesta
     * @param array  $options Opzioni opzionali: max_tokens, temperature
     * @return array          Risposta standardizzata
     */
    public function generate( string $prompt, array $options = [] ): array {
        if ( empty( $this->api_key ) ) {
            return $this->format_error( 'API key Gemini non configurata.' );
        }

        $max_tokens  = $options['max_tokens'] ?? 2048;
        $temperature = $options['temperature'] ?? 0.7;
        $model       = $options['model'] ?? $this->model;

        $url = $this->api_base . $model . ':generateContent?key=' . $this->api_key;

        $body = [
            'contents' => [
                [
                    'parts' => [
                        [ 'text' => $prompt ]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $max_tokens,
                'temperature'     => $temperature,
            ],
        ];

        $response = $this->http_post( $url, $body );

        if ( ! $response['success'] ) {
            ChatPress_Logger::log( 'gemini', 0, 0, 'error', $response['error'] );
            return $this->format_error( $response['error'], $response['code'] );
        }

        // Estrai il testo dalla risposta Gemini
        $data = $response['data'];
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ( empty( $text ) ) {
            $error = 'Risposta vuota da Gemini.';
            ChatPress_Logger::log( 'gemini', 0, 0, 'error', $error );
            return $this->format_error( $error );
        }

        // Token usage (Gemini li restituisce in usageMetadata)
        $prompt_tokens     = $data['usageMetadata']['promptTokenCount'] ?? 0;
        $completion_tokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

        ChatPress_Logger::log( 'gemini', $prompt_tokens, $completion_tokens, 'success' );

        return $this->format_response( $text, $prompt_tokens, $completion_tokens );
    }

    /**
     * Restituisce i modelli disponibili
     */
    public static function get_models(): array {
        return [
            'gemini-2.0-flash'   => 'Gemini 2.0 Flash (consigliato)',
            'gemini-1.5-flash'   => 'Gemini 1.5 Flash',
            'gemini-1.5-pro'     => 'Gemini 1.5 Pro (lento, 50 req/giorno)',
        ];
    }
}