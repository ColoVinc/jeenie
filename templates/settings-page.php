<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap chatpress-settings">

    <div class="chatpress-header">
        <h1>🤖 ChatPress</h1>
        <p>Assistente AI per il tuo WordPress</p>
    </div>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'chatpress_settings' ); ?>

        <div class="chatpress-grid">

            <!-- COLONNA SINISTRA -->
            <div class="chatpress-col">

                <div class="chatpress-card">
                    <h2>🔑 Configurazione API</h2>

                    <table class="form-table">
                        <tr>
                            <th>API Key Gemini</th>
                            <td>
                                <input
                                    type="password"
                                    name="chatpress_gemini_api_key"
                                    value="<?php echo esc_attr( get_option('chatpress_gemini_api_key') ); ?>"
                                    class="regular-text"
                                    placeholder="AIza..."
                                />
                                <p class="description">
                                    Ottieni la tua chiave su <a href="https://aistudio.google.com" target="_blank">Google AI Studio</a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>Modello Gemini</th>
                            <td>
                                <select name="chatpress_gemini_model">
                                    <?php foreach ( ChatPress_Gemini::get_models() as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected( get_option('chatpress_gemini_model', 'gemini-2.0-flash'), $value ); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div class="chatpress-test-wrap">
                        <button type="button" id="chatpress-test-api" class="button button-secondary">
                            🔌 Testa Connessione
                        </button>
                        <span id="chatpress-test-result"></span>
                    </div>
                </div>

            </div>

            <!-- COLONNA DESTRA -->
            <div class="chatpress-col">

                <div class="chatpress-card">
                    <h2>🏢 Contesto del Sito</h2>
                    <p class="description">Queste informazioni vengono passate all'AI per generare contenuti coerenti con il tuo brand.</p>

                    <table class="form-table">
                        <tr>
                            <th>Nome Sito / Azienda</th>
                            <td>
                                <input
                                    type="text"
                                    name="chatpress_site_name"
                                    value="<?php echo esc_attr( get_option('chatpress_site_name', get_bloginfo('name')) ); ?>"
                                    class="regular-text"
                                />
                            </td>
                        </tr>
                        <tr>
                            <th>Settore</th>
                            <td>
                                <input
                                    type="text"
                                    name="chatpress_site_sector"
                                    value="<?php echo esc_attr( get_option('chatpress_site_sector') ); ?>"
                                    class="regular-text"
                                    placeholder="es. E-commerce abbigliamento, Studio legale..."
                                />
                            </td>
                        </tr>
                        <tr>
                            <th>Tono di voce</th>
                            <td>
                                <input
                                    type="text"
                                    name="chatpress_site_tone"
                                    value="<?php echo esc_attr( get_option('chatpress_site_tone') ); ?>"
                                    class="regular-text"
                                    placeholder="es. Professionale, Amichevole, Tecnico..."
                                />
                            </td>
                        </tr>
                        <tr>
                            <th>Pubblico Target</th>
                            <td>
                                <input
                                    type="text"
                                    name="chatpress_site_target"
                                    value="<?php echo esc_attr( get_option('chatpress_site_target') ); ?>"
                                    class="regular-text"
                                    placeholder="es. Professionisti 30-50 anni, Mamme, PMI..."
                                />
                            </td>
                        </tr>
                        <tr>
                            <th>Descrizione</th>
                            <td>
                                <textarea
                                    name="chatpress_site_description"
                                    class="large-text"
                                    rows="4"
                                    placeholder="Descrivi brevemente cosa fa l'azienda, i prodotti/servizi principali..."
                                ><?php echo esc_textarea( get_option('chatpress_site_description') ); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

            </div>
        </div>

        <?php submit_button( 'Salva Impostazioni' ); ?>

    </form>
</div>
