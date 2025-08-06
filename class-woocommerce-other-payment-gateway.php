<?php

class WC_Other_Payment_Gateway extends WC_Payment_Gateway{

    private $order_status;
    private $text_box_required;
    private $hide_text_box;


	public function __construct(){
		$this->id = 'other_payment';
		$this->method_title = __('Custom Payment','woocommerce-other-payment-gateway');
		$this->icon = apply_filters( 'woocommerce-other-payment-gateway', plugins_url('/assets/icon.png', __FILE__ ) );
		$this->title = __('Custom Payment','woocommerce-other-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->hide_text_box = $this->get_option('hide_text_box');
		$this->text_box_required = $this->get_option('text_box_required');
		$this->order_status = $this->get_option('order_status');
		$this->name = $this->get_option('name');
		$this->UPI = $this->get_option('UPI');


		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
	}

	public function init_form_fields(){
				$this->form_fields = array(
					'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce-other-payment-gateway' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable Custom Payment', 'woocommerce-other-payment-gateway' ),
					'default' 		=> 'no'
					),

		            'title' => array(
						'title' 		=> __( 'Method Title', 'woocommerce-other-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'This controls the title', 'woocommerce-other-payment-gateway' ),
						'default'		=> __( 'Custom Payment', 'woocommerce-other-payment-gateway' ),
						'desc_tip'		=> true,
					),
					'name' => array(
						'title' 		=> __( 'Name', 'woocommerce-other-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'Show your name in barcode', 'woocommerce-other-payment-gateway' ),
						'default'		=> __( '', 'woocommerce-other-payment-gateway' ),
						'desc_tip'		=> true,
					),
					'UPI' => array(
						'title' 		=> __( 'UPI', 'woocommerce-other-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'UPI ID - Google Pay..etc', 'woocommerce-other-payment-gateway' ),
						'default'		=> __( '', 'woocommerce-other-payment-gateway' ),
						'desc_tip'		=> true,
					),
					'description' => array(
						'title' => __( 'Customer Message', 'woocommerce-other-payment-gateway' ),
						'type' => 'textarea',
						'css' => 'width:500px;',
						'default' => 'None of the other payment options are suitable for you? please drop us a note about your favourable payment option and we will contact you as soon as possible.',
						'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'woocommerce-other-payment-gateway' ),
					),
					'text_box_required' => array(
						'title' 		=> __( 'Make the text field required', 'woocommerce-other-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Make the text field required', 'woocommerce-other-payment-gateway' ),
						'default' 		=> 'no'
					),
					'hide_text_box' => array(
						'title' 		=> __( 'Hide The Payment Field', 'woocommerce-other-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Hide', 'woocommerce-other-payment-gateway' ),
						'default' 		=> 'no',
						'description' 	=> __( 'If you do not need to show the text box for customers at all, enable this option.', 'woocommerce-other-payment-gateway' ),
					),
					'order_status' => array(
						'title' => __( 'Order Status After The Checkout', 'woocommerce-other-payment-gateway' ),
						'type' => 'select',
						'options' => wc_get_order_statuses(),
						'default' => 'wc-completed',
						'description' 	=> __( 'The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway' ),
					),
			 );
	}
	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @return void
	 */

	public function validate_fields() {
	    if ($this->text_box_required === 'no') {
	        return true;
        }

        if ($this->hide_text_box === 'yes') {
            return true;
        }

	    $textbox_value = (isset($_POST['other_payment-admin-note']))? trim($_POST['other_payment-admin-note']): '';

		if ($textbox_value === '') {
			wc_add_notice( __('Update Transection or Reference ID','woocommerce-custom-payment-gateway'), 'error');
			return false;
        }

		return true;
	}

	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status($this->order_status, __( 'Awaiting payment', 'woocommerce-other-payment-gateway' ));
		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );
		if(isset($_POST[ $this->id.'-admin-note']) && trim($_POST[ $this->id.'-admin-note'])!=''){
			$order->add_order_note(esc_html($_POST[ $this->id.'-admin-note']));
		}
		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

	public function payment_fields(){
	    ?>
		<fieldset>
			<p class="form-row form-row-wide">
				<label for="<?php echo $this->id; ?>-admin-note"><?php echo ($this->description); ?> <?php if($this->text_box_required === 'yes'): ?> <span class="required"></span> <?php endif; ?></label>
				<?php
				if ($this->hide_text_box !== 'yes') {
					$order_total = ( is_object( WC()->cart ) && method_exists( WC()->cart, 'get_total' ) ) ? WC()->cart->get_total('edit') : '';
					if ($order_total) {
						$order_total_numeric = floatval(preg_replace('/[^\d.]/', '', $order_total));
						$upi_id = $this->UPI; 
						$upi_name = $this->name;
						$transaction_note = 'store.pingtamizha.com'; // You can customize or fetch this as needed
						$upi_uri = 'upi://pay?pa=' . urlencode($upi_id)
							. '&pn=' . urlencode($upi_name)
							. '&am=' . urlencode($order_total_numeric)
							. '&cu=INR'
							. '&tn=' . urlencode($transaction_note);
						?>
						<div style="margin:10px 0;">
						<?php if ( !empty($upi_uri) ) : ?>
							
							<div style="margin-bottom:10px; text-align:center;">
								<span id="custom_upi_id" style="font-family:monospace;"><?php echo esc_html($upi_id); ?></span>
								<button type="button" onclick="copyUPI()" style="margin-left:8px; padding:2px 8px; font-size:12px; cursor:pointer;">Copy</button>
							</div>
							<script type="text/javascript">
							function copyUPI() {
								var upiText = document.getElementById('custom_upi_id').innerText;
								if (navigator.clipboard) {
									navigator.clipboard.writeText(upiText).catch(function() {
										alert('Failed to copy UPI ID.');
									});
								} else {
									// fallback for older browsers
									var tempInput = document.createElement('input');
									tempInput.value = upiText;
									document.body.appendChild(tempInput);
									tempInput.select();
									try {
										document.execCommand('copy');
									} catch (err) {
										alert('Failed to copy UPI ID.');
									}
									document.body.removeChild(tempInput);
								}
							}
							</script>

							<div id="custom_qrcode" style="display: flex; justify-content: center; align-items: center;"></div>
							<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
							<script type="text/javascript">
								var qrcodeContainer = document.getElementById("custom_qrcode");
								if (qrcodeContainer) {
									qrcodeContainer.innerHTML = "";
									new QRCode(qrcodeContainer, {
										text: "<?php echo esc_js($upi_uri); ?>",
										width: 150,
										height: 150
									});
								}
							</script>
						<?php endif; ?>
						<?php
					}
				}
				?>
				<?php if($this->hide_text_box !== 'yes'){ ?>
					<br>
					<label for="<?php echo $this->id; ?>-admin-note">
						<strong><?php _e('Transaction or Reference ID', 'woocommerce-other-payment-gateway'); ?></strong>
						<!-- <span class="required" style="color: #d00;">*</span> -->
						<?php if($this->text_box_required === 'yes'): ?> <span class="required" style="color: #d00;">*</span> <?php endif; ?>
					</label>
					<input id="<?php echo $this->id; ?>-admin-note" class="input-text" type="text" name="<?php echo $this->id; ?>-admin-note" />
                <?php } ?>
			</p>
			<div class="clear"></div>
		</fieldset>
		<?php
	}
}
