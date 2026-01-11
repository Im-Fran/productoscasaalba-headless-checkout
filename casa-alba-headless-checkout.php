<?php
/**
 * Plugin Name: Casa Alba - Headless Checkout URLs
 * Plugin URI: https://productoscasaalba.cl
 * Description: Modifica las URLs de retorno del checkout de WooCommerce para redirigir al frontend headless en lugar del CMS.
 * Version: 1.1.0
 * Author: Casa Alba
 * Author URI: https://productoscasaalba.cl
 * License: GPL v3
 * Text Domain: casa-alba-headless
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('CASA_ALBA_HEADLESS_VERSION', '1.1.0');
define('CASA_ALBA_HEADLESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CASA_ALBA_HEADLESS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include API classes
require_once CASA_ALBA_HEADLESS_PLUGIN_DIR . 'includes/class-customer-api.php';
require_once CASA_ALBA_HEADLESS_PLUGIN_DIR . 'includes/class-orders-api.php';

/**
 * Class Casa_Alba_Headless_Checkout
 * 
 * Main plugin class that handles URL modifications for headless checkout
 */
class Casa_Alba_Headless_Checkout {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Frontend URL from environment or settings
     */
    private $frontend_url;
    
    /**
     * Customer API instance
     */
    private $customer_api;

    /**
     * Orders API instance
     */
    private $orders_api;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Get frontend URL from wp-config.php constant or use default
        $this->frontend_url = defined('CASA_ALBA_FRONTEND_URL') 
            ? CASA_ALBA_FRONTEND_URL 
            : get_option('casa_alba_frontend_url', 'https://productoscasaalba.cl');
        
        // Initialize API classes
        $this->customer_api = new Casa_Alba_Customer_API();
        $this->orders_api = new Casa_Alba_Orders_API();

        // Initialize hooks
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Initialize plugin hooks
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Hook to modify checkout return URL (order received page)
        add_filter('woocommerce_get_checkout_order_received_url', array($this, 'modify_order_received_url'), 10, 2);
        
        // Hook to modify checkout cancel URL
        add_filter('woocommerce_get_cancel_order_url', array($this, 'modify_cancel_order_url'), 10, 2);
        add_filter('woocommerce_get_cancel_order_url_raw', array($this, 'modify_cancel_order_url'), 10, 2);
        
        // Hook for payment gateway return URLs (for gateways like PayPal, Mercado Pago, etc.)
        add_filter('woocommerce_get_return_url', array($this, 'modify_return_url'), 10, 2);
        
        // Hook to modify checkout payment URL
        add_filter('woocommerce_get_checkout_payment_url', array($this, 'modify_payment_url'), 10, 2);
        
        // Hook for Store API checkout response (used by the headless frontend)
        add_filter('woocommerce_store_api_checkout_order_response', array($this, 'modify_store_api_response'), 10, 2);
        
        // Log when plugin is loaded
        error_log('Casa Alba Headless Checkout: Plugin initialized with frontend URL: ' . $this->frontend_url);
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $this->customer_api->register_routes();
        $this->orders_api->register_routes();
    }

    /**
     * Modify order received URL (thank you page)
     */
    public function modify_order_received_url($url, $order) {
        if (!$order) {
            return $url;
        }
        
        $order_id = is_numeric($order) ? $order : $order->get_id();
        $order_key = is_object($order) ? $order->get_order_key() : '';
        
        // Build frontend URL for order confirmation
        $frontend_url = trailingslashit($this->frontend_url) . 'pedido-recibido';
        
        // Add order parameters
        $frontend_url = add_query_arg(array(
            'order_id' => $order_id,
            'key' => $order_key
        ), $frontend_url);
        
        error_log('Casa Alba Headless: Modified order received URL from ' . $url . ' to ' . $frontend_url);
        
        return $frontend_url;
    }
    
    /**
     * Modify cancel order URL
     */
    public function modify_cancel_order_url($url, $order = null) {
        // Build frontend URL for cancellation
        $frontend_url = trailingslashit($this->frontend_url) . 'pedido-cancelado';
        
        // If we have order info, add it to the URL
        if ($order && is_object($order)) {
            $order_id = $order->get_id();
            $frontend_url = add_query_arg(array(
                'order_id' => $order_id
            ), $frontend_url);
        }
        
        error_log('Casa Alba Headless: Modified cancel URL from ' . $url . ' to ' . $frontend_url);
        
        return $frontend_url;
    }
    
    /**
     * Modify return URL for payment gateways
     */
    public function modify_return_url($url, $order) {
        if (!$order) {
            return $url;
        }
        
        // Check order status to determine which page to redirect to
        $order_status = $order->get_status();
        
        if (in_array($order_status, array('pending', 'on-hold', 'processing', 'completed'))) {
            // Success - redirect to order received page
            return $this->modify_order_received_url($url, $order);
        } else if (in_array($order_status, array('failed', 'cancelled'))) {
            // Failed or cancelled - redirect to cancel/failed page
            return $this->modify_cancel_order_url($url, $order);
        }
        
        // Default to order received URL
        return $this->modify_order_received_url($url, $order);
    }
    
    /**
     * Modify payment URL
     */
    public function modify_payment_url($url, $order) {
        // For now, keep payment URL as is since it needs to process through WooCommerce
        // But log it for debugging
        error_log('Casa Alba Headless: Payment URL: ' . $url);
        return $url;
    }
    
    /**
     * Modify Store API checkout response
     * This is crucial for headless frontends using the Store API
     */
    public function modify_store_api_response($response, $order) {
        if (!isset($response['redirect_url'])) {
            return $response;
        }
        
        // Get the payment method to determine if we need special handling
        $payment_method = $order->get_payment_method();
        
        error_log('Casa Alba Headless: Store API response for payment method: ' . $payment_method);
        error_log('Casa Alba Headless: Original redirect URL: ' . $response['redirect_url']);
        
        // Check if this is a direct payment method (no external redirect needed)
        $direct_payment_methods = array('bacs', 'cheque', 'cod'); // Bank transfer, check, cash on delivery
        
        if (in_array($payment_method, $direct_payment_methods)) {
            // For direct payment methods, redirect to order confirmation
            $response['redirect_url'] = $this->modify_order_received_url('', $order);
        } else {
            // For external payment gateways (PayPal, Mercado Pago, etc.)
            // The redirect_url should point to the payment gateway
            // The gateway will then redirect back using our modified return URLs
            error_log('Casa Alba Headless: Keeping gateway redirect URL: ' . $response['redirect_url']);
        }
        
        error_log('Casa Alba Headless: Modified redirect URL: ' . $response['redirect_url']);
        
        return $response;
    }
    
    /**
     * Admin notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('Casa Alba - Headless Checkout URLs requiere que WooCommerce esté instalado y activado.', 'casa-alba-headless'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add settings page to WordPress admin
     */
    public function add_admin_menu() {
        add_options_page(
            __('Casa Alba Headless Checkout', 'casa-alba-headless'),
            __('Headless Checkout', 'casa-alba-headless'),
            'manage_options',
            'casa-alba-headless',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('casa_alba_headless_settings', 'casa_alba_frontend_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://productoscasaalba.cl'
        ));
        
        add_settings_section(
            'casa_alba_headless_main',
            __('Configuración de URL del Frontend', 'casa-alba-headless'),
            array($this, 'settings_section_callback'),
            'casa-alba-headless'
        );
        
        add_settings_field(
            'casa_alba_frontend_url',
            __('URL del Frontend', 'casa-alba-headless'),
            array($this, 'frontend_url_field_callback'),
            'casa-alba-headless',
            'casa_alba_headless_main'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure la URL de su aplicación frontend (React/Vue/etc.) para las redirecciones del checkout.', 'casa-alba-headless') . '</p>';
    }
    
    /**
     * Frontend URL field callback
     */
    public function frontend_url_field_callback() {
        $value = get_option('casa_alba_frontend_url', 'https://productoscasaalba.cl');
        ?>
        <input type="url" name="casa_alba_frontend_url" value="<?php echo esc_attr($value); ?>" 
               class="regular-text" placeholder="https://productoscasaalba.cl" required />
        <p class="description">
            <?php _e('URL base del frontend (sin barra final). Ejemplo: https://productoscasaalba.cl', 'casa-alba-headless'); ?>
        </p>
        <?php
    }
    
    /**
     * Settings page HTML
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('casa_alba_headless_settings');
                do_settings_sections('casa-alba-headless');
                submit_button(__('Guardar Configuración', 'casa-alba-headless'));
                ?>
            </form>
            
            <hr />
            
            <h2><?php _e('Información del Plugin', 'casa-alba-headless'); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php _e('Versión:', 'casa-alba-headless'); ?></strong></td>
                        <td><?php echo CASA_ALBA_HEADLESS_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('URL Frontend Configurada:', 'casa-alba-headless'); ?></strong></td>
                        <td><code><?php echo esc_html($this->frontend_url); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WooCommerce:', 'casa-alba-headless'); ?></strong></td>
                        <td>
                            <?php 
                            if (class_exists('WooCommerce')) {
                                echo '<span style="color: green;">✓ ' . __('Activado', 'casa-alba-headless') . '</span>';
                            } else {
                                echo '<span style="color: red;">✗ ' . __('No encontrado', 'casa-alba-headless') . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <hr />
            
            <h2><?php _e('Páginas Requeridas en el Frontend', 'casa-alba-headless'); ?></h2>
            <p><?php _e('Asegúrese de que las siguientes rutas existan en su aplicación frontend:', 'casa-alba-headless'); ?></p>
            <ul>
                <li><code><?php echo esc_html($this->frontend_url); ?>/pedido-recibido</code> - <?php _e('Página de confirmación de pedido', 'casa-alba-headless'); ?></li>
                <li><code><?php echo esc_html($this->frontend_url); ?>/pedido-cancelado</code> - <?php _e('Página de cancelación de pedido', 'casa-alba-headless'); ?></li>
            </ul>
        </div>
        <?php
    }
}

/**
 * Initialize the plugin
 */
function casa_alba_headless_checkout_init() {
    return Casa_Alba_Headless_Checkout::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'casa_alba_headless_checkout_init');
