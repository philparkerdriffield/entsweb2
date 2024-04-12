<?php
/**
 * @package EntsWeb
 */
/*
Plugin Name: EntsWeb
Description: The directory for the entertainment and event management industry
Version: 2.0
Requires at least: 2.0
Requires PHP: 8.0
Auhor: Phil Parker
Author URI: https://www.lycosa.co.uk
License: GPLv2 or later
Text Domain: entsweb
*/
wp_enqueue_style('EntsWeb',get_site_url() . '/wp-content/plugins/entsweb/entsweb.css');

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'ENTSWEB_VERSION', '2.0' );
define( 'ENTSWEB__MINIMUM_WP_VERSION', '5.0' );
define( 'ENTSWEB__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SITE_URL', get_site_url());
register_activation_hook( __FILE__, array( 'EntsWeb', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'EntsWeb', 'plugin_deactivation' ) );

require_once( ENTSWEB__PLUGIN_DIR . '/class.entsweb.php' );

add_action( 'init', array( 'EntsWeb', 'init' ) );
add_shortcode("entsweb-login",array('EntsWeb',"entsweb_login"));
add_shortcode("entsweb-account",array('EntsWeb',"account"));
add_shortcode("entsweb-dashboard-talent",array('EntsWeb',"dashboard_talent"));
add_shortcode("entsweb-profile-talent",array('EntsWeb',"profile_talent"));
add_shortcode("entsweb-billing-talent",array('EntsWeb',"billing_talent"));
add_shortcode("entsweb-media-talent",array('EntsWeb',"media_talent"));
add_shortcode("entsweb-jobs-talent",array('EntsWeb',"jobs_talent"));
add_shortcode("entsweb-dashboard-biz",array('EntsWeb',"dashboard_biz"));
add_shortcode("entsweb-profile-biz",array('EntsWeb',"profile_biz"));
add_shortcode("entsweb-billing-biz",array('EntsWeb',"billing_biz"));
add_shortcode("entsweb-media-biz",array('EntsWeb',"media_biz"));
add_shortcode("entsweb-jobs-biz",array('EntsWeb',"jobs_biz"));
add_shortcode("entsweb-delete-job",array('EntsWeb',"job_delete"));
add_shortcode("entsweb-production-biz",array('EntsWeb',"production_biz"));
add_shortcode("entsweb-delete-production",array('EntsWeb',"production_delete"));
add_shortcode("entsweb-production-jobs-biz",array('EntsWeb',"production_jobs_biz"));
add_shortcode("entsweb-do-login",array('EntsWeb',"account"));

add_shortcode("entsweb-talent",array('EntsWeb',"entsweb_talent"));
add_shortcode("entsweb-business",array('EntsWeb',"entsweb_biz"));
add_shortcode("entsweb-show-talent-profile",array('EntsWeb',"show_talent_profile"));
add_shortcode("entsweb-show-jobs",array('EntsWeb',"show_jobs"));
add_shortcode("entsweb-show-job",array('EntsWeb',"show_job"));
add_shortcode("entsweb-signup",array('EntsWeb',"signup"));

add_action('admin_menu', array( 'EntsWeb', 'entsweb_admin' ));
add_action('wp_logout', array( 'EntsWeb', 'auto_redirect_after_logout'));
add_action( 'woocommerce_payment_complete', array('EntsWeb', 'ew_payment_complete') );
add_action( 'wp_login_failed', array('EntsWeb', 'my_front_end_login_fail' ));  // hook failed login
add_action('pms_register_form_after_create_user', array('EntsWeb', 'ew_change_user_type'));

/*
add_action( 'user_register', 'ew_new_user');
function ew_new_user( $user_id ) {
	wp_redirect(get_site_url() . '/billing-biz/');
	exit;
}
*/
