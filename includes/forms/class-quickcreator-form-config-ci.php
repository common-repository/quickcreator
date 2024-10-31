<?php
/**
 *  Form object to easily manage forms.
 *
 * @package QuickcreatorBlog.
 */

namespace QuickcreatorBlog\Forms;

use QuickcreatorBlog\Quickcreatorblog;
use QuickcreatorBlog\Forms\Fields\Quickcreator_Form_Element_Text;
use QuickcreatorBlog\Forms\Validators\Validator_Is_Required;

/**
 * Object to store form data to easily manage forms.
 */
class Quickcreator_Form_Config_Ci extends Quickcreator_Form {

	/**
	 * Construct to initialize form structire.
	 *
	 * @return void
	 */
	public function __construct() {
		$connected = Quickcreator()->get_quickcreator()->is_quickcreator_connected();

		$this->repo = parent::REPO_OPTIONS;

		$field = new Quickcreator_Form_Element_Text( 'quickcreator_api_public_key' );
		$field->set_label( __( 'Quickcreator account', 'quickcreator' ) );
		$field->set_renderer( array( $this, 'render_connection_button' ) );
		$this->add_field( $field );

		$this->display_submit = $connected;
	}

	/**
	 * Renders quickcreator connection button.
	 *
	 * @param Quickcreator_Form_Element $field - field object.
	 * @return void
	 */
	public function render_connection_button( $field ) {
		$connection_details = Quickcreatorblog::get_instance()->get_quickcreator()->wp_connection_details();

		ob_start();
		?>
			<div class="quickcreator-connection-box">
				<div class="quickcreator-connected">
					<h3><?php echo esc_html( $field->get_label() ); ?></h3>
					<p>
					<?php
						esc_html_e(
							'Connect your Quickcreator account to easily optimize your posts with Content Editor',
							'quickcreator'
						);
					?>
					</p>

					<div class="quickcreator-connection-box--connected">
						<p class="quickcreator-connection-box__connection-info">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="currentColor">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M9.74348 1.6319C9.94431 1.74387 10.0429 1.97835 9.98239 2.20018L8.65464 7.06862H13.5C13.6991 7.06862 13.8792 7.18674 13.9586 7.36935C14.0379 7.55195 14.0014 7.76423 13.8655 7.90978L6.86554 15.4098C6.70866 15.5779 6.45736 15.6173 6.25654 15.5053C6.05571 15.3934 5.95713 15.1589 6.01763 14.9371L7.34539 10.0686H2.50001C2.30091 10.0686 2.12079 9.9505 2.04144 9.76789C1.96209 9.58529 1.99863 9.37301 2.13448 9.22746L9.13448 1.72746C9.29137 1.55937 9.54266 1.51994 9.74348 1.6319Z" fill="#338F61"/>
							</svg>

							<?php esc_html_e( 'Connected', 'quickcreator' ); ?>
						</p>

						<p class="quickcreator-connection-box__connection-details">
							<span id="quickcreator-organization-name">
								<?php if ( isset( $connection_details['integration_id'] ) ) : ?>
									<?php echo esc_html( $connection_details['integration_id'] ); ?>
								<?php endif; ?>
							</span>
							<?php esc_html_e( 'via', 'quickcreator' ); ?>
							<span id="quickcreator-via-email">quickcreator
								<?php if ( isset( $connection_details['via_email'] ) ) : ?>
									<?php echo esc_html( $connection_details['via_email'] ); ?>
								<?php endif; ?>
							</span>
						</p>

						<p class="quickcreator-connection-box__actions">
							<button class="quickcreator-button quickcreator-button--secondary quickcreator-button--xsmall" id="quickcreator_disconnect"><?php esc_html_e( 'Disconnect', 'quickcreator' ); ?></button> 
							<button id="quickcreator_reconnect" class="quickcreator-button quickcreator-button--secondary quickcreator-button--xsmall"><?php esc_html_e( 'Replace with another quickcreator account', 'quickcreator' ); ?></button>
							<img src="<?php echo esc_html( includes_url() ); ?>images/spinner.gif" alt="spinner" style="display: none" id="quickcreator-reconnection-spinner" />
						</p>
					</div>
				</div>
				
				<div class="quickcreator-not-connected">
					<p class="quickcreator-text--secondary">
						<?php esc_html_e( 'Gather the up-to-date facts and seamlessly integrate them into compelling SEO-optimized content. Effortlessly address all on-page SEO challenges for each blog post.', 'quickcreator' ); ?>
					</p>

					<div class="quickcreator-connection-box--not-connected">
						<p class="quickcreator-connection-box__actions" style="margin-left: 0px;">
							<button class="quickcreator-button quickcreator-button--primary quickcreator_make_connection">
								<?php esc_html_e( 'Log in and integrate with quickcreator', 'quickcreator' ); ?>
							</button>
							<img src="<?php echo esc_html( includes_url() ); ?>images/spinner.gif" alt="spinner" style="display: none" id="quickcreator-connection-spinner" />
						</p>
					</div>
				</div>
			</div>
		<?php
		$html = ob_get_clean();

		echo $html; // @codingStandardsIgnoreLine
	}
}
