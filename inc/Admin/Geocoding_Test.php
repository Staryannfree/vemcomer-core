<?php
/**
 * Geocoding_Test — Página de Admin para Testar Reverse Geocoding
 * 
 * @package VemComerCore
 */

namespace VC\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Geocoding_Test {
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function register_menu(): void {
        add_submenu_page(
            'vemcomer-root',
            __( 'Teste de Geocoding', 'vemcomer' ),
            __( 'Teste Geocoding', 'vemcomer' ),
            'manage_options',
            'vemcomer-geocoding-test',
            [ $this, 'render' ]
        );
    }

    public function enqueue_scripts( string $hook ): void {
        // O hook pode variar dependendo do nome do menu pai
        if ( $hook !== 'vemcomer-root_page_vemcomer-geocoding-test' && 
             $hook !== 'pedevem_page_vemcomer-geocoding-test' &&
             $hook !== 'toplevel_page_vemcomer-geocoding-test' ) {
            return;
        }

        // Enfileirar o script de reverse geocoding se disponível
        wp_enqueue_script( 'vemcomer-reverse-geocoding' );
        
        // Adicionar CSS inline para a página
        wp_add_inline_style( 'wp-admin', $this->get_inline_css() );
    }

    private function get_inline_css(): string {
        return '
            .vc-geocoding-test {
                max-width: 1200px;
                margin: 20px 0;
            }
            .vc-geocoding-form {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 20px;
                margin-bottom: 20px;
            }
            .vc-geocoding-form h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .vc-form-row {
                margin-bottom: 15px;
            }
            .vc-form-row label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .vc-form-row input[type="number"] {
                width: 100%;
                max-width: 300px;
                padding: 8px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
            .vc-form-row .description {
                color: #646970;
                font-size: 13px;
                margin-top: 5px;
            }
            .vc-test-button {
                background: #2271b1;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
            }
            .vc-test-button:hover {
                background: #135e96;
            }
            .vc-test-button:disabled {
                background: #a7aaad;
                cursor: not-allowed;
            }
            .vc-results {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 20px;
                margin-top: 20px;
                display: none;
            }
            .vc-results.show {
                display: block;
            }
            .vc-results h3 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .vc-result-item {
                margin-bottom: 15px;
                padding: 10px;
                background: #f6f7f7;
                border-left: 4px solid #2271b1;
            }
            .vc-result-item strong {
                display: inline-block;
                min-width: 120px;
                color: #1d2327;
            }
            .vc-result-item span {
                color: #50575e;
            }
            .vc-loading {
                display: none;
                margin-top: 10px;
                color: #2271b1;
            }
            .vc-loading.show {
                display: block;
            }
            .vc-error {
                background: #f0b7b1;
                border-left-color: #dc3232;
                color: #721c24;
                padding: 10px;
                margin-top: 10px;
                border-radius: 4px;
            }
            .vc-success {
                background: #d1e7dd;
                border-left-color: #00a32a;
                color: #0f5132;
                padding: 10px;
                margin-top: 10px;
                border-radius: 4px;
            }
        ';
    }

    public function render(): void {
        ?>
        <div class="wrap vc-geocoding-test">
            <h1><?php echo esc_html( __( 'Teste de Reverse Geocoding', 'vemcomer' ) ); ?></h1>
            
            <div class="vc-geocoding-form">
                <h2><?php echo esc_html( __( 'Informe as Coordenadas', 'vemcomer' ) ); ?></h2>
                
                <form id="vc-geocoding-test-form">
                    <div class="vc-form-row">
                        <label for="vc-latitude">
                            <?php echo esc_html( __( 'Latitude', 'vemcomer' ) ); ?>
                        </label>
                        <input 
                            type="number" 
                            id="vc-latitude" 
                            name="latitude" 
                            step="any" 
                            placeholder="-16.6864"
                            required
                        />
                        <p class="description">
                            <?php echo esc_html( __( 'Exemplo: -16.6864 (Goiânia)', 'vemcomer' ) ); ?>
                        </p>
                    </div>
                    
                    <div class="vc-form-row">
                        <label for="vc-longitude">
                            <?php echo esc_html( __( 'Longitude', 'vemcomer' ) ); ?>
                        </label>
                        <input 
                            type="number" 
                            id="vc-longitude" 
                            name="longitude" 
                            step="any" 
                            placeholder="-49.2643"
                            required
                        />
                        <p class="description">
                            <?php echo esc_html( __( 'Exemplo: -49.2643 (Goiânia)', 'vemcomer' ) ); ?>
                        </p>
                    </div>
                    
                    <div class="vc-form-row">
                        <button type="submit" class="vc-test-button" id="vc-test-button">
                            <?php echo esc_html( __( 'Testar Geocoding', 'vemcomer' ) ); ?>
                        </button>
                        <div class="vc-loading" id="vc-loading">
                            <?php echo esc_html( __( 'Processando...', 'vemcomer' ) ); ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="vc-results" id="vc-results">
                <h3><?php echo esc_html( __( 'Resultados', 'vemcomer' ) ); ?></h3>
                <div id="vc-results-content"></div>
            </div>
        </div>
        
        <script>
        (function() {
            const form = document.getElementById('vc-geocoding-test-form');
            const testButton = document.getElementById('vc-test-button');
            const loading = document.getElementById('vc-loading');
            const results = document.getElementById('vc-results');
            const resultsContent = document.getElementById('vc-results-content');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const lat = parseFloat(document.getElementById('vc-latitude').value);
                const lng = parseFloat(document.getElementById('vc-longitude').value);
                
                if (isNaN(lat) || isNaN(lng)) {
                    alert('Por favor, informe coordenadas válidas.');
                    return;
                }
                
                // Validar range de coordenadas
                if (lat < -90 || lat > 90) {
                    alert('Latitude deve estar entre -90 e 90.');
                    return;
                }
                
                if (lng < -180 || lng > 180) {
                    alert('Longitude deve estar entre -180 e 180.');
                    return;
                }
                
                // Desabilitar botão e mostrar loading
                testButton.disabled = true;
                loading.classList.add('show');
                results.classList.remove('show');
                resultsContent.innerHTML = '';
                
                try {
                    // Usar a função de reverse geocoding se disponível
                    if (window.VemComerReverseGeocode && window.VemComerReverseGeocode.reverseGeocode) {
                        const address = await window.VemComerReverseGeocode.reverseGeocode(lat, lng);
                        displayResults(address, lat, lng);
                    } else {
                        // Fallback: fazer requisição direta
                        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`;
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'User-Agent': 'Pedevem Marketplace'
                            }
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        // Parse do endereço
                        if (window.VemComerReverseGeocode && window.VemComerReverseGeocode.parseAddress) {
                            const address = window.VemComerReverseGeocode.parseAddress(data);
                            displayResults(address, lat, lng);
                        } else {
                            // Parse manual
                            const addr = data.address || {};
                            const address = {
                                street: addr.road || addr.pedestrian || '',
                                number: addr.house_number || '',
                                neighborhood: addr.suburb || addr.neighbourhood || addr.quarter || '',
                                city: addr.city || addr.town || addr.village || addr.municipality || '',
                                state: addr.state || addr.region || '',
                                postcode: addr.postcode || '',
                                country: addr.country || '',
                                fullAddress: data.display_name || '',
                                displayName: data.display_name || '',
                                raw: data
                            };
                            displayResults(address, lat, lng);
                        }
                    }
                } catch (error) {
                    console.error('Erro no reverse geocoding:', error);
                    resultsContent.innerHTML = `
                        <div class="vc-error">
                            <strong>Erro:</strong> ${error.message || 'Não foi possível obter o endereço.'}
                        </div>
                    `;
                    results.classList.add('show');
                } finally {
                    testButton.disabled = false;
                    loading.classList.remove('show');
                }
            });
            
            function displayResults(address, lat, lng) {
                let html = `
                    <div class="vc-success">
                        <strong>✓ Endereço obtido com sucesso!</strong>
                    </div>
                    <div class="vc-result-item">
                        <strong>Rua:</strong>
                        <span>${address.street || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>Número:</strong>
                        <span>${address.number || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>Bairro:</strong>
                        <span>${address.neighborhood || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>Cidade:</strong>
                        <span>${address.city || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>Estado:</strong>
                        <span>${address.state || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>CEP:</strong>
                        <span>${address.postcode || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>País:</strong>
                        <span>${address.country || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>Endereço Completo:</strong>
                        <span>${address.fullAddress || address.displayName || 'Não informado'}</span>
                    </div>
                    <div class="vc-result-item">
                        <strong>Coordenadas:</strong>
                        <span>Lat: ${lat}, Lng: ${lng}</span>
                    </div>
                `;
                
                // Adicionar dados brutos (raw) em um detalhes colapsável
                if (address.raw) {
                    html += `
                        <details style="margin-top: 15px;">
                            <summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Dados Brutos (JSON)</summary>
                            <pre style="background: #f6f7f7; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;">${JSON.stringify(address.raw, null, 2)}</pre>
                        </details>
                    `;
                }
                
                resultsContent.innerHTML = html;
                results.classList.add('show');
            }
        })();
        </script>
        <?php
    }
}

