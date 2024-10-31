<?php
/*
Plugin Name: Multisite sync for WooCommerce
Plugin URI: https://lemontec.at/
Description: Sync WooCommerce-Products data by SKU
Author: LEMONTEC
Version: 3.4.4
Author URI: https://lemontec.at/kontakt/
Text Domain: multisite-sync-for-woocommerce
Domain Path: /languages

WC requires at least: 2.2
WC tested up to: 5.8
*/

// INIT BACKEND
function lemon_woocommerce_sync_multisite_init() {
  load_plugin_textdomain( 'multisite-sync-for-woocommerce', false, 'multisite-sync-for-woocommerce/languages' );
}
add_action('init', 'lemon_woocommerce_sync_multisite_init');

require_once('inc/inc-backend.php');
global $woocommerce;


// SYNC FUNCTION
function lemon_woocommerce_sync_multisite($product_id) {    
    
    // GET OPTIONS
    $setting_blog_ids = get_site_option( 'lemontec_woocommerce_mulitsite_sync_blogid');
    $setting_stock = get_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_stock');
    $setting_price = get_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_price');
    
    // BUILD WC PRODUCT OBJECT
    $product = wc_get_product($product_id);
    
    // DATA SYNC
    $stock = $product->get_stock_quantity();
    $get_stock_status = $product->get_stock_status();
    $get_regular_price = $product->get_regular_price();
    $get_sale_price = $product->get_sale_price();
    $get_manage_stock = $product->get_manage_stock();
    $get_backorders = $product->get_backorders();
    $get_low_stock_amount = $product->get_low_stock_amount();
    
        
    // VARS
    $sku = $product->get_sku();
    $current_blog_id = get_current_blog_id();
    $get_blog_ids = get_sites(array('fields' => 'ids', 'site__not_in' => $current_blog_id));
    
    // UPATE STOCK TO OTHER INSTANCES
    foreach($get_blog_ids as $blog) {
        if(! in_array($blog, $setting_blog_ids)) {
            continue;
        }
        
        switch_to_blog($blog);
        
            // GET PRODUCT BY SKU
            $p_id = wc_get_product_id_by_sku($sku);
        
            // SYNC DATA
            if(! empty($p_id) && ! get_post_meta($p_id, 'lemontec_woocommerce_mulitsite_sync_disable_sync', true )) {
                
                // STOCK
                if($setting_stock == true) {
                    update_post_meta($p_id, '_manage_stock', $get_manage_stock);
                    update_post_meta($p_id, '_backorders', $get_backorders);
                    update_post_meta($p_id, '_stock', $stock);
                    update_post_meta($p_id, '_stock_status', $get_stock_status);
                    update_post_meta($p_id, '_low_stock_amount', $get_low_stock_amount);
                }
                
                // PRICING
                if($setting_price == true) {
                    update_post_meta($p_id, '_regular_price', $get_regular_price);
                    update_post_meta($p_id, '_sale_price', $get_sale_price);
                    if($get_sale_price) {
                        update_post_meta($p_id, '_price', $get_sale_price);
                    } else {
                        update_post_meta($p_id, '_price', $get_regular_price);
                    }
                }
                wc_delete_product_transients($p_id);
            }
        restore_current_blog();
    }
}

/* HREFLANG */
add_action('wp_head', 'lemontec_woocommerce_mulitsite_sync_wp_head');
function lemontec_woocommerce_mulitsite_sync_wp_head(){

    if(get_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_hreflang_active') == true && class_exists( 'WooCommerce' )) {
        $custom_lang = false;

        // GET OPTIONS
        $setting_blog_ids = get_site_option( 'lemontec_woocommerce_mulitsite_sync_blogid');

        // WOO
        $product = '';
        $sku = '';
        if(is_product()) {
            $product = wc_get_product(get_the_ID());
            $sku = $product->get_sku();
        }
        
        // VARS
        $current_blog_id = get_current_blog_id();
        $get_blog_ids = get_sites(array('fields' => 'ids'));       
        $custom_meta_post_id = get_the_ID();

        foreach($setting_blog_ids as $blog) {
            $custom_meta = get_post_meta($custom_meta_post_id, 'lemontec_woocommerce_mulitsite_sync_options_target_url_blogid_'.$blog, true);
            if(! empty($custom_meta)) {
                $iso_code = get_option( 'WPLANG');

                // WORKAROUND FORMATING CODES
                $iso_code = strtolower($iso_code);
                $iso_code = str_replace("_","-",$iso_code);

                $permalink = get_permalink();
                switch_to_blog($blog);
                echo '<link rel="alternate" hreflang="'.$iso_code.'" href="'.$permalink.'" />';

                $iso_code = get_option( 'WPLANG');

                // WORKAROUND FORMATING CODES
                $iso_code = strtolower($iso_code);
                $iso_code = str_replace("_","-",$iso_code);

                restore_current_blog();

                echo '<link rel="alternate" hreflang="'.$iso_code.'" href="'.$custom_meta.'" />';
                
                $custom_lang = true;
            }
        }
        
        // SET HREFLANG TAGS
        if($custom_lang == false) {
            foreach($get_blog_ids as $blog) {
                if(! in_array($blog, $setting_blog_ids)) {
                    continue;
                }
                
                switch_to_blog($blog);

                // INSTANCE VARS
                $iso_code = get_option( 'WPLANG');
                
                // WORKAROUND FORMATING CODES
                $iso_code = strtolower($iso_code);
                $iso_code = str_replace("_","-",$iso_code);

                $current_home_url = get_home_url();

                $p_id = '';
                if($sku) {
                    $p_id = wc_get_product_id_by_sku($sku);
                }             
                
                // HOME
                if(is_front_page()) {
                    echo '<link rel="alternate" hreflang="'.$iso_code.'" href="'.$current_home_url.'" />';
                }
                // PRODUCT
                if(! empty($p_id)) {
                    echo '<link rel="alternate" hreflang="'.$iso_code.'" href="'.get_permalink($p_id).'" />';
                }
                // CATEGORY
                if(is_product_category()) {
                    echo '<link rel="alternate" hreflang="'.$iso_code.'" href="'.get_permalink().'" />';
                }
                restore_current_blog();
            }
        }
    }

    if(get_site_option( 'lemontec_woocommerce_mulitsite_sync_blogid_canonical')[0] == true && class_exists( 'WooCommerce' )) {
        switch_to_blog(get_site_option( 'lemontec_woocommerce_mulitsite_sync_blogid_canonical')[0]);
        $canonical_permalink = get_permalink();
        restore_current_blog();
        echo '<link rel="canonical" href="'.$canonical_permalink.'" />';
    }
};

// ACTIONS
add_action( 'woocommerce_update_product', 'lemon_woocommerce_sync_multisite', 10, 1 );
add_action( 'woocommerce_update_product_variation', 'lemon_woocommerce_sync_multisite', 10, 1 );