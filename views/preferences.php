<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <h3><?php esc_html_e('Settings', 'cryptopay-integration-for-mycred'); ?></h3>
        <div class="form-group">
            <label for="<?php echo esc_attr($this->field_id('theme')); ?>"><?php esc_html_e('Theme', 'cryptopay-integration-for-mycred'); ?></label>
            <select name="<?php echo esc_attr($this->field_name('theme')); ?>" id="<?php echo esc_attr($this->field_id('theme')); ?>" class="form-control">
                <option value="light" <?php selected($this->prefs['theme'], 'light'); ?>><?php esc_html_e('Light', 'cryptopay-integration-for-mycred'); ?></option>
                <option value="dark" <?php selected($this->prefs['theme'], 'dark'); ?>><?php esc_html_e('Dark', 'cryptopay-integration-for-mycred'); ?></option>
            </select>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <h3><?php esc_html_e('Setup', 'cryptopay-integration-for-mycred'); ?></h3>
        <div class="form-group">
            <label for="<?php echo esc_attr($this->field_id('currency')); ?>"><?php esc_html_e('Currency', 'cryptopay-integration-for-mycred'); ?></label>

            <?php $this->currencies_dropdown('currency', 'mycred-gateway-cryptopay-currency'); ?>

        </div>
        <div class="form-group">
            <label><?php esc_html_e('Exchange Rates', 'cryptopay-integration-for-mycred'); ?></label>

            <?php $this->exchange_rate_setup(); ?>

        </div>
    </div>
</div>