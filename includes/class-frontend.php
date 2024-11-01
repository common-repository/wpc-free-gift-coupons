<?php

class Wpcfg_Frontend {
	protected static $instance = null;

	public $data = [];

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return bool
	 */
	public function is_frontend_request() {
		return ( ! is_admin() || defined( 'DOING_AJAX' ) )
		       && ! defined( 'DOING_CRON' )
		       && ! WC()->is_rest_api_request();
	}

	public function __construct() {
		add_action( 'wp', [ $this, 'set_gift_in_coupons' ], 10 );
		add_action( 'wp', [ $this, 'remove_invalid_free_gifts_from_cart' ], 20 );
		add_action( 'wp', [ $this, 'set_gifts' ], 30 );

		add_action( 'woocommerce_after_cart_table', [ $this, 'show_gifts' ] );

		add_filter( 'woocommerce_product_get_price', [ $this, 'set_gifts_product_price' ] );
		add_filter( 'woocommerce_product_variation_get_price', [ $this, 'set_gifts_product_price' ] );
		add_action( 'woocommerce_after_cart_item_name', [ $this, 'show_free_label_on_cart' ] );

		// Order Product
		add_filter( 'woocommerce_shortcode_products_query', [ $this, 'order_gifts_by_id' ] );

		// Add $_POST
		add_filter( 'woocommerce_loop_add_to_cart_args', [ $this, 'tag_gifts_product_as_free' ], 10, 2 );

		// Cart
		add_filter( 'woocommerce_coupon_get_items_to_validate', [ $this, 'remove_free_gifts_from_items_to_validate' ] );
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_free_gift' ], 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_free_gift_cart_item_data' ] );
		add_filter( 'woocommerce_cart_item_price', [ $this, 'set_free_gift_cart_item_price' ], 10, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', [ $this, 'set_free_gift_cart_item_price' ], 10, 2 );
		add_filter( 'woocommerce_cart_get_subtotal', [ $this, 'adjust_cart_subtotal' ] );
		add_filter( 'woocommerce_get_discounted_price', [ $this, 'adjust_cart_total' ], 10, 2 );

		// Checkout
		add_filter( 'woocommerce_checkout_cart_item_quantity', [ $this, 'show_free_label_on_checkout' ], 10, 2 );

		// Order Completed
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'set_gift_order_item_price' ], 10, 4 );
	}

	public function remove_free_gifts_from_items_to_validate( $items ) {
		foreach ( $items as $key => $item ) {
			if ( isset( $item->object['wpcfg_used_coupon'] ) ) {
				unset( $items[ $key ] );
			}
		}

		return $items;
	}

	public function remove_invalid_free_gifts_from_cart() {
		if ( ! $this->is_frontend_request() ) {
			return;
		}

		$check_main_item = false;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

			if ( ! isset( $cart_item['wpcfg_used_coupon'] ) ) {
				$check_main_item = true;
				continue;
			}

			$used_coupon_id     = $cart_item['wpcfg_used_coupon'];
			$free_gift_quantity = $cart_item['quantity'];
			$product_id         = isset( $cart_item['data'] ) ? $cart_item['data']->get_id() : null;

			if ( ! isset( $this->data['granted_coupons'][ $used_coupon_id ] ) || ! $product_id || ! in_array( $product_id, $this->data['granted_coupons'][ $used_coupon_id ]['gifts'] ) || $this->data['granted_coupons'][ $used_coupon_id ]['max_each'] < $free_gift_quantity ) {

				WC()->cart->remove_cart_item( $cart_item_key );
				continue;
			}

			$free_gift_quantity = $cart_item['quantity'];

			if ( (int) $this->data['granted_coupons'][ $used_coupon_id ]['max_each'] === (int) $free_gift_quantity ) {
				$free_gift_key = array_search( $product_id, $this->data['granted_coupons'][ $used_coupon_id ]['gifts'] );
				unset( $this->data['granted_coupons'][ $used_coupon_id ]['gifts'][ $free_gift_key ] );
			}

			$this->data['granted_coupons'][ $used_coupon_id ]['picks_left'] -= $free_gift_quantity;

			if ( 0 === $this->data['granted_coupons'][ $used_coupon_id ]['picks_left'] ) {
				$this->data['granted_coupons'][ $used_coupon_id ]['gifts'] = [];
			}

		}

		if ( ! $check_main_item ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}
		}
	}

	public function validate_free_gift( $is_valid, $product_id, $quantity ) {
		if ( ! isset( $_POST['wpcfg_free_gift'] ) ) {
			return $is_valid;
		}

		if ( ! isset( $this->data['granted_coupons'] ) || ! is_array( $this->data['granted_coupons'] ) || ! $this->data['granted_coupons'] ) {
			wc_add_notice( esc_html__( 'Unable to add this product to the cart.', 'wpc-free-gift-coupons' ), 'error' );

			return false;
		}

		foreach ( $this->data['granted_coupons'] as $coupon_data ) {
			if ( ! in_array( $product_id, $coupon_data['gifts'] ) || 0 >= $coupon_data['picks_left'] || $coupon_data['picks_left'] < $quantity ) {

				continue;
			}

			if ( ! defined( 'WPCFG_CURRENT_GIFT_USED_COUPON' ) ) {
				define( 'WPCFG_CURRENT_GIFT_USED_COUPON', $coupon_data['coupon_id'] );
			}

			return true;
		}

		return $is_valid;
	}

	public function add_free_gift_cart_item_data( $cart_item_data ) {
		if ( isset( $_POST['wpcfg_free_gift'] ) ) {
			if ( ! defined( 'WPCFG_CURRENT_GIFT_USED_COUPON' ) ) {
				$cart_item_data['wpcfg_used_coupon'] = null;
			} else {
				$cart_item_data['wpcfg_used_coupon'] = WPCFG_CURRENT_GIFT_USED_COUPON;
			}
		}

		return $cart_item_data;
	}

	public function order_gifts_by_id( $query_args ) {
		if ( ! $this->is_generating_gifts() ) {
			return $query_args;
		}

		$query_args['post_type'] = [ 'product', 'product_variation' ];
		$query_args['orderby']   = 'ID';

		return $query_args;
	}

	public function set_gifts_product_price( $price ) {
		if ( ! $this->is_generating_gifts() ) {
			return $price;
		}

		return '0';
	}

	public function tag_gifts_product_as_free( $args ) {
		if ( $this->is_generating_gifts() ) {
			$args['attributes']['data-wpcfg_free_gift'] = '';
		}

		return $args;
	}

	/**
	 * @param $price
	 * @param $cart_item
	 *
	 * @return mixed|string
	 */
	public function set_free_gift_cart_item_price( $price, $cart_item ) {
		if ( ! isset( $cart_item['wpcfg_used_coupon'] ) ) {
			return $price;
		}

		return wc_price( 0 );
	}

	/**
	 * @param $subtotal
	 *
	 * @return float|int
	 */
	public function adjust_cart_subtotal( $subtotal ) {
		$subtotal = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product          = $cart_item['data'];
			$product_quantity = $cart_item['quantity'];

			if ( ! isset( $cart_item['wpcfg_used_coupon'] ) ) {
				$subtotal += $product->get_price() * $product_quantity;
			}
		}

		return $subtotal;
	}

	/**
	 * @param $discounted_price
	 * @param $cart_item
	 *
	 * @return int|mixed
	 */
	public function adjust_cart_total( $discounted_price, $cart_item ) {
		if ( ! isset( $cart_item['wpcfg_used_coupon'] ) ) {
			return $discounted_price;
		}

		return 0;
	}

	public function set_gift_order_item_price( $item, $cart_item_key, $cart_item, $order ) {
		if ( ! isset( $cart_item['wpcfg_used_coupon'] ) ) {
			return;
		}

		$item->set_subtotal( 0 );
	}

	public function show_free_label_on_cart( $cart_item ) {
		if ( ! isset( $cart_item['wpcfg_used_coupon'] ) ) {
			return;
		}

		echo '<p>' . Wpcfg_Helper::get_setting( 'label', esc_html__( 'Gift', 'wpc-free-gift-coupons' ) ) . '</p>';
	}

	/**
	 * @param $formatted_quantity
	 * @param $cart_item
	 *
	 * @return mixed|string
	 */
	public function show_free_label_on_checkout( $formatted_quantity, $cart_item ) {
		if ( ! isset( $cart_item['wpcfg_used_coupon'] ) ) {
			return $formatted_quantity;
		}

		return $formatted_quantity . '<p>' . Wpcfg_Helper::get_setting( 'label', esc_html__( 'Gift', 'wpc-free-gift-coupons' ) ) . '</p>';
	}

	public function set_gifts() {
		if ( ! isset( $this->data['granted_coupons'] ) || ! is_array( $this->data['granted_coupons'] ) || ! $this->data['granted_coupons'] ) {
			return;
		}

		foreach ( $this->data['granted_coupons'] as $config ) {
			foreach ( $config['gifts'] as $gift_id ) {
				if ( ! isset( $this->data['gifts'][ $gift_id ] ) ) {
					$this->data['gifts'][ $gift_id ] = $gift_id;
				}
			}
		}

		$auto_add_to_cart = Wpcfg_Helper::get_setting( 'auto_add_to_cart', '' );

		if ( $auto_add_to_cart !== 'yes' || ! isset( $this->data['gifts'] ) || ! is_array( $this->data['gifts'] ) || 1 !== count( $this->data['gifts'] ) ) {
			return;
		}

		$gift_id = current( $this->data['gifts'] );

		foreach ( $this->data['granted_coupons'] as $coupon_id => $config ) {
			$quantity                            = $config['max_each'] ?? 0;
			$cart_item_data['wpcfg_used_coupon'] = $config['coupon_id'];
			WC()->cart->add_to_cart( $gift_id, $quantity, 0, [], $cart_item_data );
			$this->data['granted_coupons'][ $coupon_id ]['picks_left'] -= $config['max_each'];
			$this->data['granted_coupons'][ $coupon_id ]['gifts']      = [];
		}

		$this->data['gifts'] = [];
	}

	public function set_gift_in_coupons() {
		if ( $this->is_frontend_request() ) {
			foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
				/**
				 * @var $coupon WC_Coupon
				 */
				$config    = $coupon->get_meta( 'wpcfg_config' );
				$coupon_id = $coupon->get_id();

				if ( 'wpcfg' === $coupon->get_discount_type() ) {
					$this->save_granted_coupon( $coupon_id, $code, $config );
				} else {
					if ( ! isset( $config['enable'] ) ) {
						continue;
					}

					$this->save_granted_coupon( $coupon_id, $code, $config );
				}
			}
		}
	}

	private function save_granted_coupon( $coupon_id, $code, $config ) {
		$config['max_whole'] = $config['max_whole'] ?: 1;
		$config['max_each']  = $config['max_each'] ?: 1;

		$this->data['granted_coupons'][ $coupon_id ] = [
			'coupon_id'     => $coupon_id,
			'code'          => $code,
			'discount_type' => 'wpcfg',
			'gifts'         => $config['gifts'],
			'picks_left'    => $config['max_whole'],
			'max_each'      => 0 !== $config['max_each'] && $config['max_each'] <= $config['max_whole'] ? $config['max_each'] : $config['max_whole'],
		];
	}

	public function show_gifts() {
		if ( ! is_cart() && ! ( is_checkout() && is_ajax() ) ) {
			return;
		}

		if ( ! isset( $this->data['gifts'] ) || ! is_array( $this->data['gifts'] ) || ! $this->data['gifts'] ) {
			return;
		}

		$this->data['generating_gifts'] = true;
		?>
        <div class="wpcfg-wrapper">
            <h2><?php echo esc_html( Wpcfg_Helper::get_setting( 'heading', 'You\'ve been granted some gifts!' ) ) ?></h2>
            <div class="wpcfg-gifts">
				<?php echo WC_Shortcodes::products( [ 'ids' => join( ',', $this->data['gifts'] ) ] ); ?>
            </div>
        </div>
		<?php
		$this->data['generating_gifts'] = false;
	}

	public function is_generating_gifts() {
		if ( ! isset( $this->data['generating_gifts'] ) || ! is_bool( $this->data['generating_gifts'] ) ) {
			return false;
		}

		return $this->data['generating_gifts'];
	}
}

Wpcfg_Frontend::instance();
