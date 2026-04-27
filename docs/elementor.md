# Regole Widget Elementor
- Ogni widget nuovo deve avere la propria cartella in /components (es. sg-nome-componente)
- 1 classe = 1 widget
- Tutti i widget estendono la classe `\Elementor\Widget_Base`
- Il prefisso "sg" va usato nel nome del widget, nelle classi e nelle funzioni custom
- La categoria del widget deve essere 'jeenie-components'

L'architettura di un widget Elementor ha la seguente struttura:
```plaintext
/                                 → Cartella principale del widget
/sg-nome-componente.php          → File principale del widget Elementor
├── assets
    ├── css                       → Contiene gli stili personalizzati del frontend
    ├── js                        → Contiene gli script personalizzati del frontend
```

# Struttura di un widget Elementor

Un widget Elementor è composto da:
1. **Registrazione** — Il widget viene registrato tramite l'hook `elementor/widgets/register`
2. **Classe widget** — Estende `\Elementor\Widget_Base` e implementa i metodi obbligatori
3. **Controlli** — Definiti nel metodo `register_controls()`, creano i campi nel pannello Elementor
4. **Render** — Il metodo `render()` genera l'HTML del frontend

## Metodi obbligatori della classe widget

- `get_name()` — Restituisce lo slug univoco del widget (es. 'sg_hero_section')
- `get_title()` — Restituisce il nome visualizzato nel pannello (es. 'SG Hero Section')
- `get_icon()` — Restituisce l'icona del widget (es. 'eicon-banner')
- `get_categories()` — Restituisce le categorie (usare `['jeenie-components']`)
- `register_controls()` — Registra i controlli (campi) del widget
- `render()` — Genera l'HTML del frontend

# Esempio completo di widget Elementor

## File principale: sg-hero-section.php

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registra il widget con Elementor
 */
function sg_hero_section_register_widget( $widgets_manager ) {
    $widgets_manager->register( new \Sg_Hero_Section_Widget() );
}
add_action( 'elementor/widgets/register', 'sg_hero_section_register_widget' );

/**
 * Registra la categoria custom 'jeenie-components' se non esiste
 */
function sg_hero_section_register_category( $elements_manager ) {
    $elements_manager->add_category(
        'jeenie-components',
        [
            'title' => 'Jeenie Components',
            'icon'  => 'fa fa-puzzle-piece',
        ]
    );
}
add_action( 'elementor/elements/categories_registered', 'sg_hero_section_register_category' );

/**
 * Registra stili e script del widget
 */
function sg_hero_section_styles() {
    wp_register_style( 'sg-hero-section', plugins_url( 'assets/css/sg-hero-section.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'sg_hero_section_styles' );

function sg_hero_section_scripts() {
    wp_register_script( 'sg-hero-section', plugins_url( 'assets/js/sg-hero-section.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'sg_hero_section_scripts' );

/**
 * Classe del widget
 */
class Sg_Hero_Section_Widget extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'sg_hero_section';
    }

    public function get_title(): string {
        return esc_html__( 'SG Hero Section', 'jeenie' );
    }

    public function get_icon(): string {
        return 'eicon-banner';
    }

    public function get_categories(): array {
        return [ 'jeenie-components' ];
    }

    public function get_keywords(): array {
        return [ 'hero', 'banner', 'header', 'jeenie' ];
    }

    /**
     * Registrazione dei controlli (campi nel pannello Elementor)
     */
    protected function register_controls(): void {

        // === TAB CONTENUTO ===

        $this->start_controls_section(
            'sg_content_section',
            [
                'label' => esc_html__( 'Contenuto', 'jeenie' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Controllo testo
        $this->add_control(
            'sg_title',
            [
                'label'       => esc_html__( 'Titolo', 'jeenie' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => esc_html__( 'Benvenuto nel nostro sito', 'jeenie' ),
                'label_block' => true,
            ]
        );

        // Controllo textarea
        $this->add_control(
            'sg_subtitle',
            [
                'label'       => esc_html__( 'Sottotitolo', 'jeenie' ),
                'type'        => \Elementor\Controls_Manager::TEXTAREA,
                'default'     => esc_html__( 'Scopri i nostri servizi e prodotti', 'jeenie' ),
            ]
        );

        // Controllo immagine
        $this->add_control(
            'sg_background_image',
            [
                'label'   => esc_html__( 'Immagine di sfondo', 'jeenie' ),
                'type'    => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        // Controllo URL/link
        $this->add_control(
            'sg_button_link',
            [
                'label'       => esc_html__( 'Link bottone', 'jeenie' ),
                'type'        => \Elementor\Controls_Manager::URL,
                'default'     => [
                    'url'         => '#',
                    'is_external' => false,
                    'nofollow'    => false,
                ],
            ]
        );

        // Controllo testo bottone
        $this->add_control(
            'sg_button_text',
            [
                'label'   => esc_html__( 'Testo bottone', 'jeenie' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__( 'Scopri di più', 'jeenie' ),
            ]
        );

        // Controllo select/dropdown
        $this->add_control(
            'sg_text_align',
            [
                'label'   => esc_html__( 'Allineamento testo', 'jeenie' ),
                'type'    => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left'   => [
                        'title' => esc_html__( 'Sinistra', 'jeenie' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Centro', 'jeenie' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => esc_html__( 'Destra', 'jeenie' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .sg-hero-section' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // === TAB STILE ===

        $this->start_controls_section(
            'sg_style_section',
            [
                'label' => esc_html__( 'Stile', 'jeenie' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Controllo colore
        $this->add_control(
            'sg_title_color',
            [
                'label'     => esc_html__( 'Colore titolo', 'jeenie' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .sg-hero-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Controllo tipografia
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'sg_title_typography',
                'selector' => '{{WRAPPER}} .sg-hero-title',
            ]
        );

        // Controllo dimensione (slider)
        $this->add_control(
            'sg_min_height',
            [
                'label'      => esc_html__( 'Altezza minima', 'jeenie' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [ 'min' => 100, 'max' => 1000 ],
                    'vh' => [ 'min' => 10, 'max' => 100 ],
                ],
                'default'    => [ 'unit' => 'vh', 'size' => 60 ],
                'selectors'  => [
                    '{{WRAPPER}} .sg-hero-section' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Controllo overlay
        $this->add_control(
            'sg_overlay_color',
            [
                'label'     => esc_html__( 'Colore overlay', 'jeenie' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'rgba(0,0,0,0.5)',
                'selectors' => [
                    '{{WRAPPER}} .sg-hero-overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render del widget nel frontend
     */
    protected function render(): void {
        $settings = $this->get_settings_for_display();

        wp_enqueue_style( 'sg-hero-section' );
        wp_enqueue_script( 'sg-hero-section' );

        $bg_url = ! empty( $settings['sg_background_image']['url'] ) ? $settings['sg_background_image']['url'] : '';
        ?>
        <div class="sg-hero-section" style="background-image: url('<?php echo esc_url( $bg_url ); ?>');">
            <div class="sg-hero-overlay"></div>
            <div class="sg-hero-content">
                <?php if ( ! empty( $settings['sg_title'] ) ) : ?>
                    <h1 class="sg-hero-title"><?php echo esc_html( $settings['sg_title'] ); ?></h1>
                <?php endif; ?>
                <?php if ( ! empty( $settings['sg_subtitle'] ) ) : ?>
                    <p class="sg-hero-subtitle"><?php echo esc_html( $settings['sg_subtitle'] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $settings['sg_button_text'] ) ) : ?>
                    <?php
                    $target   = $settings['sg_button_link']['is_external'] ? ' target="_blank"' : '';
                    $nofollow = $settings['sg_button_link']['nofollow'] ? ' rel="nofollow"' : '';
                    ?>
                    <a href="<?php echo esc_url( $settings['sg_button_link']['url'] ); ?>"<?php echo $target . $nofollow; ?> class="sg-hero-button">
                        <?php echo esc_html( $settings['sg_button_text'] ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
```

# Tipi di controlli Elementor disponibili

## Controlli di contenuto (TAB_CONTENT)
- `Controls_Manager::TEXT` — Campo di testo singolo
- `Controls_Manager::TEXTAREA` — Area di testo multilinea
- `Controls_Manager::WYSIWYG` — Editor visuale (TinyMCE)
- `Controls_Manager::NUMBER` — Campo numerico
- `Controls_Manager::URL` — Campo URL con opzioni target e nofollow
- `Controls_Manager::MEDIA` — Selettore media (immagini, video)
- `Controls_Manager::GALLERY` — Galleria immagini
- `Controls_Manager::SELECT` — Menu a tendina
- `Controls_Manager::SELECT2` — Menu a tendina con ricerca
- `Controls_Manager::CHOOSE` — Scelta con icone (es. allineamento)
- `Controls_Manager::SWITCHER` — Toggle on/off
- `Controls_Manager::REPEATER` — Campo ripetitore per liste dinamiche
- `Controls_Manager::ICONS` — Selettore icone
- `Controls_Manager::DATE_TIME` — Selettore data e ora
- `Controls_Manager::CODE` — Editor di codice

## Controlli di stile (TAB_STYLE)
- `Controls_Manager::COLOR` — Selettore colore
- `Controls_Manager::SLIDER` — Slider numerico con unità (px, em, %, vh)
- `Controls_Manager::DIMENSIONS` — Padding/margin (top, right, bottom, left)
- `Group_Control_Typography::get_type()` — Controllo tipografia completo (font, size, weight, ecc.)
- `Group_Control_Text_Shadow::get_type()` — Ombra testo
- `Group_Control_Box_Shadow::get_type()` — Ombra box
- `Group_Control_Border::get_type()` — Bordi
- `Group_Control_Background::get_type()` — Sfondo (colore, gradiente, immagine)

# Esempio di widget con Repeater

```php
// Dentro register_controls()

$repeater = new \Elementor\Repeater();

$repeater->add_control(
    'sg_item_title',
    [
        'label'       => esc_html__( 'Titolo', 'jeenie' ),
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => esc_html__( 'Elemento', 'jeenie' ),
        'label_block' => true,
    ]
);

$repeater->add_control(
    'sg_item_icon',
    [
        'label'   => esc_html__( 'Icona', 'jeenie' ),
        'type'    => \Elementor\Controls_Manager::ICONS,
        'default' => [
            'value'   => 'fas fa-star',
            'library' => 'fa-solid',
        ],
    ]
);

$this->add_control(
    'sg_items_list',
    [
        'label'       => esc_html__( 'Elementi', 'jeenie' ),
        'type'        => \Elementor\Controls_Manager::REPEATER,
        'fields'      => $repeater->get_controls(),
        'default'     => [
            [ 'sg_item_title' => esc_html__( 'Elemento 1', 'jeenie' ) ],
            [ 'sg_item_title' => esc_html__( 'Elemento 2', 'jeenie' ) ],
            [ 'sg_item_title' => esc_html__( 'Elemento 3', 'jeenie' ) ],
        ],
        'title_field' => '{{{ sg_item_title }}}',
    ]
);

// Dentro render()
foreach ( $settings['sg_items_list'] as $item ) {
    echo '<div class="sg-item">';
    \Elementor\Icons_Manager::render_icon( $item['sg_item_icon'], [ 'aria-hidden' => 'true' ] );
    echo '<h3>' . esc_html( $item['sg_item_title'] ) . '</h3>';
    echo '</div>';
}
```

# Note importanti
- Il widget viene registrato tramite l'hook `elementor/widgets/register`
- La categoria custom viene registrata tramite `elementor/elements/categories_registered`
- Gli stili e script vanno registrati con `wp_register_style` / `wp_register_script` e caricati nel metodo `render()` con `wp_enqueue_style` / `wp_enqueue_script`
- I selectors CSS usano `{{WRAPPER}}` come prefisso per lo scope automatico di Elementor
- Il prefisso `sg_` va usato per tutti i param_name dei controlli
- La classe del widget deve avere il prefisso `Sg_` (es. `Sg_Hero_Section_Widget`)
