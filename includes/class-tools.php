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

            // ── IMMAGINI ─────────────────────────────────────────────

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
            case 'get_categories':        return self::get_categories();
            case 'get_site_info':         return self::get_site_info();
            case 'get_custom_post_types': return self::get_custom_post_types();
            case 'create_custom_post':    return self::create_custom_post( $args );
            case 'update_custom_post':    return self::update_custom_post( $args );
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
}
