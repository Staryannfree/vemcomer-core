<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use VC\Checkout\FulfillmentRegistry;
use VC\Checkout\Methods\FlatRateDelivery;

FulfillmentRegistry::init();

add_action( 'vemcomer_register_fulfillment_method', static function () {
    FulfillmentRegistry::register( new FlatRateDelivery(), FlatRateDelivery::SLUG );
    if ( class_exists( '\\VC\\Checkout\\Methods\\DistanceBasedDelivery' ) ) {
        FulfillmentRegistry::register( new \VC\Checkout\Methods\DistanceBasedDelivery(), \VC\Checkout\Methods\DistanceBasedDelivery::SLUG );
    }
    if ( class_exists( '\\VC\\Checkout\\Methods\\Pickup' ) ) {
        FulfillmentRegistry::register( new \VC\Checkout\Methods\Pickup(), \VC\Checkout\Methods\Pickup::SLUG );
    }
}, 5 );
