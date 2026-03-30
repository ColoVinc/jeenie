<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="chatpress-chat-widget" class="chatpress-chat-widget">

    <!-- Bottone toggle -->
    <button id="chatpress-chat-toggle" class="chatpress-chat-toggle" title="ChatPress AI">
        <span class="chatpress-chat-icon">🤖</span>
        <span class="chatpress-chat-close" style="display: none;">✕</span>
    </button>

    <!-- Finestra chat -->
    <div id="chatpress-chat-window" class="chatpress-chat-window" style="display: none;">
        <div class="chatpress-chat-header">
            <span>Chatpress Assistant</span>
            <small>Powered By Gemini</small>
        </div>

        <div id="chatpress-chat-messages" class="chatpress-chat-messages">
            <div class="chatpress-chat-message chatpress-chat-messages--ai">
                Ciao! Sono il tuo assistente AI. Come posso aiutarti oggi?
            </div>
        </div>

        <div class="chatpress-chat-suggestions">
            <button class="chatpress-suggestion" data-msg="Dammi 5 idee per articoli del blog">Idee articoli</button>
            <button class="chatpress-suggestion" data-msg="Come posso migliorare la SEO del sito?">Consiglio SEO</button>
            <button class="chatpress-suggestion" data-msg="Scrivi un post breve su un argomento a mia scelta">Scrivi un post</button>
        </div>

        <div class="chatpress-chat-input-wrap">
            <textarea id="chatpress-chat-input" placeholder="Scrivi un messaggio..." rows="2"></textarea>
            <button id="chatpress-chat-send" title="Invia">➤</button>
        </div>
    </div>
</div>
