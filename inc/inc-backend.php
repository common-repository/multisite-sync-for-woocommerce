<?php

// METABOX
function multisite_sync_for_woocommerce_json_fileds() {
    $setting_blog_ids = get_site_option( 'lemontec_woocommerce_mulitsite_sync_blogid');
        
    // VARS
    $current_blog_id = get_current_blog_id();
    $get_blog_ids = get_sites(array('fields' => 'ids', 'site__not_in' => $current_blog_id));

    // BUILD JSON ARRAY FOR FIELDS
    $lang_field_array = array ( 'title' => 'Multisite Sync language settings', 'prefix' => 'lemontec_woocommerce_mulitsite_sync_options_', 'domain' => 'multisite-sync-for-woocommerce', 'class_name' => 'multisite_sync_for_woocommerce', 'post-type' => array ( 0 => 'post', 1 => 'page', ), 'context' => 'side', 'priority' => 'high', 'cpt' => 'product', 'fields' => array (), );
    foreach($get_blog_ids as $blog) {
        if(! in_array($blog, $setting_blog_ids)) {
            continue;
        }
        $lang_field_array['fields'][] = array(
            'type' => 'url',
            'label' => 'Target URL for:<br> ' . get_site_url($blog) . '<br><br>',
            'id' => 'lemontec_woocommerce_mulitsite_sync_options_target_url_blogid_' . $blog,
        );
    }

    $json = json_encode($lang_field_array );
    return $json;
}

class multisite_sync_for_woocommerce {

	private $config = ''; 
    public function __construct() {
		$this->config = json_decode(multisite_sync_for_woocommerce_json_fileds(), true );
		$this->process_cpts();
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ] );
	}

	public function process_cpts() {
		if ( !empty( $this->config['cpt'] ) ) {
			if ( empty( $this->config['post-type'] ) ) {
				$this->config['post-type'] = [];
			}
			$parts = explode( ',', $this->config['cpt'] );
			$parts = array_map( 'trim', $parts );
			$this->config['post-type'] = array_merge( $this->config['post-type'], $parts );
		}
	}

	public function add_meta_boxes() {
		foreach ( $this->config['post-type'] as $screen ) {
			add_meta_box(
				sanitize_title( $this->config['title'] ),
				$this->config['title'],
				[ $this, 'add_meta_box_callback' ],
				$screen,
				$this->config['context'],
				$this->config['priority']
			);
		}
	}

	public function save_post( $post_id ) {
		foreach ( $this->config['fields'] as $field ) {
			switch ( $field['type'] ) {
				case 'url':
					if ( isset( $_POST[ $field['id'] ] ) ) {
						$sanitized = esc_url_raw( $_POST[ $field['id'] ] );
						update_post_meta( $post_id, $field['id'], $sanitized );
					}
					break;
				default:
					if ( isset( $_POST[ $field['id'] ] ) ) {
						$sanitized = sanitize_text_field( $_POST[ $field['id'] ] );
						update_post_meta( $post_id, $field['id'], $sanitized );
					}
			}
		}
	}

	public function add_meta_box_callback() {
		$this->fields_div();
	}

	private function fields_div() {
		foreach ( $this->config['fields'] as $field ) {
			?><div class="components-base-control">
				<div class="components-base-control__field"><?php
					$this->label( $field );
					$this->field( $field );
				?></div>
			</div><?php
		}
	}

	private function label( $field ) {
		switch ( $field['type'] ) {
			default:
				printf(
					'<label class="components-base-control__label" for="%s">%s</label>',
					$field['id'], $field['label']
				);
		}
	}

	private function field( $field ) {
		switch ( $field['type'] ) {
			default:
				$this->input( $field );
		}
	}

	private function input( $field ) {
		printf(
			'<input class="components-text-control__input %s" id="%s" name="%s" %s type="%s" value="%s">',
			isset( $field['class'] ) ? $field['class'] : '',
			$field['id'], $field['id'],
			isset( $field['pattern'] ) ? "pattern='{$field['pattern']}'" : '',
			$field['type'],
			$this->value( $field )
		);
	}

	private function value( $field ) {
		global $post;
		if ( metadata_exists( 'post', $post->ID, $field['id'] ) ) {
			$value = get_post_meta( $post->ID, $field['id'], true );
		} else if ( isset( $field['default'] ) ) {
			$value = $field['default'];
		} else {
			return '';
		}
		return str_replace( '\u0027', "'", $value );
	}

}
new multisite_sync_for_woocommerce;

/* CREATE MULTISITE BACKEND SETTING MENÜ */
add_action("network_admin_menu", "lemontec_woocommerce_mulitsite_sync_add_backend");
function lemontec_woocommerce_mulitsite_sync_add_backend() {
	add_menu_page(
		'WooCommerce Multisite Sync',
		'Woo-Sync',
		'manage_options',
		'lemontec-woocommerce-mulitsite-sync',
		'lemontec_woocommerce_mulitsite_sync_setting_page',
 		'dashicons-update',
		100 
	);
}


function lemontec_woocommerce_mulitsite_sync_setting_page() { ?>
    <div class="wrap">	
        <a href="https:lemontec.at" target="_blank" style="max-width: 200px; display: block; margin: 20px 0 0;">
            <img src="<?php echo plugin_dir_url( __DIR__ ); ?>img/lemontec-logo.svg" alt="LEMONTEC WEBAGENTUR LOGO">
        </a>
        
        <h1><?php esc_html_e('WooCommerce Multisite Sync Settings.' , 'multisite-sync-for-woocommerce'); ?></h1>
        <div style="background-color:#fff; padding:10px; margin: 15px 0;">   
            <p>
                <?php esc_html_e('You may set which WooCommerce product data and which websites in the network should be synchronized based on the SKU.' , 'multisite-sync-for-woocommerce'); ?><br>
            </p>
                <strong><?php esc_html_e('The following functions will be implemented in the future:' , 'multisite-sync-for-woocommerce'); ?></strong><br>
                <ul style="padding:15px; list-style:circle;">
                    <li>
                        <?php esc_html_e('Sync images' , 'multisite-sync-for-woocommerce'); ?>
                    </li>
                    <li>
                        <s><?php esc_html_e('Language switch with href-lang-tags' , 'multisite-sync-for-woocommerce'); ?></s>
                    </li>
                    <li>
                        <?php esc_html_e('Sync Custom-Field' , 'multisite-sync-for-woocommerce'); ?>
                    </li>
                </ul>
                <?php esc_html_e('If you have any questions, write to: office@lemontec.at' , 'multisite-sync-for-woocommerce'); ?>            
        </div>
        
		<form method="post" action="edit.php?action=syncaction">
			<?php wp_nonce_field( 'lemontec-validate' ); ?>
			<h2><?php esc_html_e('Network settings' , 'multisite-sync-for-woocommerce'); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
                        <label for="some_field">
                            <?php esc_html_e('Select the websites to be synchronized:' , 'multisite-sync-for-woocommerce'); ?>
                        </label>
                    </th>
					<td>
                        <fieldset>
                            <ul>
                                <?php 
                                $get_blogs = get_sites();
                                $saved_blogs = get_site_option('lemontec_woocommerce_mulitsite_sync_blogid');
                                foreach($get_blogs as $row) : ?>
                                  <li> 
                                    <label>
                                      <?php 
                                      if(in_array($row->blog_id, $saved_blogs)) : ?>
                                          <input type="checkbox" name="blog_id[]" value="<?php echo $row->blog_id ?>" checked>
                                          <b>URL:</b> <?php echo esc_html($row->domain); ?>
                                      <?php else : ?>
                                          <input type="checkbox" name="blog_id[]" value="<?php echo $row->blog_id ?>">
                                          <b>URL:</b> <?php echo esc_html($row->domain); ?>
                                      <?php endif; ?>
                                    </label>
                                  </li>
                                <?php endforeach; ?>
                            </ul>
                        </fieldset>
					</td>
				</tr>
			</table>
			<h2><?php esc_html_e('Synchronize the following product data:' , 'multisite-sync-for-woocommerce'); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e('Base data' , 'multisite-sync-for-woocommerce'); ?></th>
					<td>
                        <label>
                            <?php if(get_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_stock') == true) : ?>
                                <input name="lemontec_woocommerce_mulitsite_sync_checkbox_stock" type="checkbox" value="true" checked>
                            <?php else : ?>
                                <input name="lemontec_woocommerce_mulitsite_sync_checkbox_stock" type="checkbox" value="true">
                            <?php endif; ?>
                            <?php esc_html_e('Stock level' , 'multisite-sync-for-woocommerce'); ?>
                            <em>
                                 <?php esc_html_e('(Stock status, stock backlog, etc.)' , 'multisite-sync-for-woocommerce'); ?>
                            </em>
                        </label>
                        <br>
                        <label>
                            <?php if(get_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_price') == true) : ?>
                                <input name="lemontec_woocommerce_mulitsite_sync_checkbox_price" type="checkbox" value="true" checked>
                            <?php else : ?>
                                <input name="lemontec_woocommerce_mulitsite_sync_checkbox_price" type="checkbox" value="true">
                            <?php endif; ?>
                            <?php esc_html_e('Price' , 'multisite-sync-for-woocommerce'); ?>
                             <em>
                                 <?php esc_html_e('(Regular price, offer price, etc.)' , 'multisite-sync-for-woocommerce'); ?>
                            </em>
                        </label>
                    </td>
				</tr>
			</table>
            <h2><?php esc_html_e('Extra settings:' , 'multisite-sync-for-woocommerce'); ?></h2>
			<table class="form-table">
				<tr>
                    <th scope="row"><?php esc_html_e('Language settings' , 'multisite-sync-for-woocommerce'); ?></th>
					<td>
                        <label>
                            <?php if(get_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_hreflang_active') == true) : ?>
                                <input name="lemontec_woocommerce_mulitsite_sync_checkbox_hreflang_active" type="checkbox" value="true" checked>
                            <?php else : ?>
                                <input name="lemontec_woocommerce_mulitsite_sync_checkbox_hreflang_active" type="checkbox" value="true">
                            <?php endif; ?>
                            <b><?php esc_html_e('Activate hreflang tags' , 'multisite-sync-for-woocommerce'); ?></b>
                            <em>
                                 <?php esc_html_e('(<link rel="alternate" hreflang="xx-XX" href="URL"/>)' , 'multisite-sync-for-woocommerce'); ?>
                                 <br>
                                 <?php esc_html_e('The right urls will genearte by sku, you can overwrite per product, page, post...' , 'multisite-sync-for-woocommerce'); ?>
                            </em>
                        </label>
                    </td>
				</tr>
			</table>
            <table class="form-table">
				<tr>
					<th scope="row">
                        <label for="some_field">
                            <?php esc_html_e('Canonical page (important for SEO duplicate content):' , 'multisite-sync-for-woocommerce'); ?>
                        </label>
                    </th>
					<td>
                        <fieldset>
                            <ul>
                                <?php 
                                $get_blogs = get_sites();
                                $saved_blogs = get_site_option('lemontec_woocommerce_mulitsite_sync_blogid_canonical');
                                foreach($get_blogs as $row) : ?>
                                  <li> 
                                    <label>
                                      <?php 
                                      if(in_array($row->blog_id, $saved_blogs)) : ?>
                                          <input type="radio" name="blog_id_canonical[]" value="<?php echo $row->blog_id ?>" checked>
                                          <b>URL:</b> <?php echo esc_html($row->domain); ?>
                                      <?php else : ?>
                                          <input type="radio" name="blog_id_canonical[]" value="<?php echo $row->blog_id ?>">
                                          <b>URL:</b> <?php echo esc_html($row->domain); ?>
                                      <?php endif; ?>
                                    </label>
                                  </li>
                                <?php endforeach; ?>
                                <?php 
                                foreach($saved_blogs as $row) {
                                if(empty($row)) : ?>
                                <li>
                                    <input type="radio" name="blog_id_canonical[]" value="false" checked>
                                    <?php esc_html_e('disable' , 'multisite-sync-for-woocommerce'); ?>
                                 </li>
                                 <?php else : ?>
                                  <li>
                                    <input type="radio" name="blog_id_canonical[]" value="false">
                                    <?php esc_html_e('disable' , 'multisite-sync-for-woocommerce'); ?>
                                 </li>
                               <?php  
                                endif;
                                }?>
                            </ul>
                        </fieldset>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
        </form>
    </div>
<?php }


/* SAVE FIELDS */
add_action( 'network_admin_edit_syncaction', 'lemontec_woocommerce_mulitsite_sync_save_settings' );
function lemontec_woocommerce_mulitsite_sync_save_settings(){
 
    // POST VARS
    $post_sync_stock = sanitize_text_field($_POST['lemontec_woocommerce_mulitsite_sync_checkbox_stock']);
    $post_sync_price = sanitize_text_field($_POST['lemontec_woocommerce_mulitsite_sync_checkbox_price']);
    $post_sync_hreflang = sanitize_text_field($_POST['lemontec_woocommerce_mulitsite_sync_checkbox_hreflang_active']);
    $post_sync_blog_id = $_POST['blog_id'];
    $post_sync_blog_id_canonical = $_POST['blog_id_canonical'];
    
    check_admin_referer( 'lemontec-validate' ); // Nonce security check
 
    if($post_sync_stock == true || $post_sync_stock == false) {
        update_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_stock', $post_sync_stock );
    }
    if($post_sync_price == true || $post_sync_price == false) {
        update_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_price', $post_sync_price );
    }
    if($post_sync_hreflang == true || $post_sync_hreflang == false) {
        update_site_option( 'lemontec_woocommerce_mulitsite_sync_checkbox_hreflang_active', $post_sync_hreflang );
    }
    
        $blog_ids = array();
        foreach($post_sync_blog_id as $row) {
            array_push($blog_ids, intval($row));
        }
        update_site_option( 'lemontec_woocommerce_mulitsite_sync_blogid', $blog_ids);

        $blog_ids = array();
        foreach($post_sync_blog_id_canonical as $row) {
            array_push($blog_ids, intval($row));
        }
        update_site_option( 'lemontec_woocommerce_mulitsite_sync_blogid_canonical', $blog_ids);
    

	wp_redirect( add_query_arg( array(
		'page' => 'lemontec-woocommerce-mulitsite-sync',
		'updated' => true ), network_admin_url('admin.php')
	));
	exit; 
}

// DISABLE SYNC FUNCTION
add_action( 'add_meta_boxes', 'lemontec_woocommerce_mulitsite_sync_checkbox_function' );
function lemontec_woocommerce_mulitsite_sync_checkbox_function() {
   add_meta_box('lemontec_woocommerce_mulitsite_sync_disable_sync','Disable Multisite Sync', 'lemontec_woocommerce_mulitsite_sync_disable_sync_callback', 'product', 'side', 'high');
}
function lemontec_woocommerce_mulitsite_sync_disable_sync_callback( $post ) {
   global $post;
   $isFeatured=get_post_meta( $post->ID, 'lemontec_woocommerce_mulitsite_sync_disable_sync', true );
?>
   <input type="checkbox" name="lemontec_woocommerce_mulitsite_sync_disable_sync" value="yes" <?php echo (($isFeatured=='yes') ? 'checked="checked"': '');?>/>
   <?php esc_html_e('Yes disable sync for this product!' , 'multisite-sync-for-woocommerce'); ?>
<?php
}

add_action('save_post', 'save_lemontec_woocommerce_mulitsite_sync_disable_sync'); 
function save_lemontec_woocommerce_mulitsite_sync_disable_sync($post_id){ 
   update_post_meta( $post_id, 'lemontec_woocommerce_mulitsite_sync_disable_sync', $_POST['lemontec_woocommerce_mulitsite_sync_disable_sync']);
}
