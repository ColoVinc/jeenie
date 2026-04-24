<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SiteGenie_Tools
 *
 * Definisce i tool che Gemini può chiamare (function declarations)
 * ed esegue le funzioni WordPress corrispondenti.
 */
class SiteGenie_Tools {

    /**
     * Restituisce le dichiarazioni dei tool da passare all'API Gemini.
     * Ogni tool ha: name, description, parameters (schema JSON).
     */
    public static function get_declarations(): array {
        $tools = [

            // ── POSTS ────────────────────────────────────────────────
            [
                'name'        => 'create_post',
                'description' => 'Crea un nuovo articolo o pagina in WordPress. Usalo quando l\'utente chiede di creare, scrivere o aggiungere un post, articolo o pagina.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'title'    => [ 'type' => 'string',  'description' => 'Titolo del post' ],
                        'content'  => [ 'type' => 'string',  'description' => 'Contenuto HTML o testo del post' ],
                        'status'   => [ 'type' => 'string',  'description' => 'Stato: draft, publish, private. Default: draft' ],
                        'type'     => [ 'type' => 'string',  'description' => 'Tipo: post o page. Default: post' ],
                        'excerpt'  => [ 'type' => 'string',  'description' => 'Riassunto breve (opzionale)' ],
                        'tags'     => [ 'type' => 'string',  'description' => 'Tag separati da virgola (opzionale)' ],
                        'category' => [ 'type' => 'string',  'description' => 'Nome categoria (opzionale)' ],
                    ],
                    'required' => [ 'title' ],
                ],
            ],

            [
                'name'        => 'get_posts',
                'description' => 'Recupera la lista degli articoli o pagine esistenti. Usalo quando l\'utente vuole vedere, elencare o trovare post.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'status'   => [ 'type' => 'string', 'description' => 'Stato: any, publish, draft, private. Default: any' ],
                        'type'     => [ 'type' => 'string', 'description' => 'Tipo: post o page. Default: post' ],
                        'limit'    => [ 'type' => 'integer','description' => 'Quanti post restituire. Default: 5, max: 20' ],
                        'search'   => [ 'type' => 'string', 'description' => 'Parola chiave da cercare nel titolo o contenuto (opzionale)' ],
                    ],
                ],
            ],

            [
                'name'        => 'update_post',
                'description' => 'Modifica un articolo o pagina esistente. Usalo quando l\'utente vuole aggiornare, modificare o cambiare titolo/contenuto/stato di un post.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id'  => [ 'type' => 'integer', 'description' => 'ID del post da modificare' ],
                        'title'    => [ 'type' => 'string',  'description' => 'Nuovo titolo (opzionale)' ],
                        'content'  => [ 'type' => 'string',  'description' => 'Nuovo contenuto (opzionale)' ],
                        'status'   => [ 'type' => 'string',  'description' => 'Nuovo stato: draft, publish, private (opzionale)' ],
                        'excerpt'  => [ 'type' => 'string',  'description' => 'Nuovo excerpt (opzionale)' ],
                    ],
                    'required' => [ 'post_id' ],
                ],
            ],

            [
                'name'        => 'delete_post',
                'description' => 'Sposta un articolo nel cestino. Usalo solo se l\'utente chiede esplicitamente di eliminare o cancellare un post.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id' => [ 'type' => 'integer', 'description' => 'ID del post da eliminare' ],
                    ],
                    'required' => [ 'post_id' ],
                ],
            ],

            // ── MEDIA ────────────────────────────────────────────────
            [
                'name'        => 'get_media',
                'description' => 'Recupera la lista dei file nella libreria media di WordPress.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'limit'  => [ 'type' => 'integer', 'description' => 'Quanti file restituire. Default: 10' ],
                        'search' => [ 'type' => 'string',  'description' => 'Parola chiave da cercare (opzionale)' ],
                    ],
                ],
            ],

            // ── CATEGORIE & TAG ──────────────────────────────────────
            [
                'name'        => 'get_categories',
                'description' => 'Recupera tutte le categorie esistenti nel sito.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new stdClass(),
                ],
            ],

            // ── SITO ─────────────────────────────────────────────────
            [
                'name'        => 'get_site_info',
                'description' => 'Recupera informazioni generali sul sito: nome, URL, numero di post, numero di pagine, tema attivo, plugin attivi.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new stdClass(),
                ],
            ],

            // ── CUSTOM POST TYPES & ACF ──────────────────────────────
            [
                'name'        => 'get_custom_post_types',
                'description' => 'Recupera tutti i Custom Post Type registrati nel sito con i relativi campi ACF (Advanced Custom Fields). Usalo PRIMA di create_custom_post per sapere quali post type e campi sono disponibili.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new stdClass(),
                ],
            ],

            [
                'name'        => 'create_custom_post',
                'description' => 'Crea un post in qualsiasi Custom Post Type e popola i campi ACF. IMPORTANTE: chiama SEMPRE get_custom_post_types prima di usare questo tool, per conoscere i campi ACF disponibili.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'post_type' => [ 'type' => 'string',  'description' => 'Slug del Custom Post Type (es. "prodotto", "evento")' ],
                        'title'     => [ 'type' => 'string',  'description' => 'Titolo del post' ],
                        'content'   => [ 'type' => 'string',  'description' => 'Contenuto del post (opzionale)' ],
                        'status'    => [ 'type' => 'string',  'description' => 'Stato: draft, publish, private. Default: draft' ],
                        'fields'    => [ 'type' => 'object',  'description' => 'Oggetto chiave-valore con i campi ACF da compilare. Le chiavi sono i field_name ACF.' ],
                    ],
                    'required' => [ 'post_type', 'title' ],
                ],
            ],

            [
                'name'        => 'update_custom_post',
                'description' => 'Modifica un post di qualsiasi tipo e aggiorna i campi ACF. Usa get_custom_post_types per conoscere i campi disponibili.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id'  => [ 'type' => 'integer', 'description' => 'ID del post da modificare' ],
                        'title'    => [ 'type' => 'string',  'description' => 'Nuovo titolo (opzionale)' ],
                        'content'  => [ 'type' => 'string',  'description' => 'Nuovo contenuto (opzionale)' ],
                        'status'   => [ 'type' => 'string',  'description' => 'Nuovo stato (opzionale)' ],
                        'fields'   => [ 'type' => 'object',  'description' => 'Campi ACF da aggiornare (chiave-valore)' ],
                    ],
                    'required' => [ 'post_id' ],
                ],
            ],

            // ── UTENTI ────────────────────────────────────────────────
            [
                'name'        => 'get_users',
                'description' => 'Recupera la lista degli utenti del sito. Usalo quando l\'utente vuole vedere chi è registrato.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'role'  => [ 'type' => 'string',  'description' => 'Filtra per ruolo: administrator, editor, author, contributor, subscriber (opzionale)' ],
                        'limit' => [ 'type' => 'integer', 'description' => 'Quanti utenti restituire. Default: 10' ],
                    ],
                ],
            ],

            [
                'name'        => 'create_user',
                'description' => 'Crea un nuovo utente WordPress. Usalo quando l\'utente chiede di aggiungere un nuovo utente al sito.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'username' => [ 'type' => 'string', 'description' => 'Nome utente (login)' ],
                        'email'    => [ 'type' => 'string', 'description' => 'Indirizzo email' ],
                        'role'     => [ 'type' => 'string', 'description' => 'Ruolo: editor, author, contributor, subscriber. Default: subscriber' ],
                        'name'     => [ 'type' => 'string', 'description' => 'Nome visualizzato (opzionale)' ],
                    ],
                    'required' => [ 'username', 'email' ],
                ],
            ],

            // ── IMPOSTAZIONI SITO ─────────────────────────────────────
            [
                'name'        => 'update_site_settings',
                'description' => 'Modifica le impostazioni base del sito WordPress: titolo, tagline, post per pagina, stato commenti. Usalo quando l\'utente chiede di cambiare il nome del sito, la descrizione o altre impostazioni generali.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'blogname'               => [ 'type' => 'string',  'description' => 'Titolo del sito' ],
                        'blogdescription'        => [ 'type' => 'string',  'description' => 'Tagline / descrizione breve del sito' ],
                        'posts_per_page'         => [ 'type' => 'integer', 'description' => 'Numero di post per pagina' ],
                        'default_comment_status' => [ 'type' => 'string',  'description' => 'Commenti abilitati di default: open o closed' ],
                    ],
                ],
            ],

            // ── COMMENTI ──────────────────────────────────────────────
            [
                'name'        => 'get_comments',
                'description' => 'Recupera i commenti del sito. Usalo quando l\'utente vuole vedere, moderare o controllare i commenti.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'status' => [ 'type' => 'string',  'description' => 'Stato: all, hold, approve, spam, trash. Default: all' ],
                        'limit'  => [ 'type' => 'integer', 'description' => 'Quanti commenti restituire. Default: 10' ],
                        'post_id'=> [ 'type' => 'integer', 'description' => 'Filtra per ID post (opzionale)' ],
                    ],
                ],
            ],

            [
                'name'        => 'moderate_comment',
                'description' => 'Modera un commento: approvalo, segnalo come spam o eliminalo. Usalo quando l\'utente chiede di approvare, rifiutare o eliminare commenti.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'comment_id' => [ 'type' => 'integer', 'description' => 'ID del commento' ],
                        'action'     => [ 'type' => 'string',  'description' => 'Azione: approve, spam, trash' ],
                    ],
                    'required' => [ 'comment_id', 'action' ],
                ],
            ],

            [
                'name'        => 'reply_comment',
                'description' => 'Rispondi a un commento esistente. Usalo quando l\'utente chiede di rispondere a un commento specifico.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'comment_id' => [ 'type' => 'integer', 'description' => 'ID del commento a cui rispondere' ],
                        'content'    => [ 'type' => 'string',  'description' => 'Testo della risposta' ],
                    ],
                    'required' => [ 'comment_id', 'content' ],
                ],
            ],

            // ── COMPONENTI ────────────────────────────────────────────
            [
                'name'        => 'create_component',
                'description' => 'Crea un componente per un page builder (WPBakery, Elementor, ecc.). Fornisci slug, nome, editor e una descrizione dettagliata di cosa deve fare il componente. Il codice verrà generato automaticamente.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'slug'        => [ 'type' => 'string', 'description' => 'Slug del componente (es. sg-hero-section). Deve iniziare con sg-' ],
                        'name'        => [ 'type' => 'string', 'description' => 'Nome leggibile del componente (es. Hero Section)' ],
                        'editor'      => [ 'type' => 'string', 'description' => 'Editor: wpbakery, elementor, gutenberg, divi' ],
                        'description' => [ 'type' => 'string', 'description' => 'Descrizione dettagliata del componente: cosa mostra, quali campi ha, come appare' ],
                    ],
                    'required' => [ 'slug', 'name', 'editor', 'description' ],
                ],
            ],

            // ── MENU ─────────────────────────────────────────────────
            [
                'name'        => 'get_menus',
                'description' => 'Recupera i menu di navigazione del sito con le relative voci. Usalo quando l\'utente vuole vedere o gestire i menu.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new stdClass(),
                ],
            ],

            [
                'name'        => 'add_menu_item',
                'description' => 'Aggiunge una voce a un menu di navigazione. IMPORTANTE: chiama SEMPRE get_menus prima di usare questo tool per ottenere l\'ID del menu.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'menu_id' => [ 'type' => 'integer', 'description' => 'ID del menu a cui aggiungere la voce' ],
                        'title'   => [ 'type' => 'string',  'description' => 'Testo della voce di menu' ],
                        'url'     => [ 'type' => 'string',  'description' => 'URL personalizzato (per link custom)' ],
                        'page_id' => [ 'type' => 'integer', 'description' => 'ID della pagina/post da linkare (alternativo a url)' ],
                    ],
                    'required' => [ 'menu_id', 'title' ],
                ],
            ],

        ];

        // ── WOOCOMMERCE (solo se attivo) ─────────────────────────
        if ( class_exists( 'WooCommerce' ) ) {
            $tools[] = [
                'name'        => 'get_products',
                'description' => 'Recupera la lista dei prodotti WooCommerce.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'limit'  => [ 'type' => 'integer', 'description' => 'Quanti prodotti restituire. Default: 10' ],
                        'status' => [ 'type' => 'string',  'description' => 'Stato: publish, draft, any. Default: publish' ],
                    ],
                ],
            ];
            $tools[] = [
                'name'        => 'create_product',
                'description' => 'Crea un nuovo prodotto WooCommerce semplice.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'name'          => [ 'type' => 'string',  'description' => 'Nome del prodotto' ],
                        'price'         => [ 'type' => 'string',  'description' => 'Prezzo (es. "29.90")' ],
                        'description'   => [ 'type' => 'string',  'description' => 'Descrizione del prodotto (opzionale)' ],
                        'status'        => [ 'type' => 'string',  'description' => 'Stato: draft, publish. Default: draft' ],
                    ],
                    'required' => [ 'name', 'price' ],
                ],
            ];
            $tools[] = [
                'name'        => 'get_orders',
                'description' => 'Recupera gli ordini WooCommerce recenti.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'limit'  => [ 'type' => 'integer', 'description' => 'Quanti ordini restituire. Default: 10' ],
                        'status' => [ 'type' => 'string',  'description' => 'Stato: any, processing, completed, on-hold. Default: any' ],
                    ],
                ],
            ];
        }

        return $tools;
    }

    /**
     * Esegue il tool richiesto da Gemini e restituisce il risultato.
     *
     * @param string $name    Nome del tool
     * @param array  $args    Parametri passati da Gemini
     * @return array          Risultato dell'esecuzione
     */
    public static function execute( string $name, array $args ): array {
        switch ( $name ) {
            case 'create_post':     return self::create_post( $args );
            case 'get_posts':       return self::get_posts( $args );
            case 'update_post':     return self::update_post( $args );
            case 'delete_post':     return self::delete_post( $args );
            case 'get_media':       return self::get_media( $args );
            case 'get_categories':        return self::get_categories();
            case 'get_site_info':         return self::get_site_info();
            case 'get_custom_post_types': return self::get_custom_post_types();
            case 'create_custom_post':    return self::create_custom_post( $args );
            case 'update_custom_post':    return self::update_custom_post( $args );
            case 'get_comments':          return self::get_comments( $args );
            case 'moderate_comment':      return self::moderate_comment( $args );
            case 'reply_comment':         return self::reply_comment( $args );
            case 'update_site_settings':  return self::update_site_settings( $args );
            case 'get_users':             return self::tool_get_users( $args );
            case 'create_user':           return self::tool_create_user( $args );
            case 'get_products':          return self::tool_get_products( $args );
            case 'create_product':        return self::tool_create_product( $args );
            case 'get_orders':            return self::tool_get_orders( $args );
            case 'get_menus':             return self::tool_get_menus();
            case 'add_menu_item':         return self::tool_add_menu_item( $args );
            case 'create_component':      return self::tool_create_component( $args );
            default:
                return [ 'error' => "Tool \"$name\" non riconosciuto." ];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // IMPLEMENTAZIONI
    // ─────────────────────────────────────────────────────────────────

    private static function create_post( array $args ): array {
        $status  = in_array( $args['status'] ?? 'draft', [ 'draft', 'publish', 'private' ] ) ? $args['status'] : 'draft';
        $type    = in_array( $args['type'] ?? 'post', [ 'post', 'page' ] ) ? $args['type'] : 'post';
        $content = $args['content'] ?? '';

        // Converte markdown semplice in paragrafi se non è già HTML
        if ( strpos( $content, '<' ) === false ) {
            $content = wpautop( $content );
        }

        $post_data = [
            'post_title'   => sanitize_text_field( $args['title'] ),
            'post_content' => wp_kses_post( $content ),
            'post_status'  => $status,
            'post_type'    => $type,
            'post_excerpt' => sanitize_text_field( $args['excerpt'] ?? '' ),
        ];

        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return [ 'error' => $post_id->get_error_message() ];
        }

        // Categoria
        if ( ! empty( $args['category'] ) ) {
            $cat = get_term_by( 'name', $args['category'], 'category' );
            if ( $cat ) {
                wp_set_post_categories( $post_id, [ $cat->term_id ] );
            } else {
                $new_cat = wp_insert_term( $args['category'], 'category' );
                if ( ! is_wp_error( $new_cat ) ) {
                    wp_set_post_categories( $post_id, [ $new_cat['term_id'] ] );
                }
            }
        }

        // Tag
        if ( ! empty( $args['tags'] ) ) {
            $tags = array_map( 'trim', explode( ',', $args['tags'] ) );
            wp_set_post_tags( $post_id, $tags );
        }

        $edit_url = get_edit_post_link( $post_id, 'raw' );

        return [
            'success'  => true,
            'post_id'  => $post_id,
            'title'    => $args['title'],
            'status'   => $status,
            'type'     => $type,
            'edit_url' => $edit_url,
            'message'  => ucfirst($type) . " \"" . $args['title'] . "\" creato con successo in stato \"$status\" (ID: $post_id).",
        ];
    }

    private static function get_posts( array $args ): array {
        $limit  = min( (int) ( $args['limit'] ?? 5 ), 20 );
        $status = $args['status'] ?? 'any';
        $type   = $args['type'] ?? 'post';

        $query_args = [
            'post_type'      => $type,
            'post_status'    => $status,
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }

        $posts = get_posts( $query_args );

        if ( empty( $posts ) ) {
            return [ 'success' => true, 'count' => 0, 'posts' => [], 'message' => 'Nessun post trovato.' ];
        }

        $result = [];
        foreach ( $posts as $post ) {
            $result[] = [
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'status'   => $post->post_status,
                'type'     => $post->post_type,
                'date'     => $post->post_date,
                'excerpt'  => $post->post_excerpt ?: '',
                'content'  => mb_strimwidth( wp_strip_all_tags( $post->post_content ), 0, 1000, '...' ),
                'word_count' => str_word_count( wp_strip_all_tags( $post->post_content ) ),
                'edit_url' => get_edit_post_link( $post->ID, 'raw' ),
            ];
        }

        return [ 'success' => true, 'count' => count( $result ), 'posts' => $result ];
    }

    private static function update_post( array $args ): array {
        $post_id = (int) $args['post_id'];
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return [ 'error' => "Post con ID $post_id non trovato." ];
        }

        $update = [ 'ID' => $post_id ];

        if ( isset( $args['title'] ) )   $update['post_title']   = sanitize_text_field( $args['title'] );
        if ( isset( $args['content'] ) ) $update['post_content'] = wp_kses_post( wpautop( $args['content'] ) );
        if ( isset( $args['excerpt'] ) ) $update['post_excerpt'] = sanitize_text_field( $args['excerpt'] );
        if ( isset( $args['status'] ) && in_array( $args['status'], [ 'draft', 'publish', 'private' ] ) ) {
            $update['post_status'] = $args['status'];
        }

        $result = wp_update_post( $update, true );

        if ( is_wp_error( $result ) ) {
            return [ 'error' => $result->get_error_message() ];
        }

        return [
            'success' => true,
            'post_id' => $post_id,
            'message' => "Post \"" . get_the_title( $post_id ) . "\" (ID: $post_id) aggiornato con successo.",
        ];
    }

    private static function delete_post( array $args ): array {
        $post_id = (int) $args['post_id'];
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return [ 'error' => "Post con ID $post_id non trovato." ];
        }

        $title  = $post->post_title;
        $result = wp_trash_post( $post_id );

        if ( ! $result ) {
            return [ 'error' => "Impossibile eliminare il post $post_id." ];
        }

        return [
            'success' => true,
            'post_id' => $post_id,
            'message' => "Post \"$title\" (ID: $post_id) spostato nel cestino.",
        ];
    }

    private static function get_media( array $args ): array {
        $limit = min( (int) ( $args['limit'] ?? 10 ), 30 );

        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }

        $media = get_posts( $query_args );
        $result = [];

        foreach ( $media as $item ) {
            $result[] = [
                'id'       => $item->ID,
                'title'    => $item->post_title,
                'filename' => basename( get_attached_file( $item->ID ) ),
                'url'      => wp_get_attachment_url( $item->ID ),
                'type'     => $item->post_mime_type,
                'date'     => $item->post_date,
            ];
        }

        return [ 'success' => true, 'count' => count( $result ), 'media' => $result ];
    }

    private static function get_categories(): array {
        $cats = get_categories( [ 'hide_empty' => false ] );
        $result = [];

        foreach ( $cats as $cat ) {
            $result[] = [
                'id'    => $cat->term_id,
                'name'  => $cat->name,
                'slug'  => $cat->slug,
                'count' => $cat->count,
            ];
        }

        return [ 'success' => true, 'categories' => $result ];
    }

    private static function get_site_info(): array {
        $active_plugins = [];
        foreach ( get_option( 'active_plugins', [] ) as $plugin ) {
            $data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
            if ( ! empty( $data['Name'] ) ) {
                $active_plugins[] = $data['Name'];
            }
        }

        $theme = wp_get_theme();

        return [
            'success'        => true,
            'site_name'      => get_bloginfo( 'name' ),
            'site_url'       => get_site_url(),
            'wp_version'     => get_bloginfo( 'version' ),
            'total_posts'    => wp_count_posts( 'post' )->publish,
            'draft_posts'    => wp_count_posts( 'post' )->draft,
            'total_pages'    => wp_count_posts( 'page' )->publish,
            'total_comments' => wp_count_comments()->approved,
            'theme'          => $theme->get( 'Name' ),
            'plugins'        => $active_plugins,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // CUSTOM POST TYPES & ACF
    // ─────────────────────────────────────────────────────────────────

    /**
     * Valida e sanitizza un valore ACF in base al tipo di campo.
     */
    private static function sanitize_acf_value( string $field_name, $value, int $post_id ) {
        $field_obj = get_field_object( $field_name, $post_id, false, false );
        if ( ! $field_obj ) return sanitize_text_field( $value );

        switch ( $field_obj['type'] ) {
            case 'number':
            case 'range':
                return is_numeric( $value ) ? floatval( $value ) : null;
            case 'email':
                return sanitize_email( $value ) ?: null;
            case 'url':
                return esc_url_raw( $value ) ?: null;
            case 'true_false':
                return (bool) $value;
            case 'select':
            case 'radio':
            case 'button_group':
                if ( ! empty( $field_obj['choices'] ) && ! array_key_exists( $value, $field_obj['choices'] ) ) return null;
                return sanitize_text_field( $value );
            case 'textarea':
            case 'wysiwyg':
                return wp_kses_post( $value );
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Scrive i campi ACF validati su un post. Restituisce i nomi dei campi aggiornati.
     */
    private static function update_acf_fields( array $fields, int $post_id ): array {
        $updated = [];
        foreach ( $fields as $key => $value ) {
            $clean_key   = sanitize_text_field( $key );
            $clean_value = self::sanitize_acf_value( $clean_key, $value, $post_id );
            if ( $clean_value === null ) continue;
            update_field( $clean_key, $clean_value, $post_id );
            $updated[] = $clean_key;
        }
        return $updated;
    }

    private static function get_custom_post_types(): array {
        $cpts = get_post_types( [ '_builtin' => false, 'public' => true ], 'objects' );
        $result = [];

        foreach ( $cpts as $cpt ) {
            $entry = [
                'slug'  => $cpt->name,
                'label' => $cpt->label,
                'count' => wp_count_posts( $cpt->name )->publish ?? 0,
            ];

            // Campi ACF associati
            if ( function_exists( 'acf_get_field_groups' ) ) {
                $groups = acf_get_field_groups( [ 'post_type' => $cpt->name ] );
                $fields = [];
                foreach ( $groups as $group ) {
                    $group_fields = acf_get_fields( $group['key'] );
                    if ( ! $group_fields ) continue;
                    foreach ( $group_fields as $f ) {
                        $field_info = [
                            'name' => $f['name'],
                            'label' => $f['label'],
                            'type'  => $f['type'],
                        ];
                        if ( ! empty( $f['choices'] ) ) {
                            $field_info['choices'] = $f['choices'];
                        }
                        if ( ! empty( $f['required'] ) ) {
                            $field_info['required'] = true;
                        }
                        $fields[] = $field_info;
                    }
                }
                if ( $fields ) {
                    $entry['acf_fields'] = $fields;
                }
            }

            $result[] = $entry;
        }

        if ( empty( $result ) ) {
            return [ 'success' => true, 'message' => 'Nessun Custom Post Type trovato.', 'post_types' => [] ];
        }

        return [ 'success' => true, 'post_types' => $result ];
    }

    private static function create_custom_post( array $args ): array {
        $post_type = sanitize_text_field( $args['post_type'] ?? '' );
        if ( ! post_type_exists( $post_type ) ) {
            return [ 'error' => "Post type \"$post_type\" non esiste." ];
        }

        $status  = in_array( $args['status'] ?? 'draft', [ 'draft', 'publish', 'private' ] ) ? $args['status'] : 'draft';
        $content = $args['content'] ?? '';
        if ( $content && strpos( $content, '<' ) === false ) {
            $content = wpautop( $content );
        }

        $post_id = wp_insert_post( [
            'post_title'   => sanitize_text_field( $args['title'] ),
            'post_content' => wp_kses_post( $content ),
            'post_status'  => $status,
            'post_type'    => $post_type,
        ], true );

        if ( is_wp_error( $post_id ) ) {
            return [ 'error' => $post_id->get_error_message() ];
        }

        // Popola campi ACF
        $fields_updated = [];
        if ( ! empty( $args['fields'] ) && function_exists( 'update_field' ) ) {
            $fields_updated = self::update_acf_fields( $args['fields'], $post_id );
        }

        $cpt_obj = get_post_type_object( $post_type );
        $label   = $cpt_obj ? $cpt_obj->labels->singular_name : $post_type;

        return [
            'success'        => true,
            'post_id'        => $post_id,
            'title'          => $args['title'],
            'post_type'      => $post_type,
            'status'         => $status,
            'fields_updated' => $fields_updated,
            'edit_url'       => get_edit_post_link( $post_id, 'raw' ),
            'message'        => "$label \"" . $args['title'] . "\" creato in stato \"$status\" (ID: $post_id)."
                              . ( $fields_updated ? ' Campi ACF compilati: ' . implode( ', ', $fields_updated ) . '.' : '' ),
        ];
    }

    private static function update_custom_post( array $args ): array {
        $post_id = (int) $args['post_id'];
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return [ 'error' => "Post con ID $post_id non trovato." ];
        }

        $update = [ 'ID' => $post_id ];
        if ( isset( $args['title'] ) )   $update['post_title']   = sanitize_text_field( $args['title'] );
        if ( isset( $args['content'] ) ) $update['post_content'] = wp_kses_post( wpautop( $args['content'] ) );
        if ( isset( $args['status'] ) && in_array( $args['status'], [ 'draft', 'publish', 'private' ] ) ) {
            $update['post_status'] = $args['status'];
        }

        if ( count( $update ) > 1 ) {
            $result = wp_update_post( $update, true );
            if ( is_wp_error( $result ) ) {
                return [ 'error' => $result->get_error_message() ];
            }
        }

        // Aggiorna campi ACF
        $fields_updated = [];
        if ( ! empty( $args['fields'] ) && function_exists( 'update_field' ) ) {
            $fields_updated = self::update_acf_fields( $args['fields'], $post_id );
        }

        return [
            'success'        => true,
            'post_id'        => $post_id,
            'fields_updated' => $fields_updated,
            'message'        => "Post \"" . get_the_title( $post_id ) . "\" (ID: $post_id) aggiornato."
                              . ( $fields_updated ? ' Campi ACF aggiornati: ' . implode( ', ', $fields_updated ) . '.' : '' ),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // COMMENTI
    // ─────────────────────────────────────────────────────────────────

    private static function get_comments( array $args ): array {
        $limit   = min( (int) ( $args['limit'] ?? 10 ), 30 );
        $status  = $args['status'] ?? 'all';
        $query   = [ 'number' => $limit, 'status' => $status, 'orderby' => 'comment_date', 'order' => 'DESC' ];

        if ( ! empty( $args['post_id'] ) ) {
            $query['post_id'] = (int) $args['post_id'];
        }

        $comments = get_comments( $query );
        if ( empty( $comments ) ) {
            return [ 'success' => true, 'count' => 0, 'comments' => [], 'message' => 'Nessun commento trovato.' ];
        }

        $result = [];
        foreach ( $comments as $c ) {
            $result[] = [
                'id'      => $c->comment_ID,
                'author'  => $c->comment_author,
                'email'   => $c->comment_author_email,
                'content' => wp_trim_words( $c->comment_content, 30 ),
                'status'  => wp_get_comment_status( $c ),
                'date'    => $c->comment_date,
                'post_id' => $c->comment_post_ID,
                'post'    => get_the_title( $c->comment_post_ID ),
            ];
        }

        return [ 'success' => true, 'count' => count( $result ), 'comments' => $result ];
    }

    private static function moderate_comment( array $args ): array {
        $comment_id = (int) $args['comment_id'];
        $action     = $args['action'] ?? '';

        $comment = get_comment( $comment_id );
        if ( ! $comment ) {
            return [ 'error' => "Commento con ID $comment_id non trovato." ];
        }

        $map = [ 'approve' => '1', 'spam' => 'spam', 'trash' => 'trash' ];
        if ( ! isset( $map[ $action ] ) ) {
            return [ 'error' => "Azione \"$action\" non valida. Usa: approve, spam, trash." ];
        }

        $old_status = $comment->comment_approved;

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- direct update needed
        $wpdb->update(
            $wpdb->comments,
            [ 'comment_approved' => $map[ $action ] ],
            [ 'comment_ID' => $comment_id ],
            [ '%s' ],
            [ '%d' ]
        );
        clean_comment_cache( $comment_id );
        wp_update_comment_count_now( $comment->comment_post_ID );

        $labels = [ 'approve' => 'approvato', 'spam' => 'segnalato come spam', 'trash' => 'eliminato' ];
        return [
            'success'    => true,
            'old_status' => $old_status,
            'new_status' => $map[ $action ],
            'message'    => "Commento #$comment_id di \"{$comment->comment_author}\" {$labels[$action]}.",
        ];
    }

    private static function reply_comment( array $args ): array {
        $comment_id = (int) $args['comment_id'];
        $content    = $args['content'] ?? '';

        $parent = get_comment( $comment_id );
        if ( ! $parent ) {
            return [ 'error' => "Commento con ID $comment_id non trovato." ];
        }

        $user = wp_get_current_user();
        $new_id = wp_insert_comment( [
            'comment_post_ID' => $parent->comment_post_ID,
            'comment_parent'  => $comment_id,
            'comment_content' => wp_kses_post( $content ),
            'comment_author'  => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_approved' => 1,
            'user_id'         => $user->ID,
        ] );

        if ( ! $new_id ) {
            return [ 'error' => 'Impossibile inserire la risposta.' ];
        }

        return [
            'success' => true,
            'comment_id' => $new_id,
            'message' => "Risposta al commento #$comment_id pubblicata (ID: $new_id).",
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // IMPOSTAZIONI SITO
    // ─────────────────────────────────────────────────────────────────

    private static function update_site_settings( array $args ): array {
        $allowed = [ 'blogname', 'blogdescription', 'posts_per_page', 'default_comment_status' ];
        $updated = [];

        foreach ( $allowed as $key ) {
            if ( ! isset( $args[ $key ] ) ) continue;

            $value = $key === 'posts_per_page' ? absint( $args[ $key ] ) : sanitize_text_field( $args[ $key ] );
            update_option( $key, $value );
            $updated[] = $key;
        }

        if ( empty( $updated ) ) {
            return [ 'error' => 'Nessuna impostazione da aggiornare.' ];
        }

        return [
            'success' => true,
            'updated' => $updated,
            'message' => 'Impostazioni aggiornate: ' . implode( ', ', $updated ) . '.',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // UTENTI
    // ─────────────────────────────────────────────────────────────────

    private static function tool_get_users( array $args ): array {
        $query = [ 'number' => min( (int) ( $args['limit'] ?? 10 ), 30 ), 'orderby' => 'registered', 'order' => 'DESC' ];
        if ( ! empty( $args['role'] ) ) $query['role'] = sanitize_text_field( $args['role'] );

        $users  = get_users( $query );
        $result = [];
        foreach ( $users as $u ) {
            $result[] = [
                'id'    => $u->ID,
                'login' => $u->user_login,
                'name'  => $u->display_name,
                'email' => $u->user_email,
                'role'  => implode( ', ', $u->roles ),
                'registered' => $u->user_registered,
            ];
        }

        return [ 'success' => true, 'count' => count( $result ), 'users' => $result ];
    }

    private static function tool_create_user( array $args ): array {
        $username = sanitize_user( $args['username'] ?? '' );
        $email    = sanitize_email( $args['email'] ?? '' );
        $role     = sanitize_text_field( $args['role'] ?? 'subscriber' );

        // Non permettere la creazione di administrator via chat
        if ( $role === 'administrator' ) {
            return [ 'error' => 'Non è possibile creare utenti administrator dalla chat per motivi di sicurezza.' ];
        }

        $allowed_roles = [ 'editor', 'author', 'contributor', 'subscriber' ];
        if ( ! in_array( $role, $allowed_roles, true ) ) $role = 'subscriber';

        $password = wp_generate_password( 16 );
        $user_id  = wp_insert_user( [
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'display_name' => sanitize_text_field( $args['name'] ?? $username ),
            'role'         => $role,
        ] );

        if ( is_wp_error( $user_id ) ) {
            return [ 'error' => $user_id->get_error_message() ];
        }

        return [
            'success' => true,
            'user_id' => $user_id,
            'message' => "Utente \"$username\" creato con ruolo \"$role\" (ID: $user_id). Password generata automaticamente.",
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // WOOCOMMERCE
    // ─────────────────────────────────────────────────────────────────

    private static function tool_get_products( array $args ): array {
        if ( ! class_exists( 'WooCommerce' ) ) return [ 'error' => 'WooCommerce non è attivo.' ];

        $limit  = min( (int) ( $args['limit'] ?? 10 ), 30 );
        $status = $args['status'] ?? 'publish';

        $products = wc_get_products( [ 'limit' => $limit, 'status' => $status, 'orderby' => 'date', 'order' => 'DESC' ] );
        $result   = [];

        foreach ( $products as $p ) {
            $result[] = [
                'id'    => $p->get_id(),
                'name'  => $p->get_name(),
                'price' => $p->get_price(),
                'stock' => $p->get_stock_status(),
                'status'=> $p->get_status(),
                'type'  => $p->get_type(),
            ];
        }

        return [ 'success' => true, 'count' => count( $result ), 'products' => $result ];
    }

    private static function tool_create_product( array $args ): array {
        if ( ! class_exists( 'WooCommerce' ) ) return [ 'error' => 'WooCommerce non è attivo.' ];

        $product = new \WC_Product_Simple();
        $product->set_name( sanitize_text_field( $args['name'] ) );
        $product->set_regular_price( sanitize_text_field( $args['price'] ) );
        $product->set_status( in_array( $args['status'] ?? 'draft', [ 'draft', 'publish' ] ) ? $args['status'] : 'draft' );

        if ( ! empty( $args['description'] ) ) {
            $product->set_description( wp_kses_post( $args['description'] ) );
        }

        $id = $product->save();

        return [
            'success'    => true,
            'product_id' => $id,
            'edit_url'   => get_edit_post_link( $id, 'raw' ),
            'message'    => "Prodotto \"{$args['name']}\" creato a €{$args['price']} (ID: $id).",
        ];
    }

    private static function tool_get_orders( array $args ): array {
        if ( ! class_exists( 'WooCommerce' ) ) return [ 'error' => 'WooCommerce non è attivo.' ];

        $limit  = min( (int) ( $args['limit'] ?? 10 ), 30 );
        $status = $args['status'] ?? 'any';

        $orders = wc_get_orders( [ 'limit' => $limit, 'status' => $status, 'orderby' => 'date', 'order' => 'DESC' ] );
        $result = [];

        foreach ( $orders as $o ) {
            $result[] = [
                'id'     => $o->get_id(),
                'status' => $o->get_status(),
                'total'  => $o->get_total(),
                'date'   => $o->get_date_created() ? $o->get_date_created()->date( 'Y-m-d H:i' ) : '',
                'customer' => $o->get_billing_first_name() . ' ' . $o->get_billing_last_name(),
                'items'  => $o->get_item_count(),
            ];
        }

        return [ 'success' => true, 'count' => count( $result ), 'orders' => $result ];
    }

    // ─────────────────────────────────────────────────────────────────
    // MENU
    // ─────────────────────────────────────────────────────────────────

    private static function tool_get_menus(): array {
        $result = [];

        // Menu classici (wp_nav_menus)
        $menus = wp_get_nav_menus();
        if ( empty( $menus ) ) {
            // Fallback: cerca direttamente nella tassonomia nav_menu
            $menus = get_terms( [ 'taxonomy' => 'nav_menu', 'hide_empty' => false ] );
        }
        foreach ( $menus as $menu ) {
            $items     = wp_get_nav_menu_items( $menu->term_id ) ?: [];
            $menu_data = [
                'id'     => $menu->term_id,
                'name'   => $menu->name,
                'type'   => 'classic',
                'items'  => [],
            ];
            foreach ( $items as $item ) {
                $menu_data['items'][] = [
                    'id'    => $item->ID,
                    'title' => $item->title,
                    'url'   => $item->url,
                    'type'  => $item->type,
                ];
            }
            $result[] = $menu_data;
        }

        // Menu block-based (wp_navigation post type — temi FSE)
        $nav_posts = get_posts( [ 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'posts_per_page' => -1 ] );
        foreach ( $nav_posts as $nav ) {
            $result[] = [
                'id'     => $nav->ID,
                'name'   => $nav->post_title,
                'type'   => 'block',
                'content' => $nav->post_content,
            ];
        }

        if ( empty( $result ) ) {
            return [ 'success' => true, 'message' => 'Nessun menu trovato.', 'menus' => [] ];
        }

        return [ 'success' => true, 'menus' => $result ];
    }

    private static function tool_add_menu_item( array $args ): array {
        $menu_id = (int) $args['menu_id'];
        $title   = sanitize_text_field( $args['title'] ?? '' );

        if ( ! wp_get_nav_menu_object( $menu_id ) ) {
            return [ 'error' => "Menu con ID $menu_id non trovato." ];
        }

        $item_data = [
            'menu-item-title'  => $title,
            'menu-item-status' => 'publish',
        ];

        if ( ! empty( $args['page_id'] ) ) {
            $post = get_post( (int) $args['page_id'] );
            if ( ! $post ) return [ 'error' => "Post/pagina con ID {$args['page_id']} non trovato." ];
            $item_data['menu-item-object-id'] = $post->ID;
            $item_data['menu-item-object']    = $post->post_type;
            $item_data['menu-item-type']      = 'post_type';
        } elseif ( ! empty( $args['url'] ) ) {
            $item_data['menu-item-url']  = esc_url_raw( $args['url'] );
            $item_data['menu-item-type'] = 'custom';
        } else {
            return [ 'error' => 'Specifica un page_id o un url per la voce di menu.' ];
        }

        $item_id = wp_update_nav_menu_item( $menu_id, 0, $item_data );

        if ( is_wp_error( $item_id ) ) {
            return [ 'error' => $item_id->get_error_message() ];
        }

        return [
            'success' => true,
            'item_id' => $item_id,
            'message' => "Voce \"$title\" aggiunta al menu (ID voce: $item_id).",
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // COMPONENTI
    // ─────────────────────────────────────────────────────────────────

    private static function tool_create_component( array $args ): array {
        $slug        = sanitize_file_name( $args['slug'] ?? '' );
        $name        = sanitize_text_field( $args['name'] ?? '' );
        $editor      = sanitize_text_field( $args['editor'] ?? '' );
        $description = $args['description'] ?? '';

        if ( empty( $slug ) || empty( $name ) || empty( $description ) ) {
            return [ 'error' => 'Slug, nome e descrizione sono obbligatori.' ];
        }

        $allowed_editors = [ 'wpbakery', 'elementor', 'gutenberg', 'divi' ];
        if ( ! in_array( $editor, $allowed_editors, true ) ) {
            return [ 'error' => "Editor \"$editor\" non supportato." ];
        }

        // Recupera la documentazione dell'editor dalla knowledge base
        $kb_docs = SiteGenie_Knowledge::search( "Regole moduli $editor widget", 3000 );

        // Genera il codice con una chiamata API dedicata (senza tool calling)
        $connector = SiteGenie_Admin::get_connector();
        if ( ! $connector ) return [ 'error' => 'Connettore AI non disponibile.' ];

        $prompt  = "Genera il codice completo per un componente $editor per WordPress.\n\n";
        $prompt .= "Nome componente: $name\n";
        $prompt .= "Slug: $slug\n";
        $prompt .= "Descrizione: $description\n\n";
        if ( $kb_docs ) {
            $prompt .= "DOCUMENTAZIONE DI RIFERIMENTO — DEVI seguire ESATTAMENTE questa struttura:\n$kb_docs\n\n";
        }
        $prompt .= "REGOLE OBBLIGATORIE:\n";
        $prompt .= "1. Genera SOLO codice, nessun testo di spiegazione, nessun commento fuori dal codice\n";
        $prompt .= "2. Separa i file con questi marcatori esatti su riga singola: ===PHP=== ===CSS=== ===JS===\n";
        $prompt .= "3. DEVI generare un componente per $editor — NON usare API di altri editor\n";
        if ( $editor === 'elementor' ) {
            $prompt .= "4. Il widget DEVE estendere \\Elementor\\Widget_Base con i metodi get_name(), get_title(), get_icon(), get_categories(), register_controls(), render()\n";
            $prompt .= "5. Registra il widget con add_action('elementor/widgets/register', ...)\n";
            $prompt .= "6. Registra la categoria 'sitegenie-components' con add_action('elementor/elements/categories_registered', ...)\n";
            $prompt .= "7. NON usare vc_map, add_shortcode o qualsiasi funzione di WPBakery\n";
        } elseif ( $editor === 'wpbakery' ) {
            $prompt .= "4. Il PHP DEVE seguire la struttura della documentazione: classe con costruttore, add_shortcode, add_action vc_before_init, funzione map con vc_map, funzione render HTML\n";
            $prompt .= "5. Il PHP deve iniziare con il check if (!class_exists('WPBakeryShortCodesContainer')) return;\n";
            $prompt .= "6. NON usare \\Elementor\\Widget_Base o qualsiasi classe di Elementor\n";
        }
        $prompt .= "8. La categoria nel page builder deve essere 'Sitegenie components' (WPBakery) o 'sitegenie-components' (Elementor)\n";
        $prompt .= "9. Gli asset CSS/JS vanno registrati con wp_register_style/wp_register_script e caricati nel render con wp_enqueue_style/wp_enqueue_script\n";
        $prompt .= "10. Il percorso degli asset è: plugins_url('assets/css/$slug.css', __FILE__) e plugins_url('assets/js/$slug.js', __FILE__)\n";
        $prompt .= "11. NON mischiare CSS o JS dentro il file PHP — vanno nei file separati dopo i marcatori ===CSS=== e ===JS===\n";

        $response = $connector->generate( $prompt, [ 'max_tokens' => 4000, 'temperature' => 0.2 ] );
        if ( ! $response['success'] ) {
            return [ 'error' => 'Errore nella generazione del codice: ' . $response['error'] ];
        }

        // Parsa il codice generato
        $generated = $response['text'];

        // Rimuovi markdown code blocks
        $generated = preg_replace( '/```\w*\n?/', '', $generated );

        $php_code = '';
        $css_code = '';
        $js_code  = '';

        if ( strpos( $generated, '===PHP===' ) !== false ) {
            // Formato con marcatori
            $parts = preg_split( '/===(?:PHP|CSS|JS)===/', $generated );
            $php_code = trim( $parts[1] ?? '' );
            $css_code = trim( $parts[2] ?? '' );
            $js_code  = trim( $parts[3] ?? '' );
        } else {
            // Tutto è PHP
            $php_code = trim( $generated );
        }

        if ( empty( $php_code ) ) {
            return [ 'error' => 'Il codice PHP generato è vuoto.' ];
        }

        if ( strpos( trim( $php_code ), '<?php' ) !== 0 ) {
            $php_code = "<?php\n" . $php_code;
        }

        return SiteGenie_Components::create( $slug, $name, $editor, $php_code, $css_code, $js_code );
    }
}
