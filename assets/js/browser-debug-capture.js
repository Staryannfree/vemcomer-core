/**
 * Browser Debug Capture - Captura TUDO do navegador automaticamente
 * 
 * Este script captura:
 * - Todos os console.log/error/warn
 * - Todas as requisições fetch/XHR
 * - Erros JavaScript não tratados
 * - Performance metrics
 * - Mudanças no DOM (opcional)
 * - Screenshots de erros (opcional)
 * 
 * @package VemComerCore
 */

(function() {
    'use strict';

    // ============================================================================
    // CONFIGURAÇÃO
    // ============================================================================
    const CONFIG = {
        enabled: true, // Ativar/desativar captura
        sendToServer: true, // Enviar logs para servidor via REST API
        saveToLocalStorage: true, // Salvar logs no localStorage
        saveToFile: false, // Salvar logs em arquivo (requer permissões)
        maxLogs: 1000, // Máximo de logs a manter
        autoFlush: true, // Enviar logs automaticamente a cada X segundos
        flushInterval: 5000, // 5 segundos
        captureNetwork: true, // Capturar requisições de rede
        captureConsole: true, // Capturar console
        captureErrors: true, // Capturar erros
        capturePerformance: true, // Capturar métricas de performance
        restEndpoint: '/wp-json/vemcomer/v1/debug/browser-logs', // Endpoint REST
        restNonce: window.vcDebugCapture?.restNonce || '', // Nonce do WordPress
    };

    // ============================================================================
    // ARMAZENAMENTO DE LOGS
    // ============================================================================
    let logs = [];
    let networkRequests = [];
    let performanceMetrics = [];

    // Carregar logs existentes do localStorage
    if (CONFIG.saveToLocalStorage) {
        try {
            const saved = localStorage.getItem('vc_browser_debug_logs');
            if (saved) {
                logs = JSON.parse(saved);
            }
        } catch (e) {
            console.warn('[VC Debug] Erro ao carregar logs salvos:', e);
        }
    }

    // ============================================================================
    // FUNÇÕES DE LOGGING
    // ============================================================================
    function addLog(type, data) {
        if (!CONFIG.enabled) return;

        const logEntry = {
            timestamp: new Date().toISOString(),
            type: type,
            data: data,
            url: window.location.href,
            userAgent: navigator.userAgent,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight,
            },
        };

        logs.push(logEntry);

        // Limitar quantidade de logs
        if (logs.length > CONFIG.maxLogs) {
            logs.shift();
        }

        // Salvar no localStorage
        if (CONFIG.saveToLocalStorage) {
            try {
                localStorage.setItem('vc_browser_debug_logs', JSON.stringify(logs));
            } catch (e) {
                console.warn('[VC Debug] Erro ao salvar logs:', e);
            }
        }

        // Enviar para servidor (async, não bloqueia)
        if (CONFIG.sendToServer) {
            sendLogToServer(logEntry);
        }
    }

    // ============================================================================
    // CAPTURA DE CONSOLE
    // ============================================================================
    if (CONFIG.captureConsole) {
        const originalConsole = {
            log: console.log.bind(console),
            error: console.error.bind(console),
            warn: console.warn.bind(console),
            info: console.info.bind(console),
            debug: console.debug.bind(console),
        };

        console.log = function(...args) {
            originalConsole.log(...args);
            addLog('console.log', {
                level: 'log',
                message: args.map(arg => {
                    if (typeof arg === 'object') {
                        try {
                            return JSON.stringify(arg, null, 2);
                        } catch (e) {
                            return String(arg);
                        }
                    }
                    return String(arg);
                }).join(' '),
                args: args,
            });
        };

        console.error = function(...args) {
            originalConsole.error(...args);
            addLog('console.error', {
                level: 'error',
                message: args.map(arg => String(arg)).join(' '),
                args: args,
                stack: new Error().stack,
            });
        };

        console.warn = function(...args) {
            originalConsole.warn(...args);
            addLog('console.warn', {
                level: 'warn',
                message: args.map(arg => String(arg)).join(' '),
                args: args,
            });
        };

        console.info = function(...args) {
            originalConsole.info(...args);
            addLog('console.info', {
                level: 'info',
                message: args.map(arg => String(arg)).join(' '),
                args: args,
            });
        };

        console.debug = function(...args) {
            originalConsole.debug(...args);
            addLog('console.debug', {
                level: 'debug',
                message: args.map(arg => String(arg)).join(' '),
                args: args,
            });
        };
    }

    // ============================================================================
    // CAPTURA DE ERROS
    // ============================================================================
    if (CONFIG.captureErrors) {
        // Erros não tratados
        window.addEventListener('error', function(event) {
            addLog('error.unhandled', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error ? {
                    name: event.error.name,
                    message: event.error.message,
                    stack: event.error.stack,
                } : null,
            });
        }, true);

        // Promessas rejeitadas
        window.addEventListener('unhandledrejection', function(event) {
            addLog('error.promise', {
                reason: event.reason ? String(event.reason) : 'Unknown',
                error: event.reason instanceof Error ? {
                    name: event.reason.name,
                    message: event.reason.message,
                    stack: event.reason.stack,
                } : null,
            });
        });
    }

    // ============================================================================
    // CAPTURA DE REQUISIÇÕES DE REDE
    // ============================================================================
    if (CONFIG.captureNetwork) {
        // Interceptar fetch
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const url = args[0];
            const options = args[1] || {};
            const startTime = performance.now();

            return originalFetch.apply(this, args)
                .then(response => {
                    const endTime = performance.now();
                    const duration = endTime - startTime;

                    // Clonar response para ler o body sem consumir
                    const clonedResponse = response.clone();

                    clonedResponse.text().then(body => {
                        const requestData = {
                            method: options.method || 'GET',
                            url: url,
                            headers: options.headers || {},
                            body: options.body || null,
                            status: response.status,
                            statusText: response.statusText,
                            duration: Math.round(duration),
                            timestamp: new Date().toISOString(),
                        };

                        // Tentar parsear JSON
                        try {
                            requestData.responseBody = JSON.parse(body);
                        } catch (e) {
                            requestData.responseBody = body.substring(0, 500); // Limitar tamanho
                        }

                        networkRequests.push(requestData);
                        addLog('network.fetch', requestData);

                        // Limitar quantidade
                        if (networkRequests.length > 100) {
                            networkRequests.shift();
                        }
                    }).catch(() => {
                        // Ignorar erros ao ler body
                    });

                    return response;
                })
                .catch(error => {
                    const endTime = performance.now();
                    const duration = endTime - startTime;

                    addLog('network.fetch.error', {
                        method: options.method || 'GET',
                        url: url,
                        error: error.message,
                        duration: Math.round(duration),
                        timestamp: new Date().toISOString(),
                    });

                    throw error;
                });
        };

        // Interceptar XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function(method, url, ...rest) {
            this._vcDebugMethod = method;
            this._vcDebugUrl = url;
            this._vcDebugStartTime = performance.now();
            return originalXHROpen.apply(this, [method, url, ...rest]);
        };

        XMLHttpRequest.prototype.send = function(...args) {
            const xhr = this;
            const method = xhr._vcDebugMethod;
            const url = xhr._vcDebugUrl;
            const startTime = xhr._vcDebugStartTime;

            xhr.addEventListener('loadend', function() {
                const endTime = performance.now();
                const duration = endTime - startTime;

                addLog('network.xhr', {
                    method: method,
                    url: url,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    duration: Math.round(duration),
                    responseType: xhr.responseType,
                    timestamp: new Date().toISOString(),
                });
            });

            xhr.addEventListener('error', function() {
                const endTime = performance.now();
                const duration = endTime - startTime;

                addLog('network.xhr.error', {
                    method: method,
                    url: url,
                    error: 'Network error',
                    duration: Math.round(duration),
                    timestamp: new Date().toISOString(),
                });
            });

            return originalXHRSend.apply(this, args);
        };
    }

    // ============================================================================
    // CAPTURA DE PERFORMANCE
    // ============================================================================
    if (CONFIG.capturePerformance) {
        window.addEventListener('load', function() {
            if (window.performance && window.performance.timing) {
                const timing = window.performance.timing;
                const metrics = {
                    dns: timing.domainLookupEnd - timing.domainLookupStart,
                    tcp: timing.connectEnd - timing.connectStart,
                    request: timing.responseStart - timing.requestStart,
                    response: timing.responseEnd - timing.responseStart,
                    dom: timing.domComplete - timing.domLoading,
                    load: timing.loadEventEnd - timing.navigationStart,
                };

                performanceMetrics.push(metrics);
                addLog('performance.pageLoad', metrics);
            }
        });
    }

    // ============================================================================
    // ENVIO PARA SERVIDOR
    // ============================================================================
    function sendLogToServer(logEntry) {
        if (!CONFIG.restEndpoint || !CONFIG.restNonce) {
            return;
        }

        // Enviar de forma assíncrona, não bloquear
        fetch(CONFIG.restEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': CONFIG.restNonce,
            },
            body: JSON.stringify(logEntry),
        }).catch(() => {
            // Ignorar erros silenciosamente
        });
    }

    // Enviar logs em lote periodicamente
    if (CONFIG.autoFlush && CONFIG.sendToServer) {
        setInterval(function() {
            if (logs.length === 0) return;

            const logsToSend = logs.slice(-50); // Últimos 50 logs
            if (logsToSend.length === 0) return;

            fetch(CONFIG.restEndpoint + '/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': CONFIG.restNonce,
                },
                body: JSON.stringify({ logs: logsToSend }),
            }).catch(() => {
                // Ignorar erros
            });
        }, CONFIG.flushInterval);
    }

    // ============================================================================
    // FUNÇÕES PÚBLICAS
    // ============================================================================
    window.vcBrowserDebug = {
        // Obter todos os logs
        getLogs: function() {
            return logs;
        },

        // Obter requisições de rede
        getNetworkRequests: function() {
            return networkRequests;
        },

        // Obter métricas de performance
        getPerformanceMetrics: function() {
            return performanceMetrics;
        },

        // Limpar logs
        clearLogs: function() {
            logs = [];
            networkRequests = [];
            performanceMetrics = [];
            if (CONFIG.saveToLocalStorage) {
                localStorage.removeItem('vc_browser_debug_logs');
            }
        },

        // Exportar logs
        exportLogs: function() {
            const data = {
                logs: logs,
                networkRequests: networkRequests,
                performanceMetrics: performanceMetrics,
                timestamp: new Date().toISOString(),
                url: window.location.href,
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `browser-debug-logs-${Date.now()}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },

        // Enviar todos os logs para servidor agora
        flushLogs: function() {
            if (!CONFIG.restEndpoint || !CONFIG.restNonce) {
                console.warn('[VC Debug] Endpoint ou nonce não configurado');
                return;
            }

            fetch(CONFIG.restEndpoint + '/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': CONFIG.restNonce,
                },
                body: JSON.stringify({
                    logs: logs,
                    networkRequests: networkRequests,
                    performanceMetrics: performanceMetrics,
                }),
            }).then(response => {
                if (response.ok) {
                    console.log('[VC Debug] Logs enviados com sucesso');
                } else {
                    console.warn('[VC Debug] Erro ao enviar logs:', response.status);
                }
            }).catch(error => {
                console.warn('[VC Debug] Erro ao enviar logs:', error);
            });
        },

        // Configuração
        config: CONFIG,
    };

    // Log inicial
    addLog('system.init', {
        message: 'Browser Debug Capture inicializado',
        config: CONFIG,
    });

    console.log('[VC Debug] Browser Debug Capture ativado. Use window.vcBrowserDebug para acessar logs.');
})();

