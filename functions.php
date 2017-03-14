<?php
//var_pre_dump(date('N'));
/**
 * Queue parent style followed by child/customized style
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_dequeue_style('vogue-style');
    wp_enqueue_style('child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style')
    );
}, 99);

/***** Woocommerce custom functions    *****/
/**
 * Add notice message in single product
 */
add_filter('woocommerce_get_price_html', function ($price_html, $product) {
    if (!$product->is_purchasable() && is_product()) {
        $price_html .= '<p class="theme_error_messages">' . __('This product cannot be purchased together with products in cart. If you wish to buy this product, please remove the other products from the cart.', 'woocommerce') . '</p>';
    }
    return $price_html;
}, 20, 2);

function theme_get_cart_contents() {

    $cart_contents = array();
    $cart = WC()->session->get('cart', null);
    if (is_null($cart) && ($saved_cart = get_user_meta(get_current_user_id(), '_woocommerce_persistent_cart', true))) {
        $cart = $saved_cart['cart'];
    } elseif (is_null($cart)) {
        $cart = array();
    }
    if (is_array($cart)) {
        foreach ($cart as $key => $values) {
            $_product = wc_get_product($values['variation_id'] ? $values['variation_id'] : $values['product_id']);
            if (!empty($_product) && $_product->exists() && $values['quantity'] > 0) {
                if ($_product->is_purchasable()) {
                    $session_data = array_merge($values, array('data' => $_product));
                    $cart_contents[$key] = apply_filters('woocommerce_get_cart_item_from_session', $session_data, $values, $key);
                }
            }
        }
    }
    return $cart_contents;
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
function get_product_ids_by_category_id($category_id) {
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

        // For SubCat Willow Grove Farm Market (id 53): Unavailable to purchase for Monday & Tuesday Arrival/Service times
        $product_ids_by_53 = get_product_ids_by_category_id(53);
        //For SubCat Main Street Bakery (id 54): unavailable to purchase for Sunday & Monday Arrival/Service times
        $product_ids_by_54 = get_product_ids_by_category_id(54);

        if(in_array($product->id, $product_ids_by_54)){
            if(date('N') == 1 || date('N') == 7 ){
                wc_add_notice('Please remove the following items from your cart, they are not available on the delivery date you have selected.', 'error');
                $is_purchasable =  FALSE;
            }
        }
        elseif (in_array($product->id, $product_ids_by_53)){
            if(date('N') == 1 || date('N') == 2 ){
                wc_add_notice('Please remove the following items from your cart, they are not available on the delivery date you have selected.', 'error');
                $is_purchasable =  FALSE;
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

function cart_unset_all_notice() {
    $notices = WC()->session->get('wc_notices', array());
    unset($notices['success'], $notices['error']);
    wc_add_notice('Cart is empty, as there is no Service in the cart.', 'error');
}

/**
 * Add Options Page in Settings
 */
add_action('admin_menu', function () {
    add_options_page(__('Services Unique Page', 'vague'), 'Services Unique Page', 'manage_options', 'functions', 'global_custom_options');
});

function global_custom_options() { ?>
    <div class="wrap">
        <h2>Unique Ids of Services</h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options') ?>
            <p><strong>Services ID:</strong><br/>
                <input type="text" name="services_unique_ids" size="45" value="<?php echo get_option('services_unique_ids'); ?>"/>
            </p>
            <p><b>Example: 123,986,568</b></p>
            <p><input type="submit" name="Submit" value="Save"/></p>
            <input type="hidden" name="action" value="update"/>
            <input type="hidden" name="page_options" value="services_unique_ids"/>
        </form>
    </div>
<?php }

function var_pre_dump($var_value, $is_purchasable){
    echo '<pre>';
    var_dump($var_value, $is_purchasable);
    echo '</pre>';
    die('asdasd');
}


