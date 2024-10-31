<?php

/**
 * Template for general Quickcreator plugin settings.
 *
 * @package QuickcreatorBlog.
 */

use QuickcreatorBlog\Quickcreatorblog;

if (! defined('ABSPATH')) exit; // Exit if accessed directly
?>

<div class="wrap quickcreator-layout">
	<h1><?php esc_html_e('Quickcreator: Settings', 'quickcreator'); ?></h1>

	<?php if (isset($error) && true === $error) : ?>
		<div class="notice error quickcreator-error is-dismissible">
			<p><?php esc_html_e('There is an error in your form.', 'quickcreator'); ?></p>
		</div>
	<?php endif; ?>

	<?php if (isset($success) && true === $success) : ?>
		<div class="notice updated quickcreator-success is-dismissible">
			<p><?php esc_html_e('Form saved properly.', 'quickcreator'); ?></p>
		</div>
	<?php endif; ?>

	<form action="" method="POST">
		<div class="quickcreator-wraper">
			<div class="quickcreator-wraper__logo">
				<img src="<?php echo esc_url(Quickcreator()->get_baseurl() . 'assets/images/quickcreator_logo.svg'); ?>" alt="Quickcreator Logo" />
			</div>
			<div class="quickcreator-wraper__content">

				<?php wp_nonce_field('quickcreator_settings_save', '_quickcreator_nonce'); ?>

				<?php if (isset($form)) : ?>
					<?php $form->render_admin_form(); ?>
				<?php endif; ?>

				<div class="quickcreator-admin-footer">
					<div class="quickcreator-debug-box quickcreator-connected">
						<h3><?php esc_html_e('Debugging', 'quickcreator'); ?></h3>
						<p>
							<?php esc_html_e('In case you have any troubles with the plugin, please click the button below to download a .txt file with debug information, and send it to our Support team. This will speed up the debug process. Thank you.', 'quickcreator'); ?>
						</p>
						<a class="quickcreator-button quickcreator-button--secondary quickcreator-button--small" target="_blank" href="<?php echo esc_html(admin_url('admin.php?page=quickcreator&action=download_debug_data')); ?>">
							<?php esc_html_e('Download debug data', 'quickcreator'); ?>
						</a>
					</div>
					<?php /* translators: %1$s & %2$s is replaced with "url" */ ?>
					<?php printf(wp_kses(__('In case of questions or troubles, please check our <a href="%1$s" target="_blank">documentation</a> or contact our <a href="%2$s" target="_blank">support team.</a>', 'quickcreator'), wp_kses_allowed_html('post')), esc_html(quickcreatorblog::get_instance()->url_wpquickcreator_docs), esc_html('mailto:support@quickcreator.io')); ?>
				</div>
			</div>
		</div>
	</form>
</div>