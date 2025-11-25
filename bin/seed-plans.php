<?php
/**
 * CLI command to seed plans
 */
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-content/plugins/vemcomer-core/inc/Utils/Plan_Seeder.php';
require_once __DIR__ . '/wp-content/plugins/vemcomer-core/inc/Model/CPT_SubscriptionPlan.php';

// Mock class CPT if not loaded
if (!class_exists('VC\Model\CPT_SubscriptionPlan')) {
    // ... (It should be loaded if plugin is active)
}

echo "Seeding plans...\n";
VC\Utils\Plan_Seeder::seed();
echo "Done.\n";

