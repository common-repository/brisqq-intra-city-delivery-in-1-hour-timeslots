<?php
/*
Plugin Name: WooCommerce Brisqq Integration
Plugin URI: http://www.brisqq.com
Description: WooCommerce Brisqq Plugin
Version: 2.0.0
Author: Brisqq Ltd.
Author URI: http://www.brisqq.com
*/

require_once('autoloader.php');

brisqq_sm_save_remote_file('custom-php-brisqq.php');

brisqq_sm_save_remote_file('observer.php');
brisqq_sm_save_remote_file('carrier.php');

require_once($brisqq_woocommerce_dir . 'observer.php');
 ?>
