<?php
/**
 * Núcleo do plugin: inicialização, i18n e orquestração de módulos.
 */

namespace VemComer\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	/**
	 * Singleton
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registro de "providers" (módulos) que expõem um método estático register()
	 * @var array<class-string>
	 */
	private array $providers = [];

	/**
	 * Acesso ao singleton
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construtor privado (singleton)
	 */
	private function __construct() {}

	/**
	 * Ponto público de boot
	 */
	public function boot(): void {
		$this->load_textdomain();
		$this->register_providers();
		$this->hooks();
	}

	/**
	 * Carrega traduções
	 */
	private function load_textdomain(): void {
		// Funções WP precisam de barra invertida em namespace
		\load_plugin_textdomain(
			'vemcomer-core',
			false,
			\dirname( \plugin_basename( \VMC_FILE ) ) . '/languages'
		);
	}

	/**
	 * Registra módulos do sistema (preparado para os próximos passos)
	 */
	private function register_providers(): void {
		$this->providers = [
			// Ex.: \VemComer\Core\Model\CPT_Product::class,
			// Ex.: \VemComer\Core\Model\CPT_Restaurant::class,
			// Ex.: \VemComer\Core\REST\Routes::class,
			// Ex.: \VemComer\Core\Admin\Assets::class,
			// Ex.: \VemComer\Core\PublicSite\Assets::class,
		];

		foreach ( $this->providers as $provider ) {
			if ( \class_exists( $provider ) && \method_exists( $provider, 'register' ) ) {
				$provider::register();
			}
		}
	}

	/**
	 * Hooks globais do plugin
	 */
	private function hooks(): void {
		// Exemplo: \add_action( 'init', [ $this, 'register_post_types' ] );
	}

	/**
	 * Rodado na ativação do plugin
	 */
	public static function activate(): void {
		// Se futuramente criarmos CPTs/Taxonomias, é aqui que garantimos reescritas
		\flush_rewrite_rules();
	}

	/**
	 * Rodado na desativação do plugin
	 */
	public static function deactivate(): void {
		\flush_rewrite_rules();
	}
}
