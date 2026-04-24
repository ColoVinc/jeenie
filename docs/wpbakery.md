# Regole Moduli WPBakery
- Ogni modulo nuovo deve avere la propria cartella in /components (es. sg-nome-componente)
- 1 classe = 1 modulo
- Il plug-in di riferimento si trova nella cartella di Wordpress /plugins/js_composer

L'architettura di un modulo di WPBakery ha la seguente struttura:
```plaintext
/                                 → Cartella principale del modulo. Contiene il file principale del modulo di WPBakery. Il nome della cartella rispetta la convenzione sg-[nome-componente]
/sg-complex-module-example.php   → File principale del modulo personalizzato
├── assets                        → Contiene le cartelle degli asset riferiti al frontend
    ├── css                       → Contiene gli stili personalizzati del frontend
    ├── js                        → Contiene gli script personalizzati del frontend
```

# Esempio di modulo personalizzato

- sg-complex-module-example.php
```php

<?php

if (!class_exists('WPBakeryShortCodesContainer')) {
    return;
}

if ( defined( 'WPB_VC_VERSION' ) ) {

    /* L'inclusione di queste classi è necessaria solo quando si crea un elemento contenitore. */

    require_once(ABSPATH .'wp-content/plugins/js_composer/include/classes/shortcodes/core/class-wpbakery-visualcomposer-abstract.php');
    require_once(ABSPATH .'wp-content/plugins/js_composer/include/classes/shortcodes/core/class-wpbakeryshortcode.php');
    require_once(ABSPATH .'wp-content/plugins/js_composer/include/classes/shortcodes/core/class-wpbakeryshortcodescontainer.php');
}

class Sg_Complex_Module_Example {
    public function __construct() {
    
        /*
        Nel costruttore vanno registrati:
            gli shortcode:
            - shortcode singolo -> elemento singolo
            - shortcode multipli -> 1 contenitore, 1 ... N elementi contenuti
            - gli script e gli stili del front-end.
         */
         
        add_shortcode('sg_complex_module_example_container', array($this, 'sg_complex_module_example_container_html'));
        add_shortcode('sg_complex_module_example_item', array($this, 'sg_complex_module_example_item_html'));

        add_action('vc_before_init', array($this, 'sg_complex_module_example_container_map'));
        add_action('vc_before_init', array($this, 'sg_complex_module_example_item_map'));

        function sg_complex_module_example_styles()
        {
            wp_register_style('sg-complex-module-example', plugins_url('assets/css/sg-complex-module-example.css', __FILE__));
        }
        add_action('wp_enqueue_scripts', 'sg_complex_module_example_styles');

        function sg_complex_module_example_scripts()
        {
            wp_register_style('sg-complex-module-example', plugins_url('assets/js/sg-complex-module-example.js', __FILE__));
        }
        add_action('wp_enqueue_scripts', 'sg_complex_module_example_scripts');
    }

	function sg_complex_module_example_container_map() {
	    
	    /* Registrazione parametri elemento */
	    
        vc_map(
                array(
                'name' => 'Contenitore Item',
                'base' => 'sg_complex_module_example_container',
                'as_parent' => array('only' => 'sg_complex_module_example_item'), // Parametro che specifica quali elementi può contenere
                'content_element' => true,
                'show_settings_on_create' => true,
                'category' => 'Sitegenie components',
                'is_container' => true,
                'params' => array(
                    /* Parametri di WPBakery */
                ),
            )
        );
    }

    function sg_complex_module_example_item_map() {
    
        /* Registrazione parametri elemento */
    
        vc_map(
            array(
                'name' => 'Item',
                'base' => 'sg_complex_module_example_item',
                'content_element' => true,
                'as_child' => array('only' => 'sg_complex_module_example'), // Parametro che specifica da quali contenitori può essere contenuto
                'js_view' => 'VcComplexModuleExampleView',
                'custom_markup' => '<div class="wpb_element_wrapper "><h4 class="wpb_element_title" style="margin-bottom: 10px !important;"><i class="vc_general vc_element-icon"></i>[Titolo preso dal parametro "name"]</h4><span class="vc_admin_label admin_label_complex_module_example" style="display: inline;"></span></div>',
                'params' => array(
                    array(
                        'type' => 'param_group',
                        'value' => '',
                        'param_name' => 'nome_gruppo_parametri',
                        "admin_label" => 'true',
                        "holder" => 'p',
                        'params' => array(
                            /* Parametri di WPBakery */
                        )
                    )
                )
            )
        );
    }

    public function sg_complex_module_example_container_html($atts, $content = null) {

        /* Funzione che renderizza l'HTML dell'elemento contenitore */
        
        wp_enqueue_style('sg-complex-module-example');
        wp_enqueue_script('sg-complex-module-example');

        extract( shortcode_atts( array(
            /* Estrazione variabili */
        ), $atts ) );

        $html = do_shortcode($content);

        return $html;
    }

    public function sg_complex_module_example_item_html($atts, $content = null) {

        /* Funzione che renderizza l'HTML dell'elemento contenuto (Se presente) */

        $html = '';

        extract( shortcode_atts( array(
            /* Estrazione variabili */
        ), $atts ) );

        return $html;

    }
}

if ( defined( 'WPB_VC_VERSION' ) ) {

    /* Dichiarazioni necessarie solo in caso di elemento contenitore */
    
    class WPBakeryShortCode_sg_complex_module_example extends WPBakeryShortCodesContainer {}
    class WPBakeryShortCode_sg_complex_module_example_item extends WPBakeryShortCode {}
}

if (class_exists('Sg_Complex_Module_Example')) {
    new Sg_Complex_Module_Example();
}


```
Note sul parametro "js_view":
Il parametro "js_view" serve a collegare un controller JavaScript (Backbone.js) personalizzato al tuo elemento del page builder nel backend (editor). WPBakery usa Backbone.js per gestire il comportamento dinamico degli elementi nell’editor visuale.
js_view permette di:
- Sovrascrivere il comportamento standard di un elemento
- Intercettare eventi (change di campi, click, ecc.)
- Modificare dinamicamente l’anteprima nell’editor
- Gestire logiche complesse lato admin

# Esempio di controller JavaScript (admin/js/sg-complex-module-script.js) associato al parametro "js_view"

```javascript
(function($) {
	window.VcComplexModuleExampleView = window.VcColumnView.extend({
		events: {
			'click > .vc_controls .vc_control-btn-edit': 'editElement',
			'click > .vc_controls .vc_control-btn-delete': 'deleteShortcode',
			'click > .vc_controls .vc_control-btn-clone': 'clone'
		},

		render: function() {
			window.VcComplexModuleExampleView.__super__.render.call(this);

			return this;
		},

		updateShortcodeLabel: function() {
			var gruppoParametri = this.model.getParam('nome_gruppo_parametri');
			var $label = this.$el.find('.admin_label_complex_module_example');

			/* Esempio di elaborazione */
			switch (true) {
				case !!gruppoParametri:
					var decoded = decodeURIComponent(gruppoParametri);
					var json = JSON.parse(decoded);
					var texts = json.map(function (p) {
						return '<span>' + p.module_categories + ' - ' + '</span>';
					});
					$label.html(texts);
					break;
			}
		},

		changeShortcodeParams: function (model) {
			window.VcComplexModuleExampleView.__super__.changeShortcodeParams.call(this, model);
			this.updateShortcodeLabel();
		},
	});
})(jQuery);
```