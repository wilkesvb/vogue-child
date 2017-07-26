<?php
/**
 * Queue parent style followed by child/customized style
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_dequeue_style('vogue-style');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
    wp_enqueue_style('chlid-styles', get_stylesheet_directory_uri() . '/child-styles.css', array('parent-style'));
}, 99);


/*2017 Theme Functions*/


/*Add Menus*/
function register_my_menus() {
  register_nav_menus(
    array(
      'front-page-menu' => __( 'Front Page Header' ),
      'front-page-footer-menu' => __( 'Front Page Footer' )
    )
  );
}
add_action( 'init', 'register_my_menus' );


/*Debug helper function*/
function debug_to_console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);

    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}
/*End*/

/*Dynamic Menus for loggedin vs non logged in users*/
function dynamic_menu() {
    if ( is_user_logged_in() ) {
        echo wp_nav_menu( array( 'theme_location' => 'primary', 'menu_id' => 'primary-menu' ) );
    } 
    else  { 
        echo wp_nav_menu(array( 'theme_location' => 'front-page-menu', 'menu_id' => 'front-page-header' ) ); 
    }
}

add_action( 'emp_partial' , 'dynamic_menu' );

function footer_dynamic_menu() {
    if ( is_user_logged_in() ) {
        echo wp_nav_menu( array( 'theme_location' => 'footer-bar', 'menu_id' => 'footer-bar-menu' ) );
    } 
    else  { 
        echo wp_nav_menu(array( 'theme_location' => 'front-page-footer-menu', 'menu_id' => 'front-page-footer' ) ); 
    }
}

add_action( 'emp_partial' , 'footer_dynamic_menu' );

/*End*/

$zip_form_page = array( 'front-page' );

/*Display zipcode form on front-page*/
function zip_form() {
    if ( is_page_template( $zip_form_page ) ) {
        get_template_part( 'zip-form' ); 
                debug_to_console( "yes-zip-form" );

    }
    else  { 
        debug_to_console( "no-zip-form" ); 
    }
}   
add_action( 'emp_partial' , 'zip_form' );

add_filter( 'body_class', 'custom_class' );
function custom_class( $classes ) {
    if ( is_page_template( 'front-example.php' ) ) {
        $classes[] = 'example';
    }
    return $classes;
}

/*End 2017 Theme Function*/






/***** Woocommerce custom functions *****/

/**
 * Add notice message in single product
 */
add_filter('woocommerce_get_price_html', function ($price_html, $product) {
    if (!$product->is_purchasable() && is_product()) {
        $price_html .= '<p class="theme_error_messages">' . __('Please add a
service package that includes grocery delivery in order to add this grocery item to your
cart.', 'woocommerce') . '</p>';
    }
    return $price_html;
}, 20, 2);

/**
 * @return array
 */
function theme_get_cart_contents()
{
    $cart_contents = array();
    $cart = WC()->session->get('cart', null);
    if (is_null($cart) && ($saved_cart = get_user_meta(get_current_user_id(), '_woocommerce_persistent_cart', true))) {
        $cart = $saved_cart['cart'];
    } elseif (is_null($cart)) {
        $cart = array();
    }
    if (is_array($cart)) {
        //Willow Grove Farm Market (id 53): Unavailable to purchase for Monday & Tuesday Arrival/Service times
        $product_ids_by_53 = get_product_ids_by_category_id(53);
        //Main Street Bakery (id 54): unavailable to purchase for Sunday & Monday Arrival/Service times
        $product_ids_by_54 = get_product_ids_by_category_id(54);
        $product_ids_certain_days = array();

        foreach ($cart as $key => $values) {
            if(in_array($values['product_id'], $product_ids_by_53) && (date('N') == 1 || date('N') == 2)){
                /*var_dump($values['product_id']);*/
                $product_ids_certain_days[] = $values['product_id'];
            }
            if(in_array($values['product_id'], $product_ids_by_54) && (date('N') == 1 || date('N') == 7)){
                /*var_dump('1111');*/
                $product_ids_certain_days[] = $values['product_id'];
            }
            $_product = wc_get_product($values['variation_id'] ? $values['variation_id'] : $values['product_id']);
            if (!empty($_product) && $_product->exists() && $values['quantity'] > 0) {
                if ($_product->is_purchasable()) {
                    $session_data = array_merge($values, array('data' => $_product));
                    $cart_contents[$key] = apply_filters('woocommerce_get_cart_item_from_session', $session_data, $values, $key);
                }
            }
        }
        add_notice_message_product_ids_certain_days($product_ids_certain_days);
    }
    return $cart_contents;
}

/**
 * @param null $product_ids_certain_days
 */
function add_notice_message_product_ids_certain_days($product_ids_certain_days = null){
    global $woocommerce;
    $product_titles = array();
    if($product_ids_certain_days){
        foreach($product_ids_certain_days as $cart_product_id){
            $product = get_product( $cart_product_id );
            $product_titles[] = $product->post->post_title;
        }
    }
    if(!empty($product_titles)){
        wc_clear_notices();
        $text = implode(",<br>",$product_titles).'!';
        wc_add_notice( sprintf( __( "Please remove the following items from your cart, they are not available on the delivery date you have selected.<br> ".$text) ) ,'error' );
    }

}

add_action('wp_loaded', function () {
    if (!is_object(WC()->session)) {
        return;
    }
    global $compare_cart_items;
    foreach (theme_get_cart_contents() as $item) {
        $compare_cart_items[] = $item['data']->id;
    }
}, 20);

/**
 * @param $category_id
 * @return array
 */
function get_product_ids_by_category_id($category_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix;
    $string = "
SELECT   {$table_name}posts.ID FROM {$table_name}posts
LEFT JOIN {$table_name}term_relationships
ON ({$table_name}posts.ID = {$table_name}term_relationships.object_id)
INNER JOIN {$table_name}postmeta
ON ( {$table_name}posts.ID = {$table_name}postmeta.post_id ) WHERE 1=1
AND ( {$table_name}term_relationships.term_taxonomy_id IN ({$category_id}))
AND (
  ( {$table_name}postmeta.meta_key = '_visibility' AND {$table_name}postmeta.meta_value IN ('catalog','visible') )
)
AND {$table_name}posts.post_type = 'product'
AND (({$table_name}posts.post_status = 'publish')) GROUP BY {$table_name}posts.ID ORDER BY {$table_name}posts.post_date ASC";

    $results = $wpdb->get_results($string, ARRAY_A);

    $array_ids = array();
    if (!empty($results)) {
        foreach ($results as $value) {
            $array_ids[] = $value['ID'];
        }
    }
    return $array_ids;
}

add_filter('woocommerce_is_purchasable', function ($is_purchasable, $product) {
    global $compare_cart_items;
    global $product_cart_items;
    // Service Category ID
    $category_id = 14;
    //All services
    $product_cart_items = get_product_ids_by_category_id($category_id);
    $intersect = @array_intersect($compare_cart_items, $product_cart_items);
    $product_unic_ids = explode(',', get_option('services_unique_ids'));
    $intersect_unic = @array_intersect($compare_cart_items, $product_unic_ids);
    if (!empty($compare_cart_items)) {
        if (!empty($intersect)) {
            if (@in_array($product->id, $product_cart_items)) {
                $is_purchasable = in_array($product->id, $compare_cart_items);
            } else {
                if ($intersect_unic) {
                    $is_purchasable = FALSE;
                }
            }
        } else {
            if (in_array($product->id, $product_unic_ids)) {
                $is_purchasable = FALSE;
            }
        }
    } elseif (empty(WC()->cart->cart_contents)) {
        $is_purchasable = in_array($product->id, $product_cart_items);
    }
    return $is_purchasable;
}, 20, 2);

/**
 *  Remove cart items
 */
add_action('template_redirect', function () {
    global $product_cart_items;
    $cart_ids = array();
    foreach (WC()->cart->cart_contents as $prod_in_cart) {
        // Get the Product ID
        $cart_ids[] = $prod_in_cart['product_id'];
    }
    $intersect = @array_intersect($cart_ids, $product_cart_items);
    if (empty($intersect)) {
        WC()->cart->empty_cart(true);
        wc_clear_notices();
        cart_unset_all_notice();
    }
});

function cart_unset_all_notice()
{
    $notices = WC()->session->get('wc_notices', array());
    unset($notices['success'], $notices['error']);
    wc_add_notice('Please <a href="/index.php#services">CLICK HERE</a> to select a service so you may order groceries.', 'error');
}

/**
 * Add Options Page in Settings
 */
add_action('admin_menu', function () {
    add_options_page(__('Services Unique Page', 'vague'), 'Services Unique Page', 'manage_options', 'functions', 'global_custom_options');
});

function global_custom_options()
{ ?>
    <div class="wrap">
        <h2><?php echo __('Unique Ids of Services','vogue'); ?></h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options') ?>
            <p><label for="services_unique_ids"><strong><?php echo __('Services ID:','vogue'); ?></strong></label><br/>
                <input type="text" name="services_unique_ids" id="services_unique_ids" size="45"
                       value="<?php echo get_option('services_unique_ids'); ?>"/>
            </p>
            <p><b><i><?php echo __('Example: 123,986,568','vogue'); ?></i></b></p><br><hr><br>
            <p>
                <label for="checkout_cabin_pre_arrival_message"><b><?php echo __('Checkout Cabin Pre Arrival Message:','vogue'); ?></b></label>
            </p>
            <p>
                <textarea name="checkout_cabin_pre_arrival_message" id="checkout_cabin_pre_arrival_message" rows="10" cols="50"><?php echo get_option('checkout_cabin_pre_arrival_message'); ?></textarea>
            </p><br><hr><br>
            <p>
                <label for="unique_pa_text"><b><?php echo __('Unique Text Cabins:','vogue'); ?></b></label>
            </p>
            <p>
                <input type="text" name="unique_pa_text" id="unique_pa_text" size="45"  value="<?php echo get_option('unique_pa_text'); ?>"/>
            </p>
            <p><b><i><?php echo __('Example: Pre-Arrival','vogue'); ?></i></b></p><br><hr><br>
            <p><input type="submit" name="Submit" value="Save"/></p>
            <input type="hidden" name="action" value="update"/>
            <input type="hidden" name="page_options" value="services_unique_ids"/>
            <input type="hidden" name="page_options" value="checkout_cabin_pre_arrival_message"/>
            <input type="hidden" name="page_options" value="unique_pa_text"/>
        </form>
    </div>
<?php }

function add_message_in_checkout_form() { ?>
    <script>
        var $ = jQuery.noConflict();
        var messageText = '<span class="messageText"><?php echo get_option('checkout_cabin_pre_arrival_message'); ?></span>';
        //var messageText = '<span class="messageText">Good News! Your cabin is eligible for pre-arrival delivery. Your groceries will be waiting for you when you arrive!</span>';
        $('#cabin_selection').after(messageText);
        $(document).ready(function(){
            $('#cabin_selection').click(function(){
                var selectValue = $('#cabin_selection :selected').val();
                if(selectValue !== null &&  selectValue.search("<?php echo get_option('unique_pa_text'); ?>") > 0){
                    $('span.messageText').addClass('active');
                }
                else{
                    $('span.messageText').removeClass('active');
                }
            });
        });
    </script>

<?php
}

add_action( 'woocommerce_after_checkout_form', 'add_message_in_checkout_form');



add_action('init', 'mvv_remove_woocommerce_template_loop_product_thumbnail', 10);

function mvv_remove_woocommerce_template_loop_product_thumbnail() {
	remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
}

add_action('init', 'mvv_add_woocommerce_template_loop_product_thumbnail', 10);

function mvv_add_woocommerce_template_loop_product_thumbnail() {
	add_action('woocommerce_before_shop_loop_item_title', 'mvv_woocommerce_template_loop_product_thumbnail', 10);
}

if ( ! function_exists( 'mvv_woocommerce_template_loop_product_thumbnail' ) ) {

	function mvv_woocommerce_template_loop_product_thumbnail() {
		
		global $post;
		global $product;

		$terms = get_the_terms( $post->ID, 'product_tag' );
		
		$term_array = array();
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
            foreach ( $terms as $term ) {
                $term_array[] = $term->name;
            }
        }

		if( in_array('local', $term_array) ) {
			echo '<div class="local-flag mvv-thumbnail-wrap">';
		}
		else {
			echo '<div class="mvv-thumbnail-wrap">';
		}
		
		$thumbnail_html = woocommerce_get_product_thumbnail();

		if ( ! $product->is_in_stock() ) {

		$value = get_post_meta( $post->ID, 'mvv_outofstock_value', true );
        
        $outofstock_text = "OUT OF STOCK";
        $outofstock_text = apply_filters('mvv_outofstock_text', $outofstock_text);
		//if ( $value === 'grey_out'){
				$thumbnail_html = str_replace('class="', 'class="gray-out ', $thumbnail_html);
				$thumbnail_html = '<div class="outofstock-text"><span class="outofstock-inner">' . $outofstock_text . '</span></div>' . $thumbnail_html;
		//	}
		}

		echo $thumbnail_html;
		
		echo '</div>';
	}
}

add_filter('woocommerce_single_product_image_html','mvv_add_local_flag', 10, 2);

if ( ! function_exists( 'mvv_add_local_flag' ) ) {
	
	function mvv_add_local_flag($html, $postID) {

		$terms = get_the_terms( $postID, 'product_tag' );
		
		$term_array = array();
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
            foreach ( $terms as $term ) {
                $term_array[] = $term->name;
            }
        }

        if( in_array('local', $term_array) ) {
        	$html = str_replace( "class=\"woocommerce-main-image", "class=\"local-flag woocommerce-main-image", $html );
        }

        $product = wc_get_product( $postID );

        if ( ! $product->is_in_stock() ) {

		$value = get_post_meta( $postID, 'mvv_outofstock_value', true );
        $outofstock_text = "OUT OF STOCK";
        $outofstock_text = apply_filters('mvv_outofstock_text', $outofstock_text);
        
		if ( $value === 'grey_out' || true){
				$html = str_replace('attachment-shop_single', 'attachment-shop_single gray-out', $html);
				
			}
		$html = str_replace('</a>', '<div class="outofstock-text"><span class="outofstock-inner">'.$outofstock_text.'</span></div></a>', $html);
		}

		return $html;
	}
}

add_action( 'add_meta_boxes', 'mvv_add_outofstock_meta_box' );

function mvv_add_outofstock_meta_box(){
    
    add_meta_box('mvv_outofstock', 'When the Product is Out of Stock', 'mvv_outofstock_callback', 'product', 'side', 'low');
    
}

function mvv_outofstock_callback( $post ){
        
        wp_nonce_field( 'mvv_outofstock_box', 'mvv_outofstock_nonce' );
        $value = get_post_meta( $post->ID, 'mvv_outofstock_value', true );
        
        ?>
        <label for="mvv_outofstock_field"><?php _e( "Choose option:", 'choose_option' ); ?></label>
        <br />  
        <input type="radio" name="outofstock_radio_buttons" value="hide_it" <?php checked( $value, 'hide_it' ); ?> >Hide it<br>
        <input type="radio" name="outofstock_radio_buttons" value="grey_out" <?php checked( $value, 'grey_out' ); ?> >Grey out<br>

        <?php
               
}

add_action( 'save_post', 'mvv_save_outofstock_meta_box' );


function mvv_save_outofstock_meta_box( $post_id ) {

        if ( !isset( $_POST['mvv_outofstock_nonce'] ) ) {
                return;
        }

        if ( !wp_verify_nonce( $_POST['mvv_outofstock_nonce'], 'mvv_outofstock_box' ) ) {
                return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
        }

        if ( !current_user_can( 'edit_post', $post_id ) ) {
                return;
        }

        $new_meta_value = ( isset( $_POST['outofstock_radio_buttons'] ) ? sanitize_html_class( $_POST['outofstock_radio_buttons'] ) : '' );

        update_post_meta( $post_id, 'mvv_outofstock_value', $new_meta_value );

}

add_filter('woocommerce_product_is_in_stock', 'mvv_check_local_avail', 99999, 1);

function mvv_check_local_avail($status) {

    if (is_admin()) { return $status; }

	global $product;
	global $woocommerce;
	$content = $woocommerce->cart->get_cart();
	if ( is_object($product)) {
	    
	    $id = $product->get_id();
	    
	    foreach($content as $key => $value) {
	
    	    if ( is_array($value['mvv_tsr']) && !empty($value['mvv_tsr']) && $value['mvv_tsr'][0]['arrival_date'] !== NULL) {
    	    	$date_day = date('N', strtotime($value['mvv_tsr'][0]['arrival_date']));
				$arrival_day = "";
				if ($date_day === '7') $arrival_day = "Sunday";
				if ($date_day === '1') $arrival_day = "Monday";
				if ($date_day === '2') $arrival_day = "Tuesday";
    		    
    		    $product_cat = get_the_terms($id, 'product_cat');

    		    foreach ($product_cat as $key => $value) {

        			    $cat_name = $value->name;
    
        			    if ($cat_name === "Main Street Bakery") {
        				    if ($arrival_day === "Sunday" || $arrival_day === "Monday") {
        					    return false;
        				    }
        			    }
        
        			    if ($cat_name === "Willow Grove Farm Market") {
        				    if ($arrival_day === "Tuesday" || $arrival_day === "Monday") {
        					    return false;
        				    }
        			    }
    		        }
    	    }
    }   
	    }

	return $status;
}

add_filter('mvv_outofstock_text', 'mvv_outofstock_text', 10, 1);

function mvv_outofstock_text($text) {
    	global $product;
	global $woocommerce;
	$content = $woocommerce->cart->get_cart();
	
	foreach($content as $key => $value) {
    	if ( is_array($value['mvv_tsr']) && !empty($value['mvv_tsr']) && $value['mvv_tsr'][0]['arrival_date'] !== NULL) {

    		$date_day = date('N', strtotime($value['mvv_tsr'][0]['arrival_date']));
				$arrival_day = "";
				if ($date_day === '7') $arrival_day = "Sunday";
				if ($date_day === '1') $arrival_day = "Monday";
				if ($date_day === '2') $arrival_day = "Tuesday";
				
    		$product_cat = get_the_terms($product->get_id(), 'product_cat');
    		foreach ($product_cat as $key => $value) {
    			$cat_name = $value->name;
    			if ($cat_name === "Main Street Bakery") {
    				if ($arrival_day === "Sunday" || $arrival_day === "Monday") {
    					return "UNAVAILABLE ON SUNDAY AND MONDAY";
    				}
    			}
    
    			if ($cat_name === "Willow Grove Farm Market") {
    				if ($arrival_day === "Tuesday" || $arrival_day === "Monday") {
    					return "UNAVAILABLE ON MONDAY AND TUESDAY";
    				}
    			}
    		}
    	}
}
    return "OUT OF STOCK";
}

if ( is_user_logged_in() ) {
    add_filter('body_class','add_role_to_body');
    add_filter('admin_body_class','add_role_to_body');
}
function add_role_to_body($classes) {
    $current_user = new WP_User(get_current_user_id());
    $user_role = array_shift($current_user->roles);
    if (is_admin()) {
        $classes .= 'role-'. $user_role;
    } else {
        $classes[] = 'role-'. $user_role;
    }
    return $classes;
}