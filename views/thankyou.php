<section class="woocommerce-ms-gp-payment-details">
	<h2>Scan QRCode Pembayaran</h2>
	<div class="ms-gp-payment-action">
		<?php 
		/**
		 * actions: generate-qr-code, deeplink-redirect, get-status, cancel
		 * method: get, get, get, post,
		 * url
		 */
		foreach( $actions as $key => $value ) : ?>
			
			<?php if ( $value['name'] == 'generate-qr-code' ) : ?>
			<div class="qr-code">
				<img src="<?php echo $value['url']; ?>">
			</div>
			<?php endif; ?>

			<?php if ( $value['name'] == 'deeplink-redirect' ) : ?>
				<?php if ( wp_is_mobile() ) : ?>
					<p><?php _e( 'Atau', 'ms-midtrans-gopay' ); ?></p>
					<div class="deeplink-redirect">
						<a class="button" href="<?php echo $value['url']; ?>"><?php _e( 'Bayar dengan aplikasi GoPay', 'ms-midtrans-gopay' ); ?></a>
					</div>
				<?php endif; ?>
			<?php endif; ?>

		<?php endforeach; ?>
	</div>

	<?php if ( '' <> $payment_gateway->get_option( 'instructions' ) ) : ?>
	<div class="ms-gp-payment-instruction">
		<?php echo wpautop( $payment_gateway->get_option( 'instructions' ) ); ?>
	</div>
	<?php endif; ?>

</section>