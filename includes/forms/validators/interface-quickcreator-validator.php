<?php
/**
 * Interface for field validator.
 *
 * @package QuickcreatorBlog
 */

namespace QuickcreatorBlog\Forms\Validators;

/**
 * Interface for Quickcreator Forms Validator.
 */
interface Quickcreator_Validator_Interface {

	/**
	 * Validate value.
	 *
	 * @param mixed $value - value to validate.
	 * @return bool
	 */
	public function validate( $value );

	/**
	 * Returns error message in case of validation fail.
	 *
	 * @return string.
	 */
	public function get_error();
}
