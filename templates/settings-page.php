<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sitegenie-settings">

    <div class="sitegenie-header rounded-3 mb-4 d-flex align-items-baseline gap-3 p-4">
        <h1 class="text-white m-0 fs-4"><i class="fa-solid fa-robot"></i> SiteGenie</h1>
        <p class="text-white-50 m-0 small"><?php esc_html_e( 'Assistente AI per il tuo WordPress', 'sitegenie' ); ?></p>
    </div>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'sitegenie_settings' ); ?>

        <div class="row g-4 mb-4">

            <!-- COLONNA SINISTRA -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title fs-6 pb-2 border-bottom"><i class="fa-solid fa-key"></i> <?php esc_html_e( 'Configurazione API', 'sitegenie' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Provider AI', 'sitegenie' ); ?></th>
                                <td>
                                    <select name="sitegenie_default_provider" id="sitegenie-provider-select">
                                        <option value="gemini" <?php selected( get_option('sitegenie_default_provider', 'gemini'), 'gemini' ); ?>>Google Gemini</option>
                                        <option value="openai" <?php selected( get_option('sitegenie_default_provider', 'gemini'), 'openai' ); ?>>OpenAI (GPT)</option>
                                        <option value="claude" <?php selected( get_option('sitegenie_default_provider', 'gemini'), 'claude' ); ?>>Anthropic Claude</option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <!-- Gemini -->
                        <div id="sitegenie-provider-gemini" class="sitegenie-provider-section">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'API Key Gemini', 'sitegenie' ); ?></th>
                                    <td>
                                        <input type="password" name="sitegenie_gemini_api_key" value="<?php echo esc_attr( get_option('sitegenie_gemini_api_key') ); ?>" class="regular-text" placeholder="AIza..." />
                                        <?php // translators: %s is a link to Google AI Studio ?>
                                        <p class="description"><?php echo wp_kses( sprintf( __( 'Ottieni la tua chiave su %s', 'sitegenie' ), '<a href="https://aistudio.google.com" target="_blank">Google AI Studio</a>' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Modello Gemini', 'sitegenie' ); ?></th>
                                    <td>
                                        <select name="sitegenie_gemini_model">
                                            <?php foreach ( SiteGenie_Gemini::get_models() as $sitegenie_value => $sitegenie_label ) : ?>
                                                <option value="<?php echo esc_attr($sitegenie_value); ?>" <?php selected( get_option('sitegenie_gemini_model', 'gemini-2.0-flash'), $sitegenie_value ); ?>><?php echo esc_html($sitegenie_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- OpenAI -->
                        <div id="sitegenie-provider-openai" class="sitegenie-provider-section" style="display:none;">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'API Key OpenAI', 'sitegenie' ); ?></th>
                                    <td>
                                        <input type="password" name="sitegenie_openai_api_key" value="<?php echo esc_attr( get_option('sitegenie_openai_api_key') ); ?>" class="regular-text" placeholder="sk-..." />
                                        <?php // translators: %s is a link to OpenAI Platform ?>
                                        <p class="description"><?php echo wp_kses( sprintf( __( 'Ottieni la tua chiave su %s', 'sitegenie' ), '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Modello OpenAI', 'sitegenie' ); ?></th>
                                    <td>
                                        <select name="sitegenie_openai_model">
                                            <?php foreach ( SiteGenie_OpenAI::get_models() as $sitegenie_value => $sitegenie_label ) : ?>
                                                <option value="<?php echo esc_attr($sitegenie_value); ?>" <?php selected( get_option('sitegenie_openai_model', 'gpt-4o-mini'), $sitegenie_value ); ?>><?php echo esc_html($sitegenie_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Claude -->
                        <div id="sitegenie-provider-claude" class="sitegenie-provider-section" style="display:none;">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'API Key Claude', 'sitegenie' ); ?></th>
                                    <td>
                                        <input type="password" name="sitegenie_claude_api_key" value="<?php echo esc_attr( get_option('sitegenie_claude_api_key') ); ?>" class="regular-text" placeholder="sk-ant-..." />
                                        <?php // translators: %s is a link to Anthropic Console ?>
                                        <p class="description"><?php echo wp_kses( sprintf( __( 'Ottieni la tua chiave su %s', 'sitegenie' ), '<a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Modello Claude', 'sitegenie' ); ?></th>
                                    <td>
                                        <select name="sitegenie_claude_model">
                                            <?php foreach ( SiteGenie_Claude::get_models() as $sitegenie_value => $sitegenie_label ) : ?>
                                                <option value="<?php echo esc_attr($sitegenie_value); ?>" <?php selected( get_option('sitegenie_claude_model', 'claude-sonnet-4-20250514'), $sitegenie_value ); ?>><?php echo esc_html($sitegenie_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Limite richieste/ora', 'sitegenie' ); ?></th>
                                <td>
                                    <input type="number" name="sitegenie_rate_limit" value="<?php echo esc_attr( get_option('sitegenie_rate_limit', 30) ); ?>" min="0" max="1000" class="small-text" />
                                    <p class="description"><?php esc_html_e( 'Massimo richieste API per utente all\'ora. 0 = nessun limite.', 'sitegenie' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Elimina chat vecchie', 'sitegenie' ); ?></th>
                                <td>
                                    <input type="number" name="sitegenie_auto_delete_days" value="<?php echo esc_attr( get_option('sitegenie_auto_delete_days', 0) ); ?>" min="0" max="365" class="small-text" />
                                    <p class="description"><?php esc_html_e( 'Elimina automaticamente le conversazioni più vecchie di X giorni. 0 = mai.', 'sitegenie' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Timeout API (secondi)', 'sitegenie' ); ?></th>
                                <td>
                                    <input type="number" name="sitegenie_api_timeout" value="<?php echo esc_attr( get_option('sitegenie_api_timeout', 30) ); ?>" min="10" max="120" class="small-text" />
                                    <p class="description"><?php esc_html_e( 'Tempo massimo di attesa per le risposte AI. Default: 30 secondi.', 'sitegenie' ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <div class="d-flex align-items-center gap-2 mt-3">
                            <button type="button" id="sitegenie-test-api" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-plug"></i> <?php esc_html_e( 'Testa Connessione', 'sitegenie' ); ?>
                            </button>
                            <span id="sitegenie-test-result"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLONNA DESTRA -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title fs-6 pb-2 border-bottom"><i class="fa-solid fa-laptop-code"></i> <?php esc_html_e( 'Contesto del Sito', 'sitegenie' ); ?></h2>
                        <p class="text-muted small"><?php esc_html_e( 'Queste informazioni vengono passate all\'AI per generare contenuti coerenti con il tuo brand.', 'sitegenie' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Nome Sito / Azienda', 'sitegenie' ); ?></th>
                                <td><input type="text" name="sitegenie_site_name" value="<?php echo esc_attr( get_option('sitegenie_site_name', get_bloginfo('name')) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Settore', 'sitegenie' ); ?></th>
                                <td><input type="text" name="sitegenie_site_sector" value="<?php echo esc_attr( get_option('sitegenie_site_sector') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'es. E-commerce abbigliamento, Studio legale...', 'sitegenie' ); ?>" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Tono di voce', 'sitegenie' ); ?></th>
                                <td><input type="text" name="sitegenie_site_tone" value="<?php echo esc_attr( get_option('sitegenie_site_tone') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'es. Professionale, Amichevole, Tecnico...', 'sitegenie' ); ?>" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Pubblico Target', 'sitegenie' ); ?></th>
                                <td><input type="text" name="sitegenie_site_target" value="<?php echo esc_attr( get_option('sitegenie_site_target') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'es. Professionisti 30-50 anni, Mamme, PMI...', 'sitegenie' ); ?>" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Descrizione', 'sitegenie' ); ?></th>
                                <td><textarea name="sitegenie_site_description" class="large-text" rows="4" placeholder="<?php esc_attr_e( 'Descrivi brevemente cosa fa l\'azienda, i prodotti/servizi principali...', 'sitegenie' ); ?>"><?php echo esc_textarea( get_option('sitegenie_site_description') ); ?></textarea></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php submit_button( __( 'Salva Impostazioni', 'sitegenie' ) ); ?>

    </form>
</div>
