<?php

class Wpcfg_Backend {
	protected static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		// Settings
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

		// Add Tab
		add_filter( 'woocommerce_coupon_data_tabs', [ $this, 'add_tab_coupon' ] );
		add_action( 'woocommerce_coupon_data_panels', [ $this, 'add_tab_coupon_panel' ] );

		add_action( 'post_updated', [ $this, 'save_coupons' ] );
		add_filter( 'woocommerce_coupon_discount_types', [ $this, 'add_coupon_discount_type' ] );
	}

	/**
	 * @return void
	 */
	public function register_settings() {
		// settings
		register_setting( 'wpcfg_settings', 'wpcfg_settings' );
	}

	/**
	 * @return void
	 */
	public function admin_menu() {
		add_submenu_page( 'wpclever', 'WPC Free Gift Coupons', 'Free Gift Coupons', 'manage_options', 'wpclever-wpcfg', [
			$this,
			'admin_menu_content'
		] );
	}

	/**
	 * @return void
	 */
	public function admin_menu_content() {
		$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
		?>
        <div class="wpclever_settings_page wrap">
            <h1 class="wpclever_settings_page_title"><?php echo 'WPC Free Gift Coupons ' . esc_html( WPCFG_VERSION ); ?></h1>
            <div class="wpclever_settings_page_desc about-text">
                <p>
					<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-free-gift-coupons' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                    <br/>
                    <a href="<?php echo esc_url( WPCFG_REVIEWS ); ?>" target="_blank"><?php esc_html_e( 'Reviews', 'wpc-free-gift-coupons' ); ?></a> |
                    <a href="<?php echo esc_url( WPCFG_CHANGELOG ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'wpc-free-gift-coupons' ); ?></a> |
                    <a href="<?php echo esc_url( WPCFG_DISCUSSION ); ?>" target="_blank"><?php esc_html_e( 'Discussion', 'wpc-free-gift-coupons' ); ?></a>
                </p>
            </div>
			<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Settings updated.', 'wpc-free-gift-coupons' ); ?></p>
                </div>
			<?php } ?>
            <div class="wpclever_settings_page_nav">
                <h2 class="nav-tab-wrapper">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcfg&tab=settings' ) ); ?>" class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
						<?php esc_html_e( 'Settings', 'wpc-free-gift-coupons' ); ?>
                    </a> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
						<?php esc_html_e( 'Essential Kit', 'wpc-free-gift-coupons' ); ?>
                    </a>
                </h2>
            </div>
            <div class="wpclever_settings_page_content">
                <form method="post" action="options.php">
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Free gift heading', 'wpc-free-gift-coupons' ); ?></th>
                            <td>
                                <label>
                                    <input type="text" name="wpcfg_settings[heading]" class="regular-text" placeholder="<?php echo esc_attr( 'You\'ve been granted some gifts!' ); ?>" value="<?php echo esc_attr( Wpcfg_Helper::get_setting( 'heading', '' ) ); ?>"/>
                                </label>
                                <span class="description"><?php echo esc_html__( 'The text to display before the gift list.', 'wpc-free-gift-coupons' ); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Free gift label', 'wpc-free-gift-coupons' ); ?></th>
                            <td>
                                <label>
                                    <input type="text" name="wpcfg_settings[label]" placeholder="<?php esc_attr_e( 'Gift', 'wpc-free-gift-coupons' ); ?>" value="<?php echo esc_attr( Wpcfg_Helper::get_setting( 'label', '' ) ); ?>"/>
                                </label>
                                <span class="description"><?php echo esc_html__( 'The label for the free gift in the cart.', 'wpc-free-gift-coupons' ); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Automatically add single free gift to cart', 'wpc-free-gift-coupons' ); ?></th>
                            <td>
                                <label> <input type="checkbox" name="wpcfg_settings[auto_add_to_cart]" value="yes"
										<?php checked( Wpcfg_Helper::get_setting( 'auto_add_to_cart', '' ), 'yes' ) ?>/>
                                </label>
                                <span class="description"><?php echo esc_html__( 'When there is only one free gift product granted, it is automatically added to the cart.', 'wpc-free-gift-coupons' ); ?></span>
                            </td>
                        </tr>
                        <tr class="submit">
                            <th colspan="2">
								<?php settings_fields( 'wpcfg_settings' ); ?><?php submit_button(); ?>
                            </th>
                        </tr>
                    </table>
                </form>
            </div><!-- /.wpclever_settings_page_content -->
            <div class="wpclever_settings_page_suggestion">
                <div class="wpclever_settings_page_suggestion_label">
                    <span class="dashicons dashicons-yes-alt"></span> Suggestion
                </div>
                <div class="wpclever_settings_page_suggestion_content">
                    <div>
                        To display custom engaging real-time messages on any wished positions, please install
                        <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart Messages</a> plugin. It's free!
                    </div>
                    <div>
                        Wanna save your precious time working on variations? Try our brand-new free plugin
                        <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC Variation Bulk Editor</a> and
                        <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC Variation Duplicator</a>.
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * @param $discount_types
	 *
	 * @return mixed
	 */
	public function add_coupon_discount_type( $discount_types ) {
		$discount_types['wpcfg'] = esc_html__( 'WPC Free Gift', 'wpc-free-gift-coupons' );

		return $discount_types;
	}

	/**
	 * @param $coupon_id
	 *
	 * @return void
	 */
	public function save_coupons( $coupon_id ) {
		if ( isset( $_POST['wpcfg_config'] ) ) {
			$config = ! empty( $_POST['wpcfg_config'] ) ? Wpcfg_Helper::sanitize_array( $_POST['wpcfg_config'] ) : [];

			if ( ! isset( $_POST['wpcfg_config_save_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wpcfg_config_save_nonce'] ), 'wpcfg-coupon-config-save' ) || ! current_user_can( 'edit_post', $coupon_id ) ) {
				return;
			}

			update_post_meta( $coupon_id, 'wpcfg_config', $config );
		}
	}

	/**
	 * @param $tabs array
	 *
	 * @return array
	 */
	public function add_tab_coupon( $tabs ) {
		$tabs['wpcfg_free_gift'] = [
			'label'  => esc_html__( 'WPC Free Gift', 'wpc-free-gift-coupons' ),
			'target' => 'wpcfg_free_gift_data',
			'class'  => '',
		];

		return $tabs;
	}

	/**
	 * @param $coupon_id
	 *
	 * @return void
	 */
	public function add_tab_coupon_panel( $coupon_id ) {
		$config    = (array) get_post_meta( $coupon_id, 'wpcfg_config', true );
		$is_enable = ! empty( $config['enable'] ) ? 'yes' : false;

		wp_nonce_field( 'wpcfg-coupon-config-save', 'wpcfg_config_save_nonce' );
		?>
        <div id="wpcfg_free_gift_data" class="panel woocommerce_options_panel">
            <div class="options_group">
				<?php
				woocommerce_wp_checkbox(
					[
						'id'          => 'wpcfg-is-enable',
						'name'        => 'wpcfg_config[enable]',
						'label'       => esc_html__( 'Active', 'wpc-free-gift-coupons' ),
						'description' => esc_html__( 'Check this box to give free gifts after the coupon is applied.', 'wpc-free-gift-coupons' ),
						'cbvalue'     => 'yes',
						'value'       => $is_enable,
					]
				);
				?>
                <p class="form-field">
                    <label><?php echo esc_html__( 'Free gifts', 'wpc-free-gift-coupons' ); ?></label> <label>
                        <select class="wc-product-search wpcfg-required-coupon-config" multiple="multiple" style="width: 50%;" name="wpcfg_config[gifts][]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wpc-free-gift-coupons' ); ?>" data-action="woocommerce_json_search_products_and_variations">
							<?php
							$free_gift_ids = ! empty( $config['gifts'] ) ? $config['gifts'] : [];

							foreach ( $free_gift_ids as $product_id ) {
								$product = wc_get_product( $product_id );

								if ( is_object( $product ) ) {
									echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
								}
							}
							?>
                        </select> </label>
                </p>
				<?php
				woocommerce_wp_text_input(
					[
						'id'                => 'wpcfg-max-free-gifts',
						'class'             => 'wpcfg-required-coupon-config',
						'name'              => 'wpcfg_config[max_whole]',
						'label'             => esc_html__( 'Maximum number of granted free gifts', 'wpc-free-gift-coupons' ),
						'placeholder'       => 1,
						'description'       => esc_html__( 'The maximum number of free gift items the customer is allowed to pick.', 'wpc-free-gift-coupons' ),
						'type'              => 'number',
						'desc_tip'          => true,
						'value'             => ! empty( $config['max_whole'] ) ? $config['max_whole'] : '',
						'custom_attributes' => [
							'min' => 1,
						],
					]
				);
				woocommerce_wp_text_input(
					[
						'id'                => 'wpcfg-max-qty-per-product',
						'class'             => 'wpcfg-required-coupon-config',
						'name'              => 'wpcfg_config[max_each]',
						'label'             => esc_html__( 'Maximum quantity per free gift product', 'wpc-free-gift-coupons' ),
						'placeholder'       => 1,
						'description'       => esc_html__( 'The maximum quantity the customer can have per free gift. Enter 0 for infinity.', 'wpc-free-gift-coupons' ),
						'type'              => 'number',
						'desc_tip'          => true,
						'value'             => $config['max_each'] ?? '',
						'custom_attributes' => [
							'min' => 0,
						],
					]
				);
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wpcfg-backend', WPCFG_URI . 'assets/js/backend.js', [
			'jquery',
			'wc-enhanced-select',
			'jquery-ui-sortable',
			'selectWoo'
		], WPCFG_VERSION, true );
	}

	function action_links( $links, $file ) {
		static $plugin;

		if ( ! isset( $plugin ) ) {
			$plugin = plugin_basename( WPCFG_FILE );
		}

		if ( $plugin === $file ) {
			$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpcfg&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-free-gift-coupons' ) . '</a>';
			array_unshift( $links, $settings );
		}

		return (array) $links;
	}

	function row_meta( $links, $file ) {
		static $plugin;

		if ( ! isset( $plugin ) ) {
			$plugin = plugin_basename( WPCFG_FILE );
		}

		if ( $plugin === $file ) {
			$row_meta = [
				'support' => '<a href="' . esc_url( WPCFG_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-free-gift-coupons' ) . '</a>',
			];

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}
}

Wpcfg_Backend::instance();
