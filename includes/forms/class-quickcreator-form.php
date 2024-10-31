<?php
/**
 *  Form object to easily manage forms.
 *
 * @package QuickcreatorBlog.
 */

namespace QuickcreatorBlog\Forms;

use QuickcreatorBlog\Quickcreatorblog;

/**
 * Object to store form data to easily manage forms.
 */
class Quickcreator_Form {

	const REPO_DB      = 1;
	const REPO_OPTIONS = 2;

	/**
	 * Stores array of fields.
	 *
	 * @var array
	 */
	protected $fields = null;

	/**
	 * Name of the form.
	 *
	 * @var string
	 */
	protected $name = null;

	/**
	 * Method used by the form: GET|POST.
	 *
	 * @var string
	 */
	protected $method = null;

	/**
	 * CSS classes for the form.
	 *
	 * @var string
	 */
	protected $classes = null;

	/**
	 * If there is an error in the form?
	 *
	 * @var bool
	 */
	protected $has_error = false;

	/**
	 * Where to store data - database|options
	 *
	 * @var int
	 */
	protected $repo = null;

	/**
	 * If this form should have submit button.
	 *
	 * @var bool
	 */
	protected $display_submit = true;

	/**
	 * Basic construct.
	 *
	 * @param string $name    - name of the form.
	 * @param string $classes - CSS classes for the form.
	 * @param string $method  - method used by the form GET|POST.
	 */
	public function __construct( $name, $classes = '', $method = 'POST' ) {
		$this->fields  = array();
		$this->name    = $name;
		$this->repo    = self::REPO_DB;
		$this->method  = $method;
		$this->classes = $classes;
	}

	/**
	 * Adds field to form fields list.
	 *
	 * @param Quickcreator_Form_Element $field - field object.
	 * @return void
	 */
	public function add_field( $field ) {
		$this->fields[ $field->get_name() ] = $field;
	}

	/**
	 * Removes element from fields list.
	 *
	 * @param string $field_name - name of the field.
	 * @return void
	 */
	public function remove_field( $field_name ) {
		if ( isset( $this->fields[ $field_name ] ) ) {
			unset( $this->fields[ $field_name ] );
		}
	}

	/**
	 * Returns selected field element if exists.
	 *
	 * @param string $field_name - name of the field.
	 * @return Quickcrator_Form_Element|bool
	 */
	public function get_field( $field_name ) {
		if ( isset( $this->fields[ $field_name ] ) ) {
			return $this->fields[ $field_name ];
		}
		return false;
	}

	/**
	 * Returns all fields or empty array.
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Adds provided values to forms elements.
	 *
	 * @param array $values - array of values to bind into the fields.
	 * @return void
	 */
	public function bind( $values = array() ) {
		if ( is_array( $values ) && count( $values ) > 0 ) {
			foreach ( $this->get_fields() as $field ) {
				if ( 'checkbox' === $field->get_type() ) {
					if ( isset( $values[ $field->get_name() ] ) ) {
						$field->set_value( $values[ $field->get_name() ] );
					} else {
						$field->set_value( false );
					}
				} elseif ( isset( $values[ $field->get_name() ] ) ) {
						$field->set_value( $values[ $field->get_name() ] );
				}
			}
		}
	}

	/**
	 * Validates all the fields in the form.
	 *
	 * @param array $data - data to validate for the fields.
	 * @return bool
	 */
	public function validate( $data ) {
		$valid = true;

		foreach ( $this->fields as $field ) {
			if ( isset( $data[ $field->get_name() ] ) ) {
				$field_validation = $field->validate( $data[ $field->get_name() ] );
				if ( ! $field_validation ) {
					$valid           = false;
					$this->has_error = true;
				}
			}
		}

		return $valid;
	}

	/**
	 * Returns form name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Returns form method.
	 *
	 * @return string
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * Returns form CSS classes
	 *
	 * @return string
	 */
	public function get_classes() {
		return $this->classes;
	}

	/**
	 * Returns has_error param.
	 *
	 * @return bool
	 */
	public function get_error() {
		return $this->has_error;
	}

	/**
	 * Renders form for wp-admin purpose.
	 *
	 * @return void
	 */
	public function render_admin_form() {
		ob_start();
		?>

		<?php foreach ( $this->get_fields() as $field ) : ?>
			<?php if ( 'hidden' === $field->get_type() ) : ?>
				<?php $field->render(); ?>
			<?php endif; ?>
		<?php endforeach; ?>
			<div class="quickcreator-layout quickcreator-admin-config-form <?php echo ( Quickcreator()->get_quickcreator()->is_quickcreator_connected() ) ? '' : 'before-connect'; ?>">
				<?php foreach ( $this->get_fields() as $field ) : ?>
					<?php if ( 'hidden' === $field->get_type() ) : ?>
						<?php continue; ?>
					<?php endif; ?>

					<div class="quickcreator-admin-config-form__single-field-row <?php echo esc_html( $field->get_row_classes() ); ?>">
						<?php if ( $field->has_renderer() ) : ?>
							<div class="quickcreator-admin-config-form__single-field-row--custom-renderer">
								<?php $field->render(); ?>
							</div>
						<?php else : ?>
							<?php if ( 'header' === $field->get_type() ) : ?>
								<h3 id="<?php echo esc_html( $field->get_name() ); ?>">
									<?php echo esc_html( $field->get_label() ); ?>
								</h3>
								<?php if ( $field->get_hint() ) : ?>
									<span class="quickcreator-admin-config-form__header_description"><?php echo esc_html($field->get_hint()); ?></span>
								<?php endif; ?>
							<?php else : ?>

								<label for="<?php echo esc_html( $field->get_name() ); ?>">
									<?php echo esc_html( $field->get_label() ); ?>
									<?php if ( $field->get_is_required() ) : ?>
										<span style="color: red;">*</span>
									<?php endif; ?>
								</label>

								<div class="quickcreator_admin_config_form__single_field">
									<?php $field->render(); ?>
									<?php if ( $field->get_hint() ) : ?>
										<br/><small><?php echo esc_html($field->get_hint()) ?></small>
									<?php endif; ?>
									<?php if ( count( $field->get_errors() ) > 0 ) : ?>
										<?php foreach ( $field->get_errors() as $error ) : ?>
										<br /><span class="quickcreator-error"><?php echo esc_html( $error ); ?></span>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php
		$html = ob_get_clean();

		echo wp_kses( $html, $this->return_allowed_html_for_forms() );
	}

	/**
	 * Returns array of allowed HTML for form element rendering
	 *
	 * @return array
	 */
	protected function return_allowed_html_for_forms() {
		$allowed_html = array(
			'input'    => array(
				'id'       => array(),
				'name'     => array(),
				'class'    => array(),
				'type'     => array(),
				'value'    => array(),
				'checked'  => array(),
				'selected' => array(),
			),
			'select'   => array(
				'id'    => array(),
				'name'  => array(),
				'class' => array(),
			),
			'option'   => array(
				'value'    => array(),
				'selected' => array(),
			),
			'textarea' => array(
				'id'    => array(),
				'name'  => array(),
				'class' => array(),
			),
			'a'        => array(
				'href'   => array(),
				'id'     => array(),
				'class'  => array(),
				'target' => array(),
				'rel'    => array(),
			),
			'small'    => array(),
			'br'       => array(),
			'label'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
				'for'   => array(),
			),
			'span'     => array(
				'id'    => array(),
				'class' => array(),
				'style' => array(),
			),
			'table'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'tbody'    => array(),
			'tr'       => array(
				'class'  => array(),
				'id'     => array(),
				'valign' => array(),
				'style'  => array(),
			),
			'th'       => array(
				'class'   => array(),
				'id'      => array(),
				'scope'   => array(),
				'colspan' => array(),
				'style'   => array(),
			),
			'td'       => array(
				'class'   => array(),
				'id'      => array(),
				'colspan' => array(),
				'style'   => array(),
			),
			'h3'       => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'button'   => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'div'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'p'        => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'img'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
				'alt'   => array(),
				'src'   => array(),
			),
			'svg'      => array(
				'width'   => array(),
				'height'  => array(),
				'fill'    => array(),
				'xmlns'   => array(),
				'viewBox' => array(),
			),
			'path'     => array(
				'fill-rule' => array(),
				'clip-rule' => array(),
				'd'         => array(),
			),
		);

		return $allowed_html;
	}
}
