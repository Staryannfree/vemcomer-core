<?php
namespace VC\Checkout;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Registro central de métodos de fulfillment.
 */
class FulfillmentRegistry {
    /** @var array<string, FulfillmentMethod> */
    protected static array $methods = [];
    protected static bool $initialized = false;
    protected static bool $booted = false;

    public static function init(): void {
        if ( self::$initialized ) {
            return;
        }
        self::$initialized = true;
        add_action( 'init', [ __CLASS__, 'boot_methods' ], 5 );
    }

    public static function boot_methods(): void {
        self::$methods = [];
        /** Permite que integrações registrem métodos personalizados. */
        do_action( 'vemcomer_register_fulfillment_method' );
        self::$booted = true;
    }

    /**
     * Registra um método e opcionalmente força um slug.
     */
    public static function register( FulfillmentMethod $method, ?string $id = null ): void {
        $key = $id ?: strtolower( str_replace( '\\', '.', $method::class ) );
        self::$methods[ $key ] = $method;
    }

    /**
     * Obtém todos os métodos que suportam o pedido atual.
     */
    public static function get_quotes( array $order ): array {
        self::ensure_booted();
        $quotes = [];
        foreach ( self::$methods as $id => $method ) {
            if ( ! $method->supports_order( $order ) ) {
                continue;
            }
            $calc = $method->calculate_fee( $order );
            $quotes[] = [
                'id'      => $id,
                'label'   => $calc['label'] ?? __( 'Fulfillment', 'vemcomer' ),
                'amount'  => (float) ( $calc['total'] ?? 0.0 ),
                'free'    => (bool) ( $calc['free'] ?? false ),
                'eta'     => $method->get_eta( $order ),
                'details' => $calc['details'] ?? [],
            ];
        }
        return $quotes;
    }

    public static function get_method( string $id ): ?FulfillmentMethod {
        self::ensure_booted();
        return self::$methods[ $id ] ?? null;
    }

    protected static function ensure_booted(): void {
        if ( ! self::$initialized ) {
            self::init();
        }
        if ( ! self::$booted ) {
            self::boot_methods();
        }
    }
}
