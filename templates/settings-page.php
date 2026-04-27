<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap jeenie-settings">

    <div class="jeenie-header rounded-3 mb-4 d-flex align-items-baseline gap-3 p-4">
        <h1 class="text-white m-0 fs-4"><i class="fa-solid fa-robot"></i> Jeenie</h1>
        <p class="text-white-50 m-0 small"><?php esc_html_e( 'Assistente AI per il tuo WordPress', 'jeenie-ai-assistant' ); ?></p>
    </div>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'jeenie_settings' ); ?>

        <div class="row g-4 mb-4">

            <!-- COLONNA SINISTRA -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title fs-6 pb-2 border-bottom"><i class="fa-solid fa-key"></i> <?php esc_html_e( 'Configurazione API', 'jeenie-ai-assistant' ); ?></h2>

                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Provider AI', 'jeenie-ai-assistant' ); ?></th>
                                <td>
                                    <select name="jeenie_default_provider" id="jeenie-provider-select">
                                        <option value="gemini" <?php selected( get_option('jeenie_default_provider', 'gemini'), 'gemini' ); ?>>Google Gemini</option>
                                        <option value="openai" <?php selected( get_option('jeenie_default_provider', 'gemini'), 'openai' ); ?>>OpenAI (GPT)</option>
                                        <option value="claude" <?php selected( get_option('jeenie_default_provider', 'gemini'), 'claude' ); ?>>Anthropic Claude</option>
                                        <option value="groq" <?php selected( get_option('jeenie_default_provider', 'gemini'), 'groq' ); ?>>Groq (gratuito)</option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <!-- Gemini -->
                        <div id="jeenie-provider-gemini" class="jeenie-provider-section">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'API Key Gemini', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <input type="password" name="jeenie_gemini_api_key" value="<?php echo esc_attr( get_option('jeenie_gemini_api_key') ); ?>" class="regular-text" placeholder="AIza..." />
                                        <?php // translators: %s is a link to Google AI Studio ?>
                                        <p class="description"><?php echo wp_kses( sprintf( __( 'Ottieni la tua chiave su %s', 'jeenie-ai-assistant' ), '<a href="https://aistudio.google.com" target="_blank">Google AI Studio</a>' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Modello Gemini', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <select name="jeenie_gemini_model">
                                            <?php foreach ( Jeenie_Gemini::get_models() as $jeenie_value => $jeenie_label ) : ?>
                                                <option value="<?php echo esc_attr($jeenie_value); ?>" <?php selected( get_option('jeenie_gemini_model', 'gemini-2.0-flash'), $jeenie_value ); ?>><?php echo esc_html($jeenie_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- OpenAI -->
                        <div id="jeenie-provider-openai" class="jeenie-provider-section" style="display:none;">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'API Key OpenAI', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <input type="password" name="jeenie_openai_api_key" value="<?php echo esc_attr( get_option('jeenie_openai_api_key') ); ?>" class="regular-text" placeholder="sk-..." />
                                        <?php // translators: %s is a link to OpenAI Platform ?>
                                        <p class="description"><?php echo wp_kses( sprintf( __( 'Ottieni la tua chiave su %s', 'jeenie-ai-assistant' ), '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Modello OpenAI', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <select name="jeenie_openai_model">
                                            <?php foreach ( Jeenie_OpenAI::get_models() as $jeenie_value => $jeenie_label ) : ?>
                                                <option value="<?php echo esc_attr($jeenie_value); ?>" <?php selected( get_option('jeenie_openai_model', 'gpt-5.4-mini'), $jeenie_value ); ?>><?php echo esc_html($jeenie_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Claude -->
                        <div id="jeenie-provider-claude" class="jeenie-provider-section" style="display:none;">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'API Key Claude', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <input type="password" name="jeenie_claude_api_key" value="<?php echo esc_attr( get_option('jeenie_claude_api_key') ); ?>" class="regular-text" placeholder="sk-ant-..." />
                                        <?php // translators: %s is a link to Anthropic Console ?>
                                        <p class="description"><?php echo wp_kses( sprintf( __( 'Ottieni la tua chiave su %s', 'jeenie-ai-assistant' ), '<a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Modello Claude', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <select name="jeenie_claude_model">
                                            <?php foreach ( Jeenie_Claude::get_models() as $jeenie_value => $jeenie_label ) : ?>
                                                <option value="<?php echo esc_attr($jeenie_value); ?>" <?php selected( get_option('jeenie_claude_model', 'claude-sonnet-4-6'), $jeenie_value ); ?>><?php echo esc_html($jeenie_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Groq -->
                        <div id="jeenie-provider-groq" class="jeenie-provider-section" style="display:none;">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'API Key Groq', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <input type="password" name="jeenie_groq_api_key" value="<?php echo esc_attr( get_option('jeenie_groq_api_key') ); ?>" class="regular-text" placeholder="gsk_..." />
                                        <?php // translators: %s is a link to Groq Console ?>
                                        <p class="description"><?php echo wp_kses( sprintf( __( 'Ottieni la tua chiave gratuita su %s', 'jeenie-ai-assistant' ), '<a href="https://console.groq.com/keys" target="_blank">Groq Console</a>' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Modello Groq', 'jeenie-ai-assistant' ); ?></th>
                                    <td>
                                        <select name="jeenie_groq_model">
                                            <?php foreach ( Jeenie_Groq::get_models() as $jeenie_value => $jeenie_label ) : ?>
                                                <option value="<?php echo esc_attr($jeenie_value); ?>" <?php selected( get_option('jeenie_groq_model', 'llama-3.3-70b-versatile'), $jeenie_value ); ?>><?php echo esc_html($jeenie_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Limite richieste/ora', 'jeenie-ai-assistant' ); ?></th>
                                <td>
                                    <input type="number" name="jeenie_rate_limit" value="<?php echo esc_attr( get_option('jeenie_rate_limit', 30) ); ?>" min="0" max="1000" class="small-text" />
                                    <p class="description"><?php esc_html_e( 'Massimo richieste API per utente all\'ora. 0 = nessun limite.', 'jeenie-ai-assistant' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Elimina chat vecchie', 'jeenie-ai-assistant' ); ?></th>
                                <td>
                                    <input type="number" name="jeenie_auto_delete_days" value="<?php echo esc_attr( get_option('jeenie_auto_delete_days', 0) ); ?>" min="0" max="365" class="small-text" />
                                    <p class="description"><?php esc_html_e( 'Elimina automaticamente le conversazioni più vecchie di X giorni. 0 = mai.', 'jeenie-ai-assistant' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Timeout API (secondi)', 'jeenie-ai-assistant' ); ?></th>
                                <td>
                                    <input type="number" name="jeenie_api_timeout" value="<?php echo esc_attr( get_option('jeenie_api_timeout', 30) ); ?>" min="10" max="120" class="small-text" />
                                    <p class="description"><?php esc_html_e( 'Tempo massimo di attesa per le risposte AI. Default: 30 secondi.', 'jeenie-ai-assistant' ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <div class="d-flex align-items-center gap-2 mt-3">
                            <button type="button" id="jeenie-test-api" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-plug"></i> <?php esc_html_e( 'Testa Connessione', 'jeenie-ai-assistant' ); ?>
                            </button>
                            <span id="jeenie-test-result"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLONNA DESTRA -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title fs-6 pb-2 border-bottom"><i class="fa-solid fa-laptop-code"></i> <?php esc_html_e( 'Contesto del Sito', 'jeenie-ai-assistant' ); ?></h2>
                        <p class="text-muted small"><?php esc_html_e( 'Queste informazioni vengono passate all\'AI per generare contenuti coerenti con il tuo brand.', 'jeenie-ai-assistant' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Nome Sito / Azienda', 'jeenie-ai-assistant' ); ?></th>
                                <td><input type="text" name="jeenie_site_name" value="<?php echo esc_attr( get_option('jeenie_site_name', get_bloginfo('name')) ); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Settore', 'jeenie-ai-assistant' ); ?></th>
                                <td><input type="text" name="jeenie_site_sector" value="<?php echo esc_attr( get_option('jeenie_site_sector') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'es. E-commerce abbigliamento, Studio legale...', 'jeenie-ai-assistant' ); ?>" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Tono di voce', 'jeenie-ai-assistant' ); ?></th>
                                <td><input type="text" name="jeenie_site_tone" value="<?php echo esc_attr( get_option('jeenie_site_tone') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'es. Professionale, Amichevole, Tecnico...', 'jeenie-ai-assistant' ); ?>" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Pubblico Target', 'jeenie-ai-assistant' ); ?></th>
                                <td><input type="text" name="jeenie_site_target" value="<?php echo esc_attr( get_option('jeenie_site_target') ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'es. Professionisti 30-50 anni, Mamme, PMI...', 'jeenie-ai-assistant' ); ?>" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Descrizione', 'jeenie-ai-assistant' ); ?></th>
                                <td><textarea name="jeenie_site_description" class="large-text" rows="4" placeholder="<?php esc_attr_e( 'Descrivi brevemente cosa fa l\'azienda, i prodotti/servizi principali...', 'jeenie-ai-assistant' ); ?>"><?php echo esc_textarea( get_option('jeenie_site_description') ); ?></textarea></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php submit_button( __( 'Salva Impostazioni', 'jeenie-ai-assistant' ) ); ?>

    </form>
</div>
