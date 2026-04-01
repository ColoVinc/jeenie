<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe base per tutti i connettori API
 * Ogni provider (Gemini, OpenAI, Claude) estende questa classe
 */
abstract class ChatPress_API_Connector {

    protected $api_key;
    protected $timeout = 30; // secondi

    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    /**
     * Metodo principale da implementare in ogni connettore
     */
    abstract public function generate( string $prompt, array $options = [] ): array;

    /**
     * Esegue una chiamata HTTP POST verso l'API
     */
    protected function http_post( string $url, array $body, array $headers = [] ): array {
        $default_headers = [
            'Content-Type' => 'application/json',
        ];

        $response = wp_remote_post( $url, [
            'timeout' => $this->timeout,
            'headers' => array_merge( $default_headers, $headers ),
            'body'    => wp_json_encode( $body ),
        ]);

        // Errore di connessione (timeout, DNS, ecc.)
        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'error'   => $response->get_error_message(),
                'code'    => 0,
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $error_msg = $body['error']['message'] ?? "Errore HTTP $code";
            return [
                'success' => false,
                'error'   => $error_msg,
                'code'    => $code,
            ];
        }

        return [
            'success' => true,
            'data'    => $body,
            'code'    => $code,
        ];
    }

    /**
     * Formatta la risposta in un formato standard uguale per tutti i provider
     */
    protected function format_response( string $text, int $prompt_tokens = 0, int $completion_tokens = 0 ): array {
        return [
            'success'           => true,
            'text'              => $text,
            'prompt_tokens'     => $prompt_tokens,
            'completion_tokens' => $completion_tokens,
            'total_tokens'      => $prompt_tokens + $completion_tokens,
        ];
    }

    /**
     * Formatta un errore in formato standard
     */
    protected function format_error( string $message, int $code = 0 ): array {
        return [
            'success' => false,
            'error'   => $message,
            'code'    => $code,
        ];
    }
}
