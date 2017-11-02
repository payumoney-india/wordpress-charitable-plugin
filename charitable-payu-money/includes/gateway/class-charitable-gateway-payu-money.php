<?php
/**
 * PayUMoney Gateway class
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Gateway_PayU_Money
 * @author      Eric Daams
 * @copyright   Copyright (c) 2015, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'Charitable_Gateway_PayU_Money' ) ) :

	/**
	 * PayUMoney Gateway
	 *
	 * @since       1.0.0
	 */
	class Charitable_Gateway_PayU_Money extends Charitable_Gateway {

		/**
		 * @var     string
		 */
		const ID = 'payu_money';

		/**
		 * Instantiate the gateway class, defining its key values.
		 *
		 * @access  public
		 * @since   1.0.0
		 */
		public function __construct() {
			$this->name = apply_filters( 'charitable_gateway_payu_money_name', __( 'PayUMoney', 'charitable-payu-money' ) );

			$this->defaults = array(
				'label' => __( 'PayUMoney', 'charitable-payu-money' ),
			);

			$this->supports = array(
				'1.3.0',
			);

			/**
			 * Needed for backwards compatibility with Charitable < 1.3
			 */
			$this->credit_card_form = false;
		}

		/**
		 * Returns the current gateway's ID.
		 *
		 * @return  string
		 * @access  public
		 * @static
		 * @since   1.0.3
		 */
		public static function get_gateway_id() {
			return self::ID;
		}

		/**
		 * Register gateway settings.
		 *
		 * @param   array $settings
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function gateway_settings( $settings ) {
			if ( 'INR' != charitable_get_option( 'currency', 'AUD' ) ) {
				$settings['currency_notice'] = array(
					'type'      => 'notice',
					'content'   => $this->get_currency_notice(),
					'priority'  => 1,
					'notice_type' => 'error',
				);
			}

			$settings['live_merchant_key'] = array(
				'type'      => 'text',
				'title'     => __( 'Live Merchant Key', 'charitable-payu-money' ),
				'priority'  => 6,
			);

			$settings['live_salt'] = array(
				'type'      => 'text',
				'title'     => __( 'Live Merchant Salt', 'charitable-payu-money' ),
				'priority'  => 8,
			);

			$settings['test_merchant_key'] = array(
				'type'      => 'text',
				'title'     => __( 'Test Merchant Key', 'charitable-payu-money' ),
				'priority'  => 10,
			);

			$settings['test_salt'] = array(
				'type'      => 'text',
				'title'     => __( 'Test Merchant Salt', 'charitable-payu-money' ),
				'priority'  => 12,
			);

			// if ( charitable_get_option( 'test_mode', false ) ) {
			//     $settings[ 'test_mode_information' ] = array(
			//         'type'      => 'content',
			//         'content'   => sprintf( '%s<table><tr><th>%s</th><td>%s</td></tr><tr><th>%s</th><td>%s</td></tr><tr><th>%s</th><td>%s</td></tr><tr><th>%s</th><td>%s</td></tr></table>',
			//             __( 'While you have Test Mode enabled, use the following credit card details to test PayUMoney:', 'charitable-payu-money' ),
			//             __( 'Test Card Name', 'charitable-payu-money' ),
			//             __( 'Any name', 'charitable-payu-money' ),
			//             __( 'Test Card Number', 'charitable-payu-money' ),
			//             '5123 4567 8901 2346',
			//             __( 'Test Card CVV', 'charitable-payu-money' ),
			//             '123',
			//             __( 'Test Card Expiry', 'charitable-payu-money' ),
			//             __( 'May 2017', 'charitable-payu-money' )
			//         ),
			//         'priority'  => 14
			//     );
			// }

			return $settings;
		}

		/**
		 * Return the keys to use.
		 *
		 * This will return the test keys if test mode is enabled. Otherwise, returns
		 * the production keys.
		 *
		 * @return  string[]
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_keys() {
			$keys = array();

			if ( charitable_get_option( 'test_mode' ) ) {
				$keys['merchant_key'] = trim( $this->get_value( 'test_merchant_key' ) );
				$keys['salt'] = trim( $this->get_value( 'test_salt' ) );
			} else {
				$keys['merchant_key'] = trim( $this->get_value( 'live_merchant_key' ) );
				$keys['salt'] = trim( $this->get_value( 'live_salt' ) );
			}

			return $keys;
		}

		/**
		 * Return the base URL.
		 *
		 * @return  string
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_base_url() {
			if ( charitable_get_option( 'test_mode' ) ) {
				return 'https://test.payu.in/_payment';
			}

			return 'https://secure.payu.in/_payment';
		}

		/**
		 * Process the donation with PayUMoney.
		 *
		 * @param   Charitable_Donation $donation
		 * @return  void
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function process_donation( $content, Charitable_Donation $donation ) {
			$gateway      = new Charitable_Gateway_PayU_Money();
			$donor        = $donation->get_donor();
			$first_name   = $donor->get_donor_meta( 'first_name' );
			$last_name    = $donor->get_donor_meta( 'last_name' );
			$address      = $donor->get_donor_meta( 'address' );
			$address_2    = $donor->get_donor_meta( 'address_2' );
			$email 		  = $donor->get_donor_meta( 'email' );
			$city         = $donor->get_donor_meta( 'city' );
			$state        = $donor->get_donor_meta( 'state' );
			$country      = $donor->get_donor_meta( 'country' );
			$postcode     = $donor->get_donor_meta( 'postcode' );
			$phone        = $donor->get_donor_meta( 'phone' );
			$donation_key = $donation->get_donation_key();
			$amount 	  = $donation->get_total_donation_amount( true );
			$product_info = sprintf( __( 'Donation %d', 'charitable-payu-money' ), $donation->ID );
			$keys 		  = $gateway->get_keys();

			$str = "{$keys['merchant_key']}|{$donation_key}|{$amount}|{$product_info}|{$first_name}|{$email}|{$donation->ID}||||||||||{$keys['salt']}";
			$hash = strtolower( hash( 'sha512', $str ) );

			$return_url = charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation->ID ) );

			$cancel_url = charitable_get_permalink( 'donation_cancel_page', array( 'donation_id' => $donation->ID ) );

			if ( ! $cancel_url ) {
				$cancel_url = esc_url( add_query_arg( array(
					'donation_id' => $donation->ID,
					'cancel' => true,
				), wp_get_referer() ) );
			}

			$payu_args = apply_filters( 'charitable_payu_redirect_args', array(
				'key'           	=> $keys['merchant_key'],
				'txnid'         	=> $donation_key,
				'amount'        	=> $amount,
				'productinfo'   	=> $product_info,
				'firstname'     	=> $first_name,
				'lastname'      	=> $last_name,
				'address1'      	=> $address,
				'address2'      	=> $address_2,
				'city'          	=> $city,
				'state'         	=> $state,
				'country'       	=> $country,
				'zipcode'       	=> $postcode,
				'email'         	=> $email,
				'phone'         	=> $phone,
				'udf1'          	=> $donation->ID,
				'surl'          	=> $return_url,
				'furl'          	=> $cancel_url,
				'hash'          	=> $hash,
				'service_provider'  => 'payu_paisa',
			), $donation );

			ob_start();

			echo $content;
	?>
	<form method="post" action="<?php echo $gateway->get_base_url() ?>" id="payu-money-form">
	<?php foreach ( $payu_args as $key => $value ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( $value ) ?>" />
	<?php endforeach ?>
	</form>
	<script type="text/javascript">        
	function charitable_submit_payu_money_form() {
		var form = document.getElementById('payu-money-form');
		form.submit();
	}

	window.onload = charitable_submit_payu_money_form;
	</script>
			<?php
			$content = ob_get_clean();

			return $content;
		}

		/**
		 * Check PayU India reponse.
		 *
		 * @param   Charitable_Donation $donation
		 * @return  void
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function process_response( Charitable_Donation $donation ) {
			if ( ! isset( $_REQUEST['txnid'] ) || ! isset( $_REQUEST['mihpayid'] ) ) {
				return;
			}

			$donation_id = $_REQUEST['udf1'];
			$donation_key = $_REQUEST['txnid'];
			$amount = $_REQUEST['amount'];

			if ( $donation_id != $donation->ID ) {
				return;
			}

			try {
				$gateway = new Charitable_Gateway_PayU_Money();
				$keys = $gateway->get_keys();
				$hash = $_REQUEST['hash'];
				$status = $_REQUEST['status'];
				$checkhash = hash( 'sha512', "{$keys['salt']}|$status||||||||||$donation_id|$_REQUEST[email]|$_REQUEST[firstname]|$_REQUEST[productinfo]|$amount|$donation_key|{$keys['merchant_key']}" );

				/* If the $hash and $checkhash don't match, it has been tampered with. */
				if ( $hash != $checkhash ) {

					charitable_get_notices()->add_error( __( 'Security Error. Illegal access detected.', 'charitable-payu-money' ) );
					return;

				}

				$status = strtolower( $status );

				/* The donation succeeded. */
				if ( 'success' == $status ) {

					/* If the donation had already been marked as complete, stop here. */
					if ( 'charitable-completed' == get_post_status( $donation_id ) ) {
						return;
					}

					/* If the donation key sent in the request does not match the one we have one store, cancel. */
					if ( $donation_key != $donation->get_donation_key() ) {

						$message = sprintf( __( 'The donation key in the response does not match the donation. Response data: %s', 'charitable-payu-money' ), json_encode( $_REQUEST ) );
						self::update_donation_log( $donation, $message );
						$donation->update_status( 'charitable-failed' );
						return;

					}

					/* Verify that the amount in the response matches the amount we expected. */
					if ( $amount < $donation->get_total_donation_amount() ) {

						$message = sprintf( __( 'The amount in the response does not match the expected donation amount. Response data: %s', 'charitable-payu-money' ), json_encode( $_REQUEST ) );
						self::update_donation_log( $donation, $message );
						$donation->update_status( 'charitable-failed' );
						return;

					}

					/* Everything checks out, so update the status and log the Source Reference ID (mihpayid) */
					$message = sprintf( __( 'PayU India Transaction ID: %s', 'charitable-payu-money' ), $_REQUEST['mihpayid'] );
					self::update_donation_log( $donation, $message );
					$donation->update_status( 'charitable-completed' );
					return;

				}

				/* The donation failed for some reason. */
				if ( 'failure' == $status ) {

					$message = sprintf( __( 'Unfortunately, your donation was declined by our payment gateway. Error message: %s', 'charitable-payu-money' ), $_REQUEST['error'] );
					self::update_donation_log( $donation, $message );
					$donation->update_status( 'charitable-failed' );
					return;

				}
			} catch ( Exception $e ) {
			}
		}

		/**
		 * Update the donation's log. 
		 *
		 * @return  void
		 * @access  public
		 * @static 
		 * @since   1.1.0		 
		 */
		public static function update_donation_log( $donation, $message ) {
			if ( version_compare( charitable()->get_version(), '1.4.0', '<' ) ) {
				return Charitable_Donation::update_donation_log( $donation->ID, $message );
			}

			return $donation->update_donation_log( $message );
		}

		/**
		 * Set the phone field to be required in the donation form.
		 *
		 * @param   array[] $fields
		 * @return  array[]
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function set_phone_field_required( $fields ) {
			$fields['phone']['required'] = true;
			return $fields;
		}

		/**
		 * Return the HTML for the currency notice.
		 *
		 * @return  string
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_currency_notice() {
			ob_start();
	?>        
	<?php printf( __( 'PayU India only accepts payments in Indian Rupees. %sChange Now%s', 'charitable-payu-money' ),
		'<a href="#" class="button" data-change-currency-to-inr>', '</a>'
	) ?>
	<script>
	( function( $ ){
	$( '[data-change-currency-to-inr]' ).on( 'click', function() {
		var $this = $(this);

		$.ajax({
			type: "POST",
			data: {
				action: 'charitable_change_currency_to_inr', 
				_nonce: "<?php echo wp_create_nonce( 'payu_money_currency_change' ) ?>"
			},
			url: ajaxurl,
			success: function ( response ) {
				console.log( response );

				if ( response.success ) {
					$this.parents( '.notice' ).first().slideUp();
				}            
			}, 
			error: function( response ) {
				console.log( response );
			}
		});
	})
	})( jQuery );
	</script>
	<?php
			return ob_get_clean();
		}

		/**
		 * Change the currency to INR.
		 *
		 * @return  void
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function change_currency_to_inr() {
			if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'payu_money_currency_change' ) ) {
				wp_send_json_error();
			}

			$settings = get_option( 'charitable_settings' );
			$settings['currency'] = 'INR';
			$updated = update_option( 'charitable_settings', $settings );

			wp_send_json( array( 'success' => $updated ) );
			wp_die();
		}

		/**
		 * Redirect the donation to the processing page.
		 *
		 * @param   int $donation_id
		 * @return  void
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function redirect_to_processing_legacy( $donation_id ) {
			wp_safe_redirect(
				charitable_get_permalink( 'donation_processing_page', array(
					'donation_id' => $donation_id,
				) )
			);

			exit();
		}
	}

endif; // End class_exists check
