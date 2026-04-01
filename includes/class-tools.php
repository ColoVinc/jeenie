<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ChatPress_Tools
 *
 * Definisce i tool che Gemini può chiamare (function declarations)
 * ed esegue le funzioni WordPress corrispondenti.
 */
class ChatPress_Tools {

    /**
     * Restituisce le dichiarazioni dei tool da passare all'API Gemini.
     * Ogni tool ha: name, description, parameters (schema JSON).
     */
    public static function get_declarations(): array {
        return [

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

        ];
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
            case 'get_categories':  return self::get_categories();
            case 'get_site_info':   return self::get_site_info();
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
}