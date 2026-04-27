<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Connettore per Anthropic Claude API — con Tool Use
 */
class Jeenie_Claude extends Jeenie_API_Connector {

    private $api_base = 'https://api.anthropic.com/v1/messages';

    public function generate( string $prompt, array $options = [] ): array {
        if ( empty( $this->api_key ) ) {
            return $this->format_error( 'API key Claude non configurata.' );
        }

        $body = [
            'model'      => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? 2048,
            'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
        ];

        $response = $this->http_post( $this->api_base, $body, $this->auth_headers() );

        if ( ! $response['success'] ) {
            Jeenie_Logger::log( 'claude', 0, 0, 'error', $response['error'] );
            return $this->format_error( $response['error'], $response['code'] );
        }

        $data = $response['data'];
        $text = $data['content'][0]['text'] ?? '';

        if ( empty( $text ) ) {
            Jeenie_Logger::log( 'claude', 0, 0, 'error', 'Risposta vuota.' );
            return $this->format_error( 'Risposta vuota da Claude.' );
        }

        $pt = $data['usage']['input_tokens']  ?? 0;
        $ct = $data['usage']['output_tokens'] ?? 0;
        Jeenie_Logger::log( 'claude', $pt, $ct, 'success' );

        return $this->format_response( $text, $pt, $ct );
    }

    public function generate_with_tools( array $history, string $message, array $options = [] ): array {
        if ( empty( $this->api_key ) ) {
            return $this->format_error( 'API key Claude non configurata.' );
        }

        $messages = [];
        foreach ( $history as $turn ) {
            $role    = $turn['role'] === 'model' ? 'assistant' : 'user';
            $content = $turn['parts'][0]['text'] ?? '';
            if ( $content ) $messages[] = [ 'role' => $role, 'content' => $content ];
        }
        $messages[] = [ 'role' => 'user', 'content' => $message ];

        $body = [
            'model'      => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'system'     => $this->build_system_prompt(),
            'messages'   => $messages,
            'tools'      => $this->convert_tools(),
        ];

        $total_pt    = 0;
        $total_ct    = 0;
        $last_action = null;
        $max_turns   = 5;

        for ( $turn = 0; $turn < $max_turns; $turn++ ) {
            $body['messages'] = $messages;
            $response = $this->http_post( $this->api_base, $body, $this->auth_headers() );

            if ( ! $response['success'] ) {
                Jeenie_Logger::log( 'claude', $total_pt, $total_ct, 'error', $response['error'] );
                return $this->format_error( $response['error'], $response['code'] );
            }

            $data     = $response['data'];
            $content  = $data['content'] ?? [];
            $total_pt += $data['usage']['input_tokens']  ?? 0;
            $total_ct += $data['usage']['output_tokens'] ?? 0;

            // Cerca tool_use blocks
            $tool_uses = array_filter( $content, fn( $b ) => $b['type'] === 'tool_use' );

            if ( empty( $tool_uses ) ) {
                $text_block = array_filter( $content, fn( $b ) => $b['type'] === 'text' );
                $text = ! empty( $text_block ) ? reset( $text_block )['text'] : 'Operazione completata.';
                Jeenie_Logger::log( 'claude', $total_pt, $total_ct, 'success' );
                $result = $this->format_response( $text, $total_pt, $total_ct );
                if ( $last_action ) $result['action_taken'] = $last_action;
                return $result;
            }

            // Aggiungi risposta assistant
            $messages[] = [ 'role' => 'assistant', 'content' => $content ];

            // Esegui tool e costruisci tool_result
            $tool_results = [];
            foreach ( $tool_uses as $tu ) {
                $tool_name   = $tu['name'];
                $tool_args   = $tu['input'] ?? [];
                $tool_result = Jeenie_Tools::execute( $tool_name, $tool_args );

                if ( in_array( $tool_name, [ 'create_post', 'update_post', 'delete_post', 'create_custom_post', 'update_custom_post', 'moderate_comment', 'reply_comment', 'update_site_settings', 'create_user', 'create_product', 'add_menu_item', 'create_component' ] ) ) {
                    $last_action = [ 'tool' => $tool_name, 'result' => $tool_result ];
                }

                $tool_results[] = [
                    'type'       => 'tool_result',
                    'tool_use_id' => $tu['id'],
                    'content'    => wp_json_encode( $tool_result ),
                ];
            }
            $messages[] = [ 'role' => 'user', 'content' => $tool_results ];
        }

        $fallback = $last_action['result']['message'] ?? 'Operazione completata.';
        Jeenie_Logger::log( 'claude', $total_pt, $total_ct, 'success' );
        $result = $this->format_response( $fallback, $total_pt, $total_ct );
        if ( $last_action ) $result['action_taken'] = $last_action;
        return $result;
    }

    private function auth_headers(): array {
        return [
            'x-api-key'         => $this->api_key,
            'anthropic-version' => '2023-06-01',
        ];
    }

    private function build_system_prompt(): string {
        $system = Jeenie_Admin::get_site_context();
        $system .= "\n\nSei un assistente AI integrato nel pannello di amministrazione WordPress. ";
        $system .= "Puoi eseguire azioni reali sul sito usando i tool disponibili. ";
        $system .= "Quando l'utente chiede di creare, modificare, eliminare o recuperare contenuti, usa SEMPRE i tool appropriati. NON chiedere mai all'utente di eseguire comandi o tool. ";
        $system .= "REGOLA FONDAMENTALE: quando l'utente menziona un Custom Post Type (qualsiasi tipo diverso da 'post' e 'page'), devi IMMEDIATAMENTE chiamare il tool get_custom_post_types per scoprire i CPT e campi ACF, poi chiamare create_custom_post o update_custom_post. ";
        $system .= "Dopo aver eseguito un'azione, conferma cosa hai fatto in modo chiaro e conciso. ";
        $system .= "Rispondi sempre in italiano.";
        return $system;
    }

    private function convert_tools(): array {
        $tools = [];
        foreach ( Jeenie_Tools::get_declarations() as $decl ) {
            $params = $decl['parameters'] ?? [];
            if ( $params instanceof \stdClass ) {
                $params = [ 'type' => 'object', 'properties' => new \stdClass() ];
            }
            $tools[] = [
                'name'         => $decl['name'],
                'description'  => $decl['description'],
                'input_schema' => $params,
            ];
        }
        return $tools;
    }

    /**
     * Streaming: manda la risposta testuale chunk per chunk via SSE.
     */
    public function stream_response( string $prompt, array $options = [] ): void {
        $body = [
            'model'      => $options['model'] ?? $this->model,
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'messages'   => $options['messages'] ?? [ [ 'role' => 'user', 'content' => $prompt ] ],
            'stream'     => true,
        ];

        if ( ! empty( $options['system'] ) ) {
            $body['system'] = $options['system'];
        }

        $total_pt = 0;
        $total_ct = 0;

        $this->http_stream( $this->api_base, $body, $this->auth_headers(), function( $line ) use ( &$total_pt, &$total_ct ) {
            $line = trim( $line );
            if ( strpos( $line, 'data: ' ) !== 0 ) return;
            $json = json_decode( substr( $line, 6 ), true );
            if ( ! $json ) return;

            if ( $json['type'] === 'content_block_delta' ) {
                $text = $json['delta']['text'] ?? '';
                if ( $text !== '' ) {
                    echo "data: " . wp_json_encode( [ 'chunk' => $text ] ) . "\n\n";
                }
            }

            if ( $json['type'] === 'message_delta' && isset( $json['usage'] ) ) {
                $total_ct = $json['usage']['output_tokens'] ?? $total_ct;
            }
            if ( $json['type'] === 'message_start' && isset( $json['message']['usage'] ) ) {
                $total_pt = $json['message']['usage']['input_tokens'] ?? $total_pt;
            }
        });

        Jeenie_Logger::log( 'claude', $total_pt, $total_ct, 'success' );
        echo "data: [DONE]\n\n";
    }

    public static function get_models(): array {
        return [
            'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (veloce, economico — $1/M token)',
            'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (bilanciato — $3/M token)',
            'claude-opus-4-6'           => 'Claude Opus 4.6 (più intelligente — $5/M token)',
        ];
    }
}
