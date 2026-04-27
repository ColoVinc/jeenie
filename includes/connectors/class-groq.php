<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Connettore per Groq API — compatibile con formato OpenAI
 */
class Jeenie_Groq extends Jeenie_OpenAI {

    private $groq_base = 'https://api.groq.com/openai/v1/chat/completions';

    protected function get_api_base(): string {
        return $this->groq_base;
    }

    protected function get_provider_name(): string {
        return 'groq';
    }

    public static function get_models(): array {
        return [
            'llama-3.3-70b-versatile'                       => 'Llama 3.3 70B (versatile, veloce)',
            'llama-3.1-8b-instant'                          => 'Llama 3.1 8B (ultra-veloce, economico)',
            'openai/gpt-oss-120b'                           => 'GPT-OSS 120B (potente, open-weight)',
            'openai/gpt-oss-20b'                            => 'GPT-OSS 20B (veloce, open-weight)',
            'meta-llama/llama-4-scout-17b-16e-instruct'     => 'Llama 4 Scout 17B (anteprima)',
            'qwen/qwen3-32b'                                => 'Qwen3 32B (anteprima)',
        ];
    }
}
