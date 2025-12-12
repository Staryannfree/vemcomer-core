<?php
/**
 * Migrate_Command — Comandos WP-CLI para migração de dados
 *
 * Comandos:
 *   wp vemcomer migrate-restaurant-data
 *   wp vemcomer validate-data
 *
 * @package VemComerCore
 */

namespace VC\CLI;

use VC\Utils\Migration_Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Migrate_Command {
    /**
     * Inicializa os comandos WP-CLI
     */
    public function init(): void {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'vemcomer migrate-restaurant-data', [ $this, 'restaurant_data' ] );
            \WP_CLI::add_command( 'vemcomer validate-data', [ $this, 'validate_data' ] );
        }
    }

    /**
     * wp vemcomer migrate-restaurant-data
     * 
     * Migra dados antigos de restaurante, produtos e categorias para a estrutura padronizada.
     *
     * ## EXEMPLOS
     *
     *     wp vemcomer migrate-restaurant-data
     *
     * @when after_wp_load
     */
    public function restaurant_data( $args, $assoc_args ): void {
        \WP_CLI::log( 'Iniciando migração de dados do VemComer...' );
        \WP_CLI::log( '' );

        // 1. Migrar produtos
        \WP_CLI::log( 'Migrando produtos...' );
        $products_stats = Migration_Helper::migrate_legacy_product_restaurant_meta();
        \WP_CLI::log( sprintf(
            '  ✓ Produtos migrados: %d | Pulados: %d | Erros: %d',
            $products_stats['migrated'],
            $products_stats['skipped'],
            $products_stats['errors']
        ) );

        // 2. Migrar categorias
        \WP_CLI::log( 'Migrando categorias...' );
        $categories_stats = Migration_Helper::migrate_categories();
        \WP_CLI::log( sprintf(
            '  ✓ Categorias migradas: %d | Limpas: %d | Erros: %d',
            $categories_stats['migrated'],
            $categories_stats['cleaned'],
            $categories_stats['errors']
        ) );

        // 3. Migrar usuários
        \WP_CLI::log( 'Sincronizando usuários...' );
        $users_stats = Migration_Helper::migrate_users();
        \WP_CLI::log( sprintf(
            '  ✓ Usuários sincronizados: %d | Pulados: %d | Erros: %d',
            $users_stats['synced'],
            $users_stats['skipped'],
            $users_stats['errors']
        ) );

        \WP_CLI::log( '' );
        \WP_CLI::success( sprintf(
            'Migração concluída. Produtos: %d | Categorias: %d migradas, %d limpas | Usuários: %d sincronizados',
            $products_stats['migrated'],
            $categories_stats['migrated'],
            $categories_stats['cleaned'],
            $users_stats['synced']
        ) );
    }

    /**
     * wp vemcomer validate-data
     * 
     * Valida a integridade dos dados e exibe relatório de inconsistências.
     *
     * ## EXEMPLOS
     *
     *     wp vemcomer validate-data
     *
     * @when after_wp_load
     */
    public function validate_data( $args, $assoc_args ): void {
        \WP_CLI::log( 'Validando integridade dos dados...' );
        \WP_CLI::log( '' );

        $report = Migration_Helper::validate_data_integrity();

        \WP_CLI::log( 'Relatório de Integridade:' );
        \WP_CLI::log( '' );

        // Produtos sem restaurante
        if ( $report['products_without_restaurant'] > 0 ) {
            \WP_CLI::warning( sprintf(
                '⚠ %d produto(s) sem _vc_restaurant_id',
                $report['products_without_restaurant']
            ) );
        } else {
            \WP_CLI::log( '✓ Todos os produtos têm _vc_restaurant_id' );
        }

        // Categorias sem restaurante
        if ( $report['categories_without_restaurant'] > 0 ) {
            \WP_CLI::warning( sprintf(
                '⚠ %d categoria(s) de restaurante sem _vc_restaurant_id',
                $report['categories_without_restaurant']
            ) );
        } else {
            \WP_CLI::log( '✓ Todas as categorias de restaurante têm _vc_restaurant_id' );
        }

        // Categorias de catálogo com restaurante
        if ( $report['catalog_categories_with_restaurant'] > 0 ) {
            \WP_CLI::warning( sprintf(
                '⚠ %d categoria(s) de catálogo com _vc_restaurant_id (deveria estar vazio)',
                $report['catalog_categories_with_restaurant']
            ) );
        } else {
            \WP_CLI::log( '✓ Todas as categorias de catálogo estão corretas' );
        }

        // Usuários sem meta
        if ( $report['users_without_meta'] > 0 ) {
            \WP_CLI::warning( sprintf(
                '⚠ %d usuário(s) autores de restaurantes sem vc_restaurant_id no meta',
                $report['users_without_meta']
            ) );
        } else {
            \WP_CLI::log( '✓ Todos os usuários autores têm vc_restaurant_id' );
        }

        \WP_CLI::log( '' );

        $total_issues = $report['products_without_restaurant'] 
                      + $report['categories_without_restaurant']
                      + $report['catalog_categories_with_restaurant']
                      + $report['users_without_meta'];

        if ( $total_issues === 0 ) {
            \WP_CLI::success( 'Todos os dados estão consistentes!' );
        } else {
            \WP_CLI::warning( sprintf(
                'Encontradas %d inconsistência(s). Execute "wp vemcomer migrate-restaurant-data" para corrigir.',
                $total_issues
            ) );
        }
    }
}

