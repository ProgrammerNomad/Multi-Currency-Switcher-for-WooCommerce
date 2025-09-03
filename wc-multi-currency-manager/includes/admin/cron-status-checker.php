<?php
/**
 * Cron Job Status Checker for WC Multi Currency Manager
 * Add this to your admin dashboard to monitor auto-updates
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add cron status to admin dashboard
 */
function wc_multi_currency_manager_cron_status_widget() {
    ?>
    <div class="card">
        <h2>🕒 Auto Currency Update Status</h2>
        
        <?php
        // Check if cron is scheduled
        $next_scheduled = wp_next_scheduled('wc_multi_currency_manager_daily_update');
        $last_updated = get_option('wc_multi_currency_manager_rates_last_updated', 0);
        
        if ($next_scheduled) {
            $next_update = date('Y-m-d H:i:s', $next_scheduled);
            echo "<p><strong>✅ Auto-Update: ACTIVE</strong></p>";
            echo "<p>📅 Next Update: {$next_update}</p>";
        } else {
            echo "<p><strong>❌ Auto-Update: INACTIVE</strong></p>";
            echo "<p>⚠️ Cron job not scheduled</p>";
        }
        
        if ($last_updated) {
            $last_update_date = date('Y-m-d H:i:s', $last_updated);
            $hours_ago = round((time() - $last_updated) / 3600, 1);
            echo "<p>🔄 Last Update: {$last_update_date} ({$hours_ago} hours ago)</p>";
        } else {
            echo "<p>🔄 Last Update: Never</p>";
        }
        
        // Show current rates count
        $rates = get_option('wc_multi_currency_manager_exchange_rates', array());
        $rates_count = count($rates);
        echo "<p>💱 Active Rates: {$rates_count} currencies</p>";
        ?>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=wc-multi-currency-manager-general'); ?>" class="button button-primary">
                Manual Update Now
            </a>
        </p>
    </div>
    <?php
}

// Add this function to check cron status programmatically
function wc_multi_currency_manager_is_cron_active() {
    return wp_next_scheduled('wc_multi_currency_manager_daily_update') !== false;
}

// Add this function to manually trigger cron (for testing)
function wc_multi_currency_manager_trigger_cron_manually() {
    return wc_multi_currency_manager_update_all_exchange_rates();
}
