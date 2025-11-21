<?php
/**
 * Message_Formatter — Formatador de mensagens WhatsApp para pedidos
 * @package VemComerCore
 */

namespace VC\WhatsApp;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Message_Formatter {
	/**
	 * Formata mensagem de pedido para WhatsApp.
	 *
	 * @param array $order_data Dados do pedido
	 * @param array $customer_data Dados do cliente
	 * @param array $restaurant_data Dados do restaurante
	 * @return string Mensagem formatada
	 */
	public static function format_order( array $order_data, array $customer_data, array $restaurant_data ): string {
		// Permitir filtro customizado
		$template = apply_filters( 'vemcomer/whatsapp_message_template', null, $order_data, $customer_data, $restaurant_data );
		if ( $template ) {
			return $template;
		}

		// Template padrão
		$message = self::get_default_template();
		$message = self::replace_placeholders( $message, $order_data, $customer_data, $restaurant_data );

		return $message;
	}

	/**
	 * Retorna template padrão.
	 */
	private static function get_default_template(): string {
		return "*Novo Pedido via VemComer* 🛵\n\n" .
			"--------------------------------\n\n" .
			"*Cliente:* {customer_name}\n" .
			"*Telefone:* {customer_phone}\n" .
			"*Endereço:* {customer_address}\n\n" .
			"--------------------------------\n\n" .
			"*PEDIDO:*\n{order_items}\n\n" .
			"--------------------------------\n\n" .
			"*Subtotal:* {subtotal}\n" .
			"*Taxa de Entrega:* {delivery_fee}\n" .
			"*TOTAL:* {total}\n\n" .
			"--------------------------------\n\n" .
			"*Tipo:* {fulfillment_type}\n" .
			"*Pagamento:* A combinar na entrega.\n";
	}

	/**
	 * Substitui placeholders no template.
	 */
	private static function replace_placeholders( string $template, array $order_data, array $customer_data, array $restaurant_data ): string {
		// Formatar itens
		$items_text = '';
		$items = $order_data['items'] ?? [];
		foreach ( $items as $index => $item ) {
			$qty = $item['quantity'] ?? 1;
			$name = $item['name'] ?? '';
			$price = $item['price'] ?? '0,00';
			$items_text .= sprintf( "%dx %s (R$ %s)\n", $qty, $name, $price );

			// Adicionar modificadores se houver
			if ( ! empty( $item['modifiers'] ) && is_array( $item['modifiers'] ) ) {
				foreach ( $item['modifiers'] as $modifier ) {
					$mod_name = $modifier['name'] ?? '';
					$mod_price = isset( $modifier['price'] ) && $modifier['price'] > 0 ? sprintf( ' (+R$ %s)', number_format( $modifier['price'], 2, ',', '' ) ) : '';
					$items_text .= "   + " . $mod_name . $mod_price . "\n";
				}
			}
		}

		// Placeholders
		$replacements = [
			'{customer_name}'    => $customer_data['name'] ?? '',
			'{customer_phone}'   => $customer_data['phone'] ?? '',
			'{customer_address}' => $customer_data['address'] ?? '',
			'{order_items}'      => trim( $items_text ),
			'{subtotal}'         => self::format_money( $order_data['subtotal'] ?? 0 ),
			'{delivery_fee}'     => self::format_money( $order_data['delivery_fee'] ?? 0 ),
			'{total}'            => self::format_money( $order_data['total'] ?? 0 ),
			'{fulfillment_type}' => $order_data['fulfillment_type'] === 'pickup' ? 'Retirada' : 'Entrega',
		];

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Formata valor monetário.
	 */
	private static function format_money( $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}
		return 'R$ ' . number_format( (float) $value, 2, ',', '.' );
	}

	/**
	 * Gera URL do WhatsApp com mensagem pré-formatada.
	 *
	 * @param string $phone Número do WhatsApp (apenas dígitos)
	 * @param string $message Mensagem formatada
	 * @return string URL do WhatsApp
	 */
	public static function generate_whatsapp_url( string $phone, string $message ): string {
		$phone = preg_replace( '/\D+/', '', $phone );
		$encoded_message = rawurlencode( $message );
		return sprintf( 'https://wa.me/%s?text=%s', $phone, $encoded_message );
	}
}

