<?php
/*
 * EntsWeb 2

talent pay £4.99/m to gain access to job details
Business directory FOC
Job posting FOC

Custom Post Types:
talent
Business
Jobs

User Admin:
shortcode to display form
display purchase history with invoices

Super Admin:
List users, status
Edit user, cancel account, update renewal date

Sign up:
Select account type: talen, employer, business

talent:
Name, DOB, gender, categories, photos, biog, tags, phone, email, social links, website, location
Work history: Date, venue, company, gig details
News
Categories (filter): actor, singer, dancer, solo artist, musician, musical group, entertainer

Business:
Category, Name, Address, Year started, Type of business, logo image, biog, photos, phone, email, social links, website
People: Name, role, phone, email, photo

Jobs:
Public: Title, reference, description, location
Members: Company, contact details


 */

class EntsWeb {
	private static $initiated = false;

	public static $header = "";

	// plugin functions
	public static function plugin_activation() {
		if ( version_compare( $GLOBALS['wp_version'], ENTSWEB__MINIMUM_WP_VERSION, '<' ) ) {
			load_plugin_textdomain( 'entsweb' );

			$message = '<strong>'.sprintf(esc_html__( 'EntsWeb %s requires WordPress %s or higher.' , 'entsweb'), ENTSWEB_VERSION, ENTSWEB__MINIMUM_WP_VERSION ).'</strong> '.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version, or <a href="%2$s">downgrade to version 2.4 of the EntsWeb plugin</a>.', 'akismet'), 'https://codex.wordpress.org/Upgrading_WordPress', 'https://wordpress.org/extend/plugins/entsweb/download/');

			EntsWeb::bail_on_activation( $message );
		} elseif ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php' ) ) {
			add_option( 'Activated_EntsWeb', true );
			self::build_custom_post_types();
		}

		/*
		 * Create all the custom post types
		 */
	}
	public static function plugin_deactivation() {}
	public static function init() {
		self::init_hooks();
		//if ( self::$initiated ) return;

		// create user types
		add_role( 'talent' , 'Talent', array() );
		add_role( 'biz' , 'Biz', array() );
	}
	private static function init_hooks() {
		self::$initiated = true;
		add_action( 'wp_login_failed', array('EntsWeb', 'entsweb_login_fail' ), 10, 2);

		//add_action( 'wp_insert_comment', array( 'EntsWeb', 'auto_check_update_meta' ), 10, 2 );
		//add_filter( 'preprocess_comment', array( 'EntsWeb', 'main_function' ), 1 );

	}
	public static function build_custom_post_types() {
		register_post_type( 'talent',
			array(
				'labels' => array(
					'name' => __( 'talent' ),
					'singular_name' => __( 'talent' )
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'talent'),
				'show_in_rest' => true,

			)
		);
		register_post_type( 'business',
			array(
				'labels' => array(
					'name' => __( 'business' ),
					'singular_name' => __( 'Business' )
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'business'),
				'show_in_rest' => true,

			)
		);
		register_post_type( 'productions',
			array(
				'labels' => array(
					'name' => __( 'Productions' ),
					'singular_name' => __( 'Production' )
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'productions'),
				'show_in_rest' => true,

			)
		);
		register_post_type( 'jobs',
			array(
				'labels' => array(
					'name' => __( 'Jobs' ),
					'singular_name' => __( 'Job' )
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'jobs'),
				'show_in_rest' => true,

			)
		);
	}

	// front end functions
	public static function entsweb_talent() {
	   $args = ['post_type' => 'talent',
			'meta_query' => [
                'relation' => 'AND',
				[
					'key'   => 'profile_talent_hide',
					'value' => '0'
				]
			]
		];
       $none_select = $african_select = $asian_select = $hispanic_select = $indian_select = $white_select = "";
       $no_gender_select = $male_select = $female_select = $other_select = "";
       $talent_age = $name = "";
       $any_age =  $selected_18 =  $selected_20 =  $selected_30 =  $selected_40 =  $selected_50 =  $selected_60 =  $selected_70 =  $selected_80 = "";

		if ($_POST['action']=='talent_search') {
			if ( isset( $_POST['talent_search_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['talent_search_nonce'],
					'talent_search' )) {
					if (isset($_POST['talent_talent'])) {
						switch (sanitize_text_field( $_POST['talent_talent'] )) {
							case '':
								$all_talent = "selected";
								break;
							case 'Actor':
								$actor_select = "selected";
								$args['meta_query'][] = ['key' => 'profile_talent_actor', 'value' => '1' ];
								break;
							case 'Dancer':
								$dancer_select = "selected";
								$args['meta_query'][] = ['key' => 'profile_talent_dancer', 'value' => '1' ];
								break;
							case 'Singer':
								$singer_select = "selected";
								$args['meta_query'][] = ['key' => 'profile_talent_singer', 'value' => '1' ];
								break;
							case 'Musician':
								$musician_select = "selected";
								$args['meta_query'][] = ['key' => 'profile_talent_musician', 'value' => '1' ];
								break;
							case 'Entertainer':
								$entertainer_select = "selected";
								$args['meta_query'][] = ['key' => 'profile_talent_entertainer', 'value' => '1' ];
								break;
							case 'Crew':
								$crew_select = "selected";
								$args['meta_query'][] = ['key' => 'profile_talent_crew', 'value' => '1' ];
								break;
							default:
								$all_talent = "selected";
								break;
						}
					}
                    if (isset($_POST['talent_name'])) {
                        $name = sanitize_text_field( $_POST['talent_name'] );
                        if ("" != $name) {
	                        $args['meta_query'][] = [
				                        'key'   => 'profile_talent_full_name',
				                        'value' => $name,
				                        'compare'  => 'LIKE'
	                        ];
                        }
                    }
                    if (isset($_POST['talent_gender'])) {
                        switch (sanitize_text_field( $_POST['talent_gender'] )) {
                            case '':
                                $no_gender_select = "selected";
                                break;
                            case 'Male':
                                $male_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_gender', 'value' => 'Male' ];
                                break;
                            case 'Female':
                                $female_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_gender', 'value' => 'Female' ];
                                break;
                            case 'Other':
                                $other_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_gender', 'value' => 'Other' ];
                                break;
                            default:
                                $no_gender_select = "selected";
                                break;
                        }
				    }
                    if (isset($_POST['talent_ethnicity'])) {
                        switch (sanitize_text_field( $_POST['talent_ethnicity'] )) {
                            case '':
                                $none_select = "selected";
                                break;
                            case 'African':
                                $african_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_ethnicity', 'value' => 'African' ];
                                break;
                            case 'Asian':
                                $asian_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_ethnicity', 'value' => 'Asian' ];
                                break;
                            case 'Hispanic':
                                $hispanic_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_ethnicity', 'value' => 'Hispanic' ];
	                            break;
                            case 'Indian':
                                $indian_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_ethnicity', 'value' => 'Indian' ];
	                            break;
                            case 'White':
                                $white_select = "selected";
	                            $args['meta_query'][] = ['key' => 'profile_talent_ethnicity', 'value' => 'White' ];
                                break;
                            default:
                                $none_select = "selected";
                                break;
                        }
				    }
					if (isset($_POST['talent_age'])) {
						$talent_age = sanitize_text_field( $_POST['talent_age'] );
						switch ($talent_age) {
                            case '':
                                $any_age = "selected";
                                break;
                            case '18-19':
                                $selected_18 = "selected";
                                $args['meta_query'][] = [
                                    'key'   => 'profile_talent_age',
                                    'value' => '18,19',
                                    'compare'  => 'IN'
                                ];
                                break;
                            case '20-29':
                                $selected_20 = "selected";

	                            $args['meta_query'][] = [
		                            'key'   => 'profile_talent_age',
		                            'value' => '20,21,22,23,24,25,26,27,28,29',
		                            'compare'  => 'IN'
	                            ];
	                            break;
                            case '30-39':
                                $selected_30 = "selected";
	                            $args['meta_query'][] = [
		                            'key'   => 'profile_talent_age',
		                            'value' => '30,31,32,33,34,35,36,37,38,39',
		                            'compare'  => 'IN'
	                            ];
	                            break;
                            case '40-49':
                                $selected_40 = "selected";
	                            $args['meta_query'][] = [
		                            'key'   => 'profile_talent_age',
		                            'value' => '40,41,42,43,44,45,46,47,48,49',
		                            'compare'  => 'IN'
	                            ];
	                            break;
                            case '50-59':
                                $selected_50 = "selected";
	                            $args['meta_query'][] = [
		                            'key'   => 'profile_talent_age',
		                            'value' => '50,51,52,53,54,55,56,57,58,59',
		                            'compare'  => 'IN'
	                            ];
	                            break;
                            case '60-69':
                                $selected_60 = "selected";
	                            $args['meta_query'][] = [
		                            'key'   => 'profile_talent_age',
		                            'value' => '60,61,62,63,64,65,66,67,68,69',
		                            'compare'  => 'IN'
	                            ];
	                            break;
                            case '70-79':
                                $selected_70 = "selected";
	                            $args['meta_query'][] = [
		                            'key'   => 'profile_talent_age',
		                            'value' => '70,71,72,73,74,75,76,77,78,79',
		                            'compare'  => 'IN'
	                            ];
	                            break;
                            case '80+':
                                $selected_80 = "selected";
	                            $args['meta_query'][] = [
		                            'key'   => 'profile_talent_age',
		                            'value' => '80',
		                            'compare'  => '>='
	                            ];
	                            break;
                            default:
                                $any_age = "selected";
	                            break;
                        }
					}
                }
			}
		}
		$output = "<img width='200' src='" . get_site_url() . "/wp-content/uploads/2023/04/logo.png' alt='EntsWeb bringing talent and business together'>
<br><br><h1>Talent</h1>
If you are a business looking for actors, dancers, singers, musicians, entertainers, other performance artists or crew, then you're in the right place.<br>
Find all the talent you need on our brand new talent list, which has loads of talented people looking for work.<br><hr><br>";
		$output .= <<<END
<script type="text/javascript">
function talent_dropdown() {
    jQuery("#ew-t").toggle();
    jQuery("#ew-a").hide();
    jQuery("#ew-g").hide();
    jQuery("#ew-e").hide();
}
function age_dropdown() {
    jQuery("#ew-a").toggle();
    jQuery("#ew-t").hide();
    jQuery("#ew-g").hide();
    jQuery("#ew-e").hide();
}
function gender_dropdown() {
    jQuery("#ew-g").toggle();
    jQuery("#ew-t").hide();
    jQuery("#ew-a").hide();
    jQuery("#ew-e").hide();
}
function ethnicity_dropdown() {
    jQuery("#ew-e").toggle();
    jQuery("#ew-g").hide();
    jQuery("#ew-t").hide();
    jQuery("#ew-a").hide();
}
function talent_select(talent) {
    switch (talent) {
        case 1:
            jQuery('#talent_title').html('Talent');
            jQuery('#talent_talent').val('');
        break;
        case 2:
            jQuery('#talent_title').html('Actor');
            jQuery('#talent_talent').val('Actor');
        break;
        case 3:
            jQuery('#talent_title').html('Dancer');
            jQuery('#talent_talent').val('Dancer');
        break;
        case 4:
            jQuery('#talent_title').html('Singer');
            jQuery('#talent_talent').val('Singer');
        break;
        case 5:
            jQuery('#talent_title').html('Musician');
            jQuery('#talent_talent').val('Musician');
        break;
        case 6:
            jQuery('#talent_title').html('Entertainer');
            jQuery('#talent_talent').val('Entertainer');
        break;
        case 7:
            jQuery('#talent_title').html('Crew');
            jQuery('#talent_talent').val('Crew');
        break;
        default:
            jQuery('#talent_title').html('All');
            jQuery('#talent_talent').val('All');
        break;
    }
}
function age_select(age) {
    switch (age) {
        case 1:
            jQuery('#age_title').html('Age');
            jQuery('#talent_age').val('');
        break;
        case 2:
            jQuery('#age_title').html('18-19');
            jQuery('#talent_age').val('18-19');
        break;
        case 3:
            jQuery('#age_title').html('20-29');
            jQuery('#talent_age').val('20-29');
        break;
        case 4:
            jQuery('#age_title').html('30-39');
            jQuery('#talent_age').val('30-39');
        break;
        case 5:
            jQuery('#age_title').html('40-49');
            jQuery('#talent_age').val('40-49');
        break;
        case 6:
            jQuery('#age_title').html('50-59');
            jQuery('#talent_age').val('50-59');
        break;
        case 7:
            jQuery('#age_title').html('60-69');
            jQuery('#talent_age').val('60-69');
        break;
        case 8:
            jQuery('#age_title').html('70-79');
            jQuery('#talent_age').val('70-79');
        break;
        case 9:
            jQuery('#age_title').html('80+');
            jQuery('#talent_age').val('80+');
        break;
        }
}
function gender_select(gender) {
    switch (gender) {
        case 1:
            jQuery('#gender_title').html('Gender');
            jQuery('#talent_gender').val('');
        break;
        case 2:
            jQuery('#gender_title').html('Male');
            jQuery('#talent_gender').val('Male');
        break;
        case 3:
            jQuery('#gender_title').html('Female');
            jQuery('#talent_gender').val('Female');
        break;
        case 4:
            jQuery('#gender_title').html('Other');
            jQuery('#talent_gender').val('Other');
        break;
        }
}

function ethnicity_select(ethnicity) {
    switch (ethnicity) {
        case 1:
            jQuery('#ethnicity_title').html('Ethnicity');
            jQuery('#talent_ethnicity').val('');
        break;
        case 2:
            jQuery('#ethnicity_title').html('African');
            jQuery('#talent_ethnicity').val('African');
        break;
        case 3:
            jQuery('#ethnicity_title').html('Asian');
            jQuery('#talent_ethnicity').val('Asian');
        break;
        case 4:
            jQuery('#ethnicity_title').html('Hispanic');
            jQuery('#talent_ethnicity').val('Hispanic');
        break;
        case 5:
            jQuery('#ethnicity_title').html('Indian');
            jQuery('#talent_ethnicity').val('Indian');
        break;
        case 6:
            jQuery('#ethnicity_title').html('White');
            jQuery('#talent_ethnicity').val('White');
        break;
        }
}
</script>
END;
$talent_list = "<div class='ew-dropdown-box' id='ew-t'>
<div onclick='talent_select(1);'>Any</div>
<div onclick='talent_select(2);'>Actor</div>
<div onclick='talent_select(3);'>Dancer</div>
<div onclick='talent_select(4);'>Singer</div>
<div onclick='talent_select(5);'>Musician</div>
<div onclick='talent_select(6);'>Entertainer</div>
<div onclick='talent_select(7);'>Crew</div>
</div>";
$age_list = "<div class='ew-dropdown-box' id='ew-a'>
<div onclick='age_select(1);'>Any</div>
<div onclick='age_select(2);'>18-19</div>
<div onclick='age_select(3);'>20-29</div>
<div onclick='age_select(4);'>30-39</div>
<div onclick='age_select(5);'>40-49</div>
<div onclick='age_select(6);'>50-59</div>
<div onclick='age_select(7);'>60-69</div>
<div onclick='age_select(8);'>70-79</div>
<div onclick='age_select(9);'>80+</div>
</div>";
$gender_list = "<div class='ew-dropdown-box' id='ew-g'>
<div onclick='gender_select(1);'>Any</div>
<div onclick='gender_select(2);'>Male</div>
<div onclick='gender_select(3);'>Female</div>
<div onclick='gender_select(4);'>Other</div>
</div>";
$ethnicity_list = "<div class='ew-dropdown-box' id='ew-e'>
<div onclick='ethnicity_select(1);'>Any</div>
<div onclick='ethnicity_select(2);'>African</div>
<div onclick='ethnicity_select(3);'>Asian</div>
<div onclick='ethnicity_select(4);'>Hispanic</div>
<div onclick='ethnicity_select(5);'>Indian</div>
<div onclick='ethnicity_select(6);'>White</div>
</div>";
$output .= "<div class='ew-searchbox'><form action='' method='post' id='talent_search' enctype='multipart/form-data'>
<input type='hidden' name='action' value='talent_search'>" .
                 wp_nonce_field( 'talent_search', 'talent_search_nonce' ) . "
<input type='hidden' name='talent_talent' id='talent_talent' value='Any'>
<input type='hidden' name='talent_age' id='talent_age' value='Any'>
<input type='hidden' name='talent_gender' id='talent_gender' value='Any'>
<input type='hidden' name='talent_ethnicity' id='talent_ethnicity' value='Any'>
<table class='ew-talent-search'><tr><td><input type='text' name='talent_name' value='$name'></td></td>
<td onclick='talent_dropdown();' id='talent_select'><span id='talent_title'>Talent</span><span class='arrow down'></span>$talent_list</td>
<td onclick='age_dropdown();' id='age_select'><span id='age_title'>Age</span><span class='arrow down'></span>$age_list</td>
<td onclick='gender_dropdown();' id='gender_select'><span id='gender_title'>Gender</span><span class='arrow down'></span>$gender_list</td>
<td onclick='ethnicity_dropdown();' id='ethnicity_select'><span id='ethnicity_title'>Ethnicity</span><span class='arrow down'></span>$ethnicity_list</td>
<td></td><td></td><td class='ew-search-button'><input type='submit' value='Find Talent'></td>
</tr></table></form></div>";

		$talent_posts = new WP_Query($args);
		$post_url = get_site_url() . "/talent-profile";
		if ($talent_posts->have_posts()) {
           while ($talent_posts->have_posts()) {
               $talent_posts->the_post();
               $post_id = get_the_ID();
	           $custom = get_post_custom($post_id);
	           $profile_talent_first_name = isset($custom['profile_talent_first_name'][0]) ? esc_html($custom['profile_talent_first_name'][0]) : "";
	           $profile_talent_last_name = isset($custom['profile_talent_last_name'][0]) ? esc_html($custom['profile_talent_last_name'][0]) : "";
	           $profile_talent_gender = isset($custom['profile_talent_gender'][0]) ? esc_html($custom['profile_talent_gender'][0]) : "";
	           $profile_talent_age = isset($custom['profile_talent_age'][0]) ? esc_html($custom['profile_talent_age'][0]) : "";
	           $profile_talent_strapline = isset($custom['profile_talent_strapline'][0]) ? esc_html($custom['profile_talent_strapline'][0]) : "";
	           $profile_talent_ethnicity = isset($custom['profile_talent_ethnicity'][0]) ? esc_html($custom['profile_talent_ethnicity'][0]) : "";
	           $profile_talent_colour = isset($custom['profile_talent_colour'][0]) ? esc_html($custom['profile_talent_colour'][0]) : "";
	           $avatar_id = isset($custom['profile_talent_avatar'][0]) ? esc_html($custom['profile_talent_avatar'][0]) : "";
	           $actor = isset($custom['profile_talent_actor'][0]) ? esc_html($custom['profile_talent_actor'][0]) : 0;
	           $dancer = isset($custom['profile_talent_dancer'][0]) ? esc_html($custom['profile_talent_dancer'][0]) : 0;
	           $singer = isset($custom['profile_talent_singer'][0]) ? esc_html($custom['profile_talent_singer'][0]) : 0;
	           $musician = isset($custom['profile_talent_musician'][0]) ? esc_html($custom['profile_talent_musician'][0]) : 0;
	           $entertainer = isset($custom['profile_talent_entertainer'][0]) ? esc_html($custom['profile_talent_entertainer'][0]) : 0;
	           $crew = isset($custom['profile_talent_crew'][0]) ? esc_html($custom['profile_talent_crew'][0]) : 0;
	           if ("" == $avatar_id) $avatar = "";
	           else $avatar = wp_get_attachment_image_url( $avatar_id, 'thumbnail');

	           $output .= self::format_page('archive',
                        array(  'post_url'      => $post_url,
                                'profileid'     => $post_id,
                                'name'          => $profile_talent_first_name . " " . $profile_talent_last_name,
                                'gender'        => $profile_talent_gender,
                                'age'           => $profile_talent_age,
                                'strapline'     => $profile_talent_strapline,
                                'ethnicity'     => $profile_talent_ethnicity,
                                'colour'        => $profile_talent_colour,
                                'actor'         => $actor,
                                'dancer'        => $dancer,
                                'singer'        => $singer,
                                'musician'      => $musician,
                                'crew'          => $crew,
                                'entertainer'   => $entertainer,
                                'image'         => $avatar ));
           }
       }
       else $output .= "Sorry, no results found.";

		return $output;
    }
	public static function show_talent_profile() {
		if (isset($_GET['profileid'])) $pid = $_GET['profileid'];
		$custom = get_post_custom($pid);
		$profile_talent_first_name = isset($custom['profile_talent_first_name'][0]) ? esc_html($custom['profile_talent_first_name'][0]) : "";
		$profile_talent_last_name = isset($custom['profile_talent_last_name'][0]) ? esc_html($custom['profile_talent_last_name'][0]) : "";
		$profile_talent_gender = isset($custom['profile_talent_gender'][0]) ? esc_html($custom['profile_talent_gender'][0]) : "";
		$profile_talent_age = isset($custom['profile_talent_age'][0]) ? esc_html($custom['profile_talent_age'][0]) : "";
		$profile_talent_strapline = isset($custom['profile_talent_strapline'][0]) ? esc_html($custom['profile_talent_strapline'][0]) : "";
		$profile_talent_ethnicity = isset($custom['profile_talent_ethnicity'][0]) ? esc_html($custom['profile_talent_ethnicity'][0]) : "";
		$profile_talent_colour = isset($custom['profile_talent_colour'][0]) ? esc_html($custom['profile_talent_colour'][0]) : "";
		$avatar_id = isset($custom['profile_talent_avatar'][0]) ? esc_html($custom['profile_talent_avatar'][0]) : "";
		$actor = isset($custom['profile_talent_actor'][0]) ? esc_html($custom['profile_talent_actor'][0]) : 0;
		$dancer = isset($custom['profile_talent_dancer'][0]) ? esc_html($custom['profile_talent_dancer'][0]) : 0;
		$singer = isset($custom['profile_talent_singer'][0]) ? esc_html($custom['profile_talent_singer'][0]) : 0;
		$musician = isset($custom['profile_talent_musician'][0]) ? esc_html($custom['profile_talent_musician'][0]) : 0;
		$entertainer = isset($custom['profile_talent_entertainer'][0]) ? esc_html($custom['profile_talent_entertainer'][0]) : 0;
		$crew = isset($custom['profile_talent_crew'][0]) ? esc_html($custom['profile_talent_crew'][0]) : 0;
		$projects = unserialize($custom['profile_talent_projects'][0]);

		if ("" == $avatar_id) $avatar = "";
		else $avatar = wp_get_attachment_image( $avatar_id, 'medium');
		$args = array(  'post_url'      => $site_url . $post_id,
		                'profileid'     => $post_id,
		                'name'          => $profile_talent_first_name . " " . $profile_talent_last_name,
		                'gender'        => $profile_talent_gender,
		                'age'           => $profile_talent_age,
		                'strapline'     => $profile_talent_strapline,
		                'ethnicity'     => $profile_talent_ethnicity,
		                'colour'        => $profile_talent_colour,
		                'actor'         => $actor,
		                'dancer'        => $dancer,
		                'singer'        => $singer,
		                'musician'      => $musician,
		                'crew'          => $crew,
		                'entertainer'   => $entertainer,
		                'avatar'        => $avatar,
                        'projects'      => $projects);
		$output = self::format_page('single', $args);
		return $output;
	}
	public static function entsweb_biz() {
		$args = ['post_type' => 'biz',
		         'meta_query' => [
			         'relation' => 'AND',
			         [
				         'key'   => 'profile_biz_hide',
				         'value' => '0'
			         ]
		         ]
		];
		$biz_type = "Type";
		$biz_name = "";

		if ($_POST['action']=='biz_search') {
			if ( isset( $_POST['biz_search_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['biz_search_nonce'], 'biz_search' )) {
					if (isset($_POST['biz_type'])) {
						switch (sanitize_text_field( $_POST['biz_type'] )) {
							case '':
								$biz_selected = "Any";
								$biz_type = "Type";
								break;
							case 'Agent':
								$biz_selected = "Agent";
								$biz_type = "Agent";
								$args['meta_query'][] = ['key' => 'profile_biz_type', 'value' => 'Agent' ];
								break;
							case 'Production':
								$biz_selected = "Production";
								$biz_type = "Production";
								$args['meta_query'][] = ['key' => 'profile_biz_type', 'value' => 'Production' ];
								break;
							case 'Other':
								$biz_selected = "Other";
								$biz_type = "Other";
								$args['meta_query'][] = ['key' => 'profile_biz_type', 'value' => 'Other' ];
								break;
							default:
								$biz_selected = "Any";
								$biz_type = "Type";
								break;
						}
					}
					if (isset($_POST['biz_name'])) {
						$biz_name = sanitize_text_field( $_POST['biz_name'] );
						if ("" != $biz_name) {
							$args['meta_query'][] = [
								'key'   => 'profile_biz_name',
								'value' => $biz_name,
								'compare'  => 'LIKE'
							];
						}
					}
				}
			}
		}
		$output = "<img width='200' src='" . get_site_url() . "/wp-content/uploads/2023/04/logo.png' alt='EntsWeb bringing talent and business together'>
<br><br><h1>Business</h1>
If you're looking for a business in the entertainment industry, you're in the right place<br>
Find production companies, agencies and a wide variety of associated businesses.<hr><br>";
		$output .= <<<END
<script type="text/javascript">
function biz_type_dropdown() {
    jQuery("#ew-t").toggle();
}
function biz_select(biz) {
    switch (biz) {
        case 1:
            jQuery('#biz_title').html('Type');
            jQuery('#biz_type').val('Type');
        break;
        case 2:
            jQuery('#biz_title').html('Agent');
            jQuery('#biz_type').val('Agent');
        break;
        case 3:
            jQuery('#biz_title').html('Production');
            jQuery('#biz_type').val('Production');
        break;
        case 4:
            jQuery('#biz_title').html('Other');
            jQuery('#biz_type').val('Other');
        break;
    }
}
</script>
END;
		$biz_list = "<div class='ew-dropdown-box' id='ew-t'>
<div onclick='biz_select(1);'>Any</div>
<div onclick='biz_select(2);'>Agent</div>
<div onclick='biz_select(3);'>Production</div>
<div onclick='biz_select(4);'>Other</div>
</div>";

		$output .= "<div class='ew-searchbox'><form action='' method='post' id='biz_search' enctype='multipart/form-data'>
<input type='hidden' name='action' value='biz_search'>" .
		           wp_nonce_field( 'biz_search', 'biz_search_nonce' ) . "
<input type='hidden' name='biz_type' id='biz_type' value='$biz_selected'>
<table class='ew-talent-search'><tr><td><input type='text' name='biz_name' value='$biz_name'></td></td>
<td onclick='biz_type_dropdown();' id='biz_select'><span id='biz_title'>$biz_type</span><span class='arrow down'></span>$biz_list</td>
<td></td><td></td><td class='ew-search-button'><input type='submit' value='Find Businesses'></td>
</tr></table></form></div>";

		$talent_posts = new WP_Query($args);
		$site_url = get_site_url() . "/talent-profile?profileid=";
		if ($talent_posts->have_posts()) {
			while ($talent_posts->have_posts()) {
				$talent_posts->the_post();
				$post_id = get_the_ID();
				$custom = get_post_custom($post_id);
				$profile_biz_name = isset($custom['profile_biz_name'][0]) ? esc_html($custom['profile_biz_name'][0]) : "";
				$profile_biz_address_1 = isset($custom['profile_biz_address_1'][0]) ? esc_html($custom['profile_biz_address_1'][0]) : "";
				$profile_biz_address_2 = isset($custom['profile_biz_address_2'][0]) ? esc_html($custom['profile_biz_address_2'][0]) : "";
				$profile_biz_address_3 = isset($custom['profile_biz_address_3'][0]) ? esc_html($custom['profile_biz_address_3'][0]) : "";
				$profile_biz_city = isset($custom['profile_biz_city'][0]) ? esc_html($custom['profile_biz_city'][0]) : "";
				$profile_biz_county = isset($custom['profile_biz_county'][0]) ? esc_html($custom['profile_biz_county'][0]) : "";
				$profile_biz_postcode = isset($custom['profile_biz_postcode'][0]) ? esc_html($custom['profile_biz_postcode'][0]) : "";
				$profile_biz_country = isset($custom['profile_biz_country'][0]) ? esc_html($custom['profile_biz_country'][0]) : "";
				$profile_biz_phone = isset($custom['profile_biz_phone'][0]) ? esc_html($custom['profile_biz_phone'][0]) : "";
				$profile_biz_mobile = isset($custom['profile_biz_mobile'][0]) ? esc_html($custom['profile_biz_mobile'][0]) : "";
				$profile_biz_email = isset($custom['profile_biz_email'][0]) ? esc_html($custom['profile_biz_email'][0]) : "";
				$profile_biz_strapline = isset($custom['profile_biz_strapline'][0]) ? esc_html($custom['profile_biz_strapline'][0]) : "";
				$profile_biz_description = isset($custom['profile_biz_description'][0]) ? esc_html($custom['profile_biz_description'][0]) : "";
				$profile_biz_speciality = isset($custom['profile_biz_speciality'][0]) ? esc_html($custom['profile_biz_speciality'][0]) : "";
				$profile_biz_tags = isset($custom['profile_biz_tags'][0]) ? esc_html($custom['profile_biz_tags'][0]) : "";
				if ("" == $avatar_id) $avatar = "";
				else $avatar = wp_get_attachment_image_url( $avatar_id, 'thumbnail');

				$output .= self::format_page('archive',
					array(  'post_url'      => $site_url . $post_id,
					        'name'          => $profile_biz_name,
					        'address_1'     => $profile_biz_address_1,
					        'address_2'     => $profile_biz_address_2,
					        'address_3'     => $profile_biz_address_3,
					        'city'          => $profile_biz_city,
					        'county'        => $profile_biz_county,
					        'postcode'      => $profile_biz_postcode,
					        'country'       => $profile_biz_country,
					        'phone'         => $profile_biz_phone,
					        'mobile'        => $profile_biz_mobile,
					        'email'         => $profile_biz_email,
					        'strapline'     => $profile_biz_strapline,
					        'description'   => $profile_biz_description,
					        'speciality'    => $profile_biz_speciality,
					        'tags'          => $profile_biz_tags,
					        'image'         => $avatar ));
			}
		}
		else $output .= "Sorry, no results found.";

		return $output;
	}
	public static function show_jobs() {
	    $search = [];
		if ($_POST['action']=='job_search') {
			if ( isset( $_POST['job_search_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['job_search_nonce'],
					'job_search' )) {
				    if (isset($_POST['job_search_terms']) && "" != $_POST['job_search_terms']) {
				        $search_terms = sanitize_text_field($_POST['job_search_terms']);
				        $terms_array = explode(" ", $search_terms);
						$args = [
							'post_type'  => 'jobs',
							'meta_query' => [
								'relation' => 'AND',
								[
									'key'   => 'job_hide',
									'value' => '0'
								],
                                [
                                    'relation' => 'OR',
                                    [
                                        'key'       => 'job_title',
                                        'value'     => $search_terms,
                                        'compare'   => 'LIKE'
                                    ],
                                    [
	                                    'key'       => 'job_tags',
	                                    'value'     => $terms_array,
	                                    'compare'   => 'IN'
                                    ]
                                ]
							]
						];
					}
				    else {
					    $args = [
						    'post_type'  => 'jobs',
						    'meta_query' => [
							    'relation' => 'AND',
							    [
								    'key'   => 'job_hide',
								    'value' => '0'
							    ]
						    ]
					    ];
				    }
				}

			}
		}
		else {
			$args = [
				'post_type'  => 'jobs',
				'meta_query' => [
					'relation' => 'AND',
					[
						'key'   => 'job_hide',
						'value' => '0'
					]
				]
			];
		}
		$output = "<img width='200' src='" . get_site_url() . "/wp-content/uploads/2023/04/logo.png' alt='EntsWeb bringing talent and business together'>
<br><br><h1>Entertainment jobs and gigs</h1>
If you are an actor, dancer, singer, musician, entertainer, other performance artist or crew looking for work, then you're in the right place.<br>
Find your next gig on our brand new jobs board, which has loads of gigs and jobs for talent and crew.<br><hr><br>";
       $output .= "<div class='ew-searchbox'><form action='' method='post' id='job_search' enctype='multipart/form-data'>
<input type='hidden' name='action' value='job_search'>" .
                 wp_nonce_field( 'job_search', 'job_search_nonce' ) . "
<table><tr><td><input type='text' name='job_search_terms'></td><td class='ew-search-button'><input type='submit' value='Find Jobs'></td></tr></table></form></div>";
		$output .= <<<END
<script type="text/javascript">
function show_job(job_id, job_url, job_title, production_title, business_name, job_location, job_brief, job_description, job_start_date, job_duration, job_pay) {
    console.log(job_url);
    html = "<h4>" + job_title + "</h4>";
    html += production_title + "<br>";
    html += business_name + "<br>";
    html += job_description + "<br>";
    html += job_location + "<br>";
    html += job_start_date + "<br>";
    html += job_duration + "<br>";
    html += job_pay + "<br>";
    html += "<form action='" + job_url + "' method='get'><input type='hidden' name='job_id' value='" + job_id + "'><input type='submit' value='Full Details'></form>";
    jQuery("#ew-job-sidebar").html(html);
}
</script>
END;


		$job_list = new WP_Query($args);
		$site_url = get_site_url() . "/show-job?job_id=";
		if ($job_list->have_posts()) {
		    $jobs_list = "";
			while ( $job_list->have_posts() ) {
				$job_list->the_post();
				$post_id = get_the_ID();
				$custom  = get_post_custom( $post_id );
				$job_title = esc_html($custom['job_title'][0]);
				$job_brief = esc_html($custom['job_brief'][0]);
				$job_location = esc_html($custom['job_location'][0]);
				$production_title = esc_html($custom['production_title'][0]);
				$production_id = esc_html($custom['production_id'][0]);
				$business_name = esc_html($custom['business_name'][0]);
				$business_id = esc_html($custom['business_id'][0]);

				if ("" == $jobs_list) $first_job = self::format_page( 'job_sidebar',
                    [  'post_url'          => $site_url . $post_id,
                       'site_url'          => $site_url,
                       'job_id'            => $post_id,
                       'job_title'         => $job_title,
                       'job_brief'         => $job_brief,
                       'job_location'      => $job_location,
                       'production_title'  => $production_title,
                       'production_id'     => $production_id,
                       'business_name'     => $business_name,
                       'business_id'       => $business_id
				    ]);

				$jobs_list .= self::format_page('job_archive',
					    [   'post_url'          => $site_url . $post_id,
					        'site_url'          => $site_url,
					        'job_id'            => $post_id,
					        'job_title'         => $job_title,
					        'job_brief'         => $job_brief,
					        'job_location'      => $job_location,
                            'production_title'  => $production_title,
                            'production_id'     => $production_id,
                            'business_name'     => $business_name,
                            'business_id'       => $business_id
                        ]
                );
			}
			$args = [   'jobs_list' => $jobs_list,
                        'first_job' => $first_job
                    ];
            $output .= self::format_page('jobs_initial', $args);
			//$output .= "<div class='ew-job-archive'><div class='ew-job-split'>$jobs_list</div><div class='ew-job-split'><div class='ew-job-sidebar' id='ew-job-sidebar'>side bar</div></div></div>";
		}
		else $output .= "Sorry, no results found.";
		return $output;
    }
	public static function show_job() {
		if (isset($_GET['job_id'])) $pid = $_GET['job_id'];
		$custom = get_post_custom($pid);
		$production_id = isset($custom['production_id'][0]) ? esc_html($custom['production_id'][0]) : 0;
		$business_id = isset($custom['business_id'][0]) ? esc_html($custom['business_id'][0]) : 0;
		$job_title = isset($custom['job_title'][0]) ? esc_html($custom['job_title'][0]) : "";
		$job_brief = isset($custom['job_brief'][0]) ? esc_html($custom['job_brief'][0]) : "";
		$job_description = isset($custom['job_description'][0]) ? esc_html($custom['job_description'][0]) : "";
		$job_start_date = isset($custom['job_start_date'][0]) ? esc_html($custom['job_start_date'][0]) : "";
		$job_duration = isset($custom['job_duration'][0]) ? esc_html($custom['job_duration'][0]) : "";
		$job_pay = isset($custom['job_pay'][0]) ? esc_html($custom['job_pay'][0]) : "";

		// get production details
        $custom  = get_post_custom($production_id);
		$production_title = isset($custom['production_title'][0]) ? esc_html($custom['production_title'][0]) : "";
		$production_description = isset($custom['production_description'][0]) ? esc_html($custom['production_description'][0]) : "";
		$production_date = isset($custom['production_date'][0]) ? esc_html($custom['production_date'][0]) : "";

        // get business details
        $custom = get_post_custom($business_id);
		$biz_name = isset($custom['profile_biz_name'][0]) ? esc_html($custom['profile_biz_name'][0]) : "";
		$biz_address_1 = isset($custom['profile_biz_address_1'][0]) ? esc_html($custom['profile_biz_address_1'][0]) : "";
		$biz_address_2 = isset($custom['profile_biz_address_3'][0]) ? esc_html($custom['profile_biz_address_2'][0]) : "";
		$biz_address_3 = isset($custom['profile_biz_address_1'][0]) ? esc_html($custom['profile_biz_address_3'][0]) : "";
		$biz_city = isset($custom['profile_biz_city'][0]) ? esc_html($custom['profile_biz_city'][0]) : "";
		$biz_county = isset($custom['profile_biz_county'][0]) ? esc_html($custom['profile_biz_county'][0]) : "";
		$biz_postcode = isset($custom['profile_biz_postcode'][0]) ? esc_html($custom['profile_biz_postcode'][0]) : "";
		$biz_phone = isset($custom['profile_biz_phone'][0]) ? esc_html($custom['profile_biz_phone'][0]) : "";
		$biz_mobile = isset($custom['profile_biz_mobile'][0]) ? esc_html($custom['profile_biz_mobile'][0]) : "";
		$biz_email = isset($custom['profile_biz_email'][0]) ? esc_html($custom['profile_biz_email'][0]) : "";
		$biz_description = isset($custom['profile_biz_description'][0]) ? esc_html($custom['profile_biz_description'][0]) : "";
		$biz_speciality = isset($custom['profile_biz_speciality'][0]) ? esc_html($custom['profile_biz_speciality'][0]) : "";

		// get job list
		$output = "<img width='200' src='" . get_site_url() . "/wp-content/uploads/2023/04/logo.png' alt='EntsWeb bringing talent and business together'>
<br><br><h1>Entertainment jobs and gigs</h1><hr><br>";
		$output .= <<<END
<script type="text/javascript">
function show_job2(job_id, job_url, job_title, job_location, job_brief, job_description, job_start_date, job_duration, job_pay) {
    html = "<h4>" + job_title + "</h4>";
    html += job_description + "<br>";
    html += job_location + "<br>";
    html += job_start_date + "<br>";
    html += job_duration + "<br>";
    html += job_pay + "<br>";
    jQuery("#ew-job-sidebar").html(html);
}
</script>
END;

		$args = [
			'post_type'  => 'jobs',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'   => 'job_hide',
					'value' => '0'
				],
                [
                    'key'   => 'production_id',
                    'value' => $production_id
                ]
			]
		];
		$job_list = new WP_Query($args);
		$site_url = get_site_url() . "/job?job_id=";
		$jobs_list = "";
		if ($job_list->have_posts()) {
			while ( $job_list->have_posts() ) {
				$job_list->the_post();
				$post_id = get_the_ID();
				$custom  = get_post_custom( $post_id );
				$list_job_title = esc_html($custom['job_title'][0]);
				$list_job_brief = esc_html($custom['job_brief'][0]);
				$list_job_description = esc_html($custom['job_description'][0]);
				$list_job_location = esc_html($custom['job_location'][0]);
				$list_job_start_date = esc_html($custom['job_start_date'][0]);
				$list_job_duration = esc_html($custom['job_duration'][0]);
				$list_job_pay = esc_html($custom['job_pay'][0]);
				$production_id = esc_html($custom['production_id'][0]);
				$business_name = esc_html($custom['business_name'][0]);
				$business_id = esc_html($custom['business_id'][0]);

				$jobs_list .= self::format_page('job_list',
					array(  'post_url'          => $site_url . $post_id,
					        'site_url'          => $site_url,
					        'job_id'            => $post_id,
					        'job_title'         => $list_job_title,
					        'job_brief'         => $list_job_brief,
					        'job_description'   => $list_job_description,
					        'job_location'      => $list_job_location,
					        'job_start_date'    => $list_job_start_date,
					        'job_duration'      => $list_job_duration,
					        'job_pay'           => $list_job_pay,
					        'production_title'  => $production_title,
					        'production_id'     => $production_id,
					        'business_name'     => $business_name,
					        'business_id'       => $business_id));
			}
		}


		$job_data = array(
		        'job_title'                 => $job_title,
                'job_brief'                 => $job_brief,
                'job_description'           => $job_description,
                'job_start_date'            => $job_start_date,
                'job_duration'              => $job_duration,
                'job_pay'                   => $job_pay,
                'production_title'          => $production_title,
                'production_description'    => $production_description,
                'production_date'           => $production_date,
                'biz_name'                  => $biz_name,
                'biz_address_1'             => $biz_address_1,
                'biz_address_2'             => $biz_address_2,
                'biz_address_3'             => $biz_address_3,
                'biz_city'                  => $biz_city,
                'biz_county'                => $biz_county,
                'biz_postcode'              => $biz_postcode,
                'biz_phone'                 => $biz_phone,
                'biz_mobile'                => $biz_mobile,
                'biz_email'                 => $biz_email,
                'biz_description'           => $biz_description,
                'biz_speciality'            => $biz_speciality,
                'jobs_list'                 => $jobs_list
            );
		$jobs_url = get_site_url();
		$output .= "<div class='ew-searchbox'><form action='$jobs_url' method='post' id='job_search' enctype='multipart/form-data'>
<input type='hidden' name='action' value='job_search'>" .
		          wp_nonce_field( 'job_search', 'job_search_nonce' ) . "
<table><tr><td><input type='text' name='job_search_terms'></td><td class='ew-search-button'><input type='submit' value='Find Jobs'></td></tr></table></form></div>";
		$output .= self::format_page('job_single', $job_data);
		return $output;
	}
    public static function format_page($page_type, $args) {
	    $output = "";
	    switch ($page_type) {
            case 'archive':
                // $args - $post_url, name, gender, image, age, ethnicity, colour

                 $output = "
<div class='ew-archive-box'>
    <img src='$args[image]'>
    <div class='ew-archive-details'>
        <h4>$args[name]</h4>
        $args[gender] $args[age]<br>
        $args[ethnicity] $args[nationality]
    </div>
    <div class='ew-archive-box-strapline'>
    $args[strapline]
    </div>
    <form action='$args[post_url]' class='ew-view-profile-button' method='get'><input type='hidden' name='profileid' value='$args[profileid]'><input type='submit' value='View Full Profile'></form>
</div>";
            break;
            case 'single':
                $output = "
    <h1 class='ew-talent-single-title'>$args[name]</h1>
    <div class='ew-talent-single-header'>
    <div class='ew-talent-single-header-details'>
        $args[gender] $args[age]<br>
        $args[ethnicity] $args[nationality]
    </div>
    <div class='ew-archive-box-strapline'>
    $args[strapline]
    </div>
   
        $args[avatar]
    </div>";

    $projects = $args['projects'];
    if (0 == count($projects)) {
        $output .= "No projects to show.";
    }
    else {
	    $output .= "<br><h2>Projects</h2>
<table><tr><th>Title</th><th>Employer</th><th>Role</th><th>Details</th></tr>";

	    if ( isset( $projects['title'] ) ) {
		    $p_count = count( $projects['title'] );
		    for ( $count = 0; $count < $p_count; $count ++ ) {
			    $output .= "<tr><td>" . $projects['title'][$count] . "</td>";
			    $output .= "<td>" . $projects['employer'][ $count ] . "</td>";
			    $output .= "<td>" . $projects['role'][ $count ] . "</td>";
			    $output .= "<td>" . $projects['details'][ $count ] . "</td></tr>";
		    }
	    }
	    $output .= "</table>";
    }
            break;
		    case 'job_archive':
			    // $args - $post_url, job_title, production_title, production_id, business_name, business_id

//			    <div class='ew-job-archive-box' onclick=\"show_job('$args[job_title]', '$args[production_title]', '$args[business_name], '$args[jobs_location]', '$args[job_brief]', '$args[job_description]', '$args[job_start_date]', '$args[job_duration]', '$args[job_pay]');\">
			    $output = "
<div class='ew-job-archive-box' onclick=\"show_job('$args[job_id]', '$args[site_url]', '$args[job_title]', '$args[production_title]', '$args[business_name]', '$args[job_location]', '$args[job_brief]', '$args[job_description]', '$args[job_start_date]', '$args[job_duration]', '$args[job_pay]');\">
    <h4>$args[job_title]</h4>
    $args[production_title]<br>
    $args[job_location]
</div>";
            break;
		    case 'job_list':
			    $output = "
<span class='ew-job-link' onclick=\"show_job2('$args[job_id]', '$args[site_url]', '$args[job_title]', '$args[job_location]', '$args[job_brief]', '$args[job_description]', '$args[job_start_date]', '$args[job_duration]', '$args[job_pay]');\">
    $args[job_title]<br>
</span>";
			    break;
            case 'job_single':
                $date = date_format(date_create($args['production_date']), " d M Y");
                $output = "
<div class='ew-job-archive'><div class='ew-job-split'><div class='ew-job-elements'>
<h2>Production</h2>
$args[production_title]<br>
$args[production_description]<br>
$date
</div>
<div class='ew-job-elements'>
<h2>Jobs</h2>
$args[jobs_list]
</div>
<div class='ew-job-elements'>
<h2>Company</h2>
$args[biz_name]<br>
$args[biz_description]<br>
$args[biz_speciality]<br>
$args[biz_phone] $args[biz_mobile]<br>
$args[biz_email]<br>
$args[biz_address_1] $args[biz_address_2] $args[biz_address_3] $args[biz_city] $args[biz_county] $args[biz_postcode]
</div>
</div>
<div class='ew-job-split'><div class='ew-job-sidebar' id='ew-job-sidebar'>
<h4>$args[job_title]</h4>
$args[job_description]<br>
$args[job_location]<br>
$args[job_start_date]<br>
$args[job_duration]<br>
$args[job_pay]<br>
</div></div></div>";
            break;
            case 'job_sidebar':
                $output = "<h4>$args[job_title]</h4>
$args[production_title]<br>
$args[business_name]<br>
$args[job_description]<br>
$args[job_location]<br>
$args[job_start_date]<br>
$args[job_duration]<br>
$args[job_pay]<br>
<form action='$args[site_url]' method='get'><input type='hidden' name='job_id' value='$args[job_id]'><input type='submit' value='Full Details'></form>";
            break;
            case 'jobs_initial':
	            $output = "<div class='ew-job-archive'><div class='ew-job-split'>$args[jobs_list]<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></div><div class='ew-job-split'><div class='ew-job-sidebar' id='ew-job-sidebar'>$args[first_job]</div></div></div>";
            break;
	    }
        return ($output);
    }

    // account functions
	public static function get_header() {
		$user = wp_get_current_user();
		$output = "";
		if ("talent" == $user->roles[0]) {
		    $output = "<h2>Account</h2>";
			$output .= $user->display_name . "<br>";
			//$output .= "<a href='" . SITE_URL . "/dashboard-talent'>Dashboard</a> |
			$output .= "
	<a href='" . SITE_URL . "/profile-talent'>Profile</a> | 
	<a href='" . SITE_URL . "/jobs-talent'>Jobs</a> | 
	<a href='" . SITE_URL . "/billing-talent'>Billing</a> | 
	<a href='" . wp_logout_url() . "'>Log out</a>";
		}
		else if ("biz" == $user->roles[0]) {
			$output = "<h2>Account</h2>";
			$output .= $user->display_name . "<br>";
			//$output .= "<a href='" . SITE_URL . "/dashboard-biz'>Dashboard</a> |
			$output .= "
	<a href='" . SITE_URL . "/profile-biz'>Profile</a> | 
	<a href='" . SITE_URL . "/jobs-biz'>Jobs</a> | 
	<a href='" . SITE_URL . "/billing-biz'>Billing</a> | 
	<a href='" . wp_logout_url() . "'>Log out</a>";
		}
		return ($output);
	}
	public static function entsweb_login($atts = [], $content = null, $tag = '') {
		// if logged in already, redirect to relevant dashboard
		$current_user = wp_get_current_user();

		$output = $error_type = "";
		/*
		if (is_wp_error($current_user)) {
			//Login failed, find out why...
			$error_types = array_keys( $current_user->errors );
			//Error type seems to be empty if none of the fields are filled out
			$error_type = 'both_empty';
			//Otherwise just get the first error (as far as I know there
			//will only ever be one)
			if ( is_array( $error_types ) && ! empty( $error_types ) ) {
				$error_type = $error_types[0];
			}
		}
		*/
		$output .= $error_type;
		//extract( shortcode_atts( array( 'failed' => 0, ), $atts ) );
        if (isset($_GET['login']) && "failed" == $_GET['login']) $failed = 1;
        else $failed = 0;
		if (1 == $failed) $output .= "Login failed, please try again.";

		$output .= wp_login_form( array(
			'echo'           => false,
			'redirect'       => SITE_URL . '/entsweb-do-login',
			'form_id'        => 'loginform',
			'label_username' => __( 'Username or Email Address' ),
			'label_password' => __( 'Password' ),
			'label_remember' => __( 'Remember Me' ),
			'label_log_in'   => __( 'Log In' ),
			'id_username'    => 'user_login',
			'id_password'    => 'user_pass',
			'id_remember'    => 'rememberme',
			'id_submit'      => 'wp-submit',
			'remember'       => true,
			'value_username' => '',
			'value_remember' => false
		));

		return($output);
	}
	public static function entsweb_do_login() {
		//wp_verify_nonce($nonce, $action);
		if (isset($_POST['submit'])) {
			$login_data = array();
			$login_data['user_email'] = sanitize_user($_POST['email']);
			$login_data['user_password'] = esc_attr($_POST['password']);

			$user = wp_signon( $login_data, false );

			if ( is_wp_error($user) ) {
				return ($user->get_error_message());
			} else {
				wp_clear_auth_cookie();
				//do_action('wp_login', $user->ID);
				wp_set_current_user($user->ID);
				wp_set_auth_cookie($user->ID, true);
			}
		}
		else {
			$user = wp_get_current_user();
			return 1;
		}
	}
	public static function entsweb_login_fail() {
		return (do_shortcode("[entsweb-login failed='1']"));
	}
	public static function account() {
		$user = wp_get_current_user();
		$output = "";
		if ("talent" == $user->roles[0]) {
		    return (do_shortcode("[entsweb-profile-talent]"));
        }
		if ("biz" == $user->roles[0]) {
			return (do_shortcode("[entsweb-profile-biz]"));
        }
		return (do_shortcode("[entsweb-login]"));
    }
	public static function dashboard_talent() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		// display links to other pages
		$output = self::get_header();

		return($output);
	}
	public static function profile_talent() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		global $current_user;

		$user_meta = get_user_meta($current_user->ID, 'entsweb_profile')[0];
		if (!($user_meta)) {
			// create talent custom post
			$args = array (
				'post_type'     => 'talent',
				'post_author'   => $current_user->ID,
                'post_status'   => 'publish',
			);
			$pid = wp_insert_post($args);
			update_user_meta( $current_user->ID, 'entsweb_profile', $pid);
		}
		else $pid = $user_meta;


		if ($_POST['action']=='profile_talent_save') {
			if ( isset( $_POST['profile_talent_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['profile_talent_nonce'],
					'profile_talent_save' )) self::profile_talent_save();

			}
		}
		// display links to other pages
		$output = self::get_header();

		// get data from custom post type for this user
		$custom = get_post_custom($pid);
		$profile_talent_hide = isset($custom['profile_talent_hide'][0]) ? esc_html($custom['profile_talent_hide'][0]) : "";
		$profile_talent_first_name = isset($custom['profile_talent_first_name'][0]) ? esc_html($custom['profile_talent_first_name'][0]) : "";
		$profile_talent_last_name = isset($custom['profile_talent_last_name'][0]) ? esc_html($custom['profile_talent_last_name'][0]) : "";
		$profile_talent_gender = isset($custom['profile_talent_gender'][0]) ? esc_html($custom['profile_talent_gender'][0]) : "";
		$profile_talent_dob = isset($custom['profile_talent_dob'][0]) ? esc_html($custom['profile_talent_dob'][0]) : "";
		$profile_talent_height = isset($custom['profile_talent_height'][0]) ? esc_html($custom['profile_talent_height'][0]) : "";
		$profile_talent_weight = isset($custom['profile_talent_weight'][0]) ? esc_html($custom['profile_talent_weight'][0]) : "";
		$profile_talent_strapline = isset($custom['profile_talent_strapline'][0]) ? esc_html($custom['profile_talent_strapline'][0]) : "";
		$profile_talent_ethnicity = isset($custom['profile_talent_ethnicity'][0]) ? esc_html($custom['profile_talent_ethnicity'][0]) : "";
		$profile_talent_nationality = isset($custom['profile_talent_nationality'][0]) ? esc_html($custom['profile_talent_nationality'][0]) : "";
		$profile_talent_speciality = isset($custom['profile_talent_speciality'][0]) ? esc_html($custom['profile_talent_speciality'][0]) : "";
		$profile_talent_tags = isset($custom['profile_talent_tags'][0]) ? esc_html($custom['profile_talent_tags'][0]) : "";
		$actor = isset($custom['profile_talent_actor'][0]) ? esc_html($custom['profile_talent_actor'][0]) : 0;
		$dancer = isset($custom['profile_talent_dancer'][0]) ? esc_html($custom['profile_talent_dancer'][0]) : 0;
		$singer = isset($custom['profile_talent_singer'][0]) ? esc_html($custom['profile_talent_singer'][0]) : 0;
		$musician = isset($custom['profile_talent_musician'][0]) ? esc_html($custom['profile_talent_musician'][0]) : 0;
		$entertainer = isset($custom['profile_talent_entertainer'][0]) ? esc_html($custom['profile_talent_entertainer'][0]) : 0;
		$crew = isset($custom['profile_talent_crew'][0]) ? esc_html($custom['profile_talent_crew'][0]) : 0;
		// get project list
		$projects = unserialize($custom['profile_talent_projects'][0]);
		if (!is_array($projects)) {
			$projects = array();
		}
		$p_list = "<br><h2>Projects</h2><table><tr><th>Delete</th><th>Title</th><th>Employer</th><th>Role</th><th>Details</th></tr>";

		if (isset($projects['title']))
        {
            $p_count = count($projects['title']);
            for ($count = 0; $count < $p_count; $count++) {
                $p_list .= "<tr><td><input type='checkbox' name='profile_talent_projects[delete][]'></td><td><input type='text' name='profile_talent_projects[title][]' value='"
                           . $projects['title'][ $count ] . "'></td>";
                $p_list .= "<td><input type='text' name='profile_talent_projects[employer][]' value='"
                           . $projects['employer'][ $count ] . "'></td>";
                $p_list .= "<td><input type='text' name='profile_talent_projects[role][]' value='"
                           . $projects['role'][ $count ] . "'></td>";
                $p_list .= "<td><input type='text' name='profile_talent_projects[details][]' value='"
                           . $projects['details'][ $count ] . "'></td></tr>";
            }
		}
		$p_list .= "<tr><td><input type='checkbox' name='profile_talent_projects[delete][]'></td>";
		$p_list .= "<td><input type='text' name='profile_talent_projects[title][]'></td>";
		$p_list .= "<td><input type='text' name='profile_talent_projects[employer][]'></td>";
		$p_list .= "<td><input type='text' name='profile_talent_projects[role][]'></td>";
		$p_list .= "<td><input type='text' name='profile_talent_projects[details][]'></td></tr>";

		$p_list .= "</table>";

		$avatar_id = isset($custom['profile_talent_avatar'][0]) ? esc_html($custom['profile_talent_avatar'][0]) : "";
		if ("" == $avatar_id) $avatar = "";
		else $avatar = "<img src='" . wp_get_attachment_image_url( $avatar_id, 'thumbnail') . "' title=''>";
		//$get_author_gravatar = get_avatar_url($get_author_id, array('size' => 450));
		//$author_archive = get_author_posts_url (get_the_author_meta( 'ID' ));
        $none_select = $african_select = $asian_select = $hispanic_select = $indian_select = $white_select = "";
		$no_gender_select = $male_select = $female_select = $other_select = '';

        switch ($profile_talent_ethnicity) {
            case '':
                $none_select = 'selected';
                break;
            case 'African':
                $african_select = 'selected';
                break;
            case 'Asian':
                $asian_select = 'selected';
                break;
            case 'Hispanic':
                $hispanic_select = 'selected';
                break;
            case 'Indian':
                $indian_select = 'selected';
                break;
            case 'White':
                $white_select = 'selected';
                break;
            default:
                $none_select = 'selected';
        }


        switch ($profile_talent_gender) {
            case '':
                $no_gender_select = 'selected';
                break;
            case 'Male':
                $male_select = 'selected';
                break;
            case 'Other':
                $other_select = 'selected';
                break;
            default:
                $no_gender_select = 'selected';
        }


		// display form
		$output .= "
<form action='' method='post' id='profile_talent_save' enctype='multipart/form-data'>
<input type='hidden' name='action' value='profile_talent_save'>" .
wp_nonce_field( 'profile_talent_save', 'profile_talent_nonce' ) . "
<br><h2>General</h2>
<input type='checkbox' name='profile_talent_hide' " . ("1" == $profile_talent_hide ? " checked" : "") . "> Hide my profile<br>
First Name: <input type='text' name='profile_talent_first_name' value='" . $profile_talent_first_name . "'>
<br>Last Name: <input type='text' name='profile_talent_last_name' value='" . $profile_talent_last_name . "'>
<br>Gender: <select name='profile_talent_gender'>
 <option value='' $no_gender_select>n/a</option>
 <option value='Male' $male_select>Male</option>
 <option value='Female' $female_select>Female</option>
 <option value='Other' $other_select>Other</option>
</select>
<br>Ethnicity: <select name='profile_talent_ethnicity'>
 <option value='' $none_select>n/a</option>
 <option value='African' $african_select>African</option>
 <option value='Asian' $asian_select>Asian</option>
 <option value='Hispanic' $hispanic_select>Hispanic</option>
 <option value='Indian' $indian_select>Indian</option>
 <option value='White' $white_select>White</option>
 </select>
<br>Nationality: <input type='text' name='profile_talent_nationality' value='" . $profile_talent_nationality . "'>
<br>Speciality: <input type='text' name='profile_talent_speciality' value='" . $profile_talent_speciality . "'>
<br>DOB: <input type='date' name='profile_talent_dob' value='" . $profile_talent_dob . "'>
<br>Height (cm): <input type='text' name='profile_talent_height' value='" . $profile_talent_height . "'>
<br>Weight (kg): <input type='text' name='profile_talent_weight' value='" . $profile_talent_weight . "'>
<br>Strapline: <input type='text' name='profile_talent_strapline' value='" . $profile_talent_strapline . "'>
<br>Tags: <input type='text' name='profile_talent_tags' value='" . $profile_talent_tags . "'>
<br><br><input type='checkbox' name='profile_talent_actor' " . ("1" == $actor ? " checked" : "") . "> Actor
<br><input type='checkbox' name='profile_talent_dancer' " . ("1" == $dancer ? " checked" : "") . "> Dancer
<br><input type='checkbox' name='profile_talent_singer' " . ("1" == $singer ? " checked" : "") . "> Singer
<br><input type='checkbox' name='profile_talent_musician' " . ("1" == $musician ? " checked" : "") . "> Musician
<br><input type='checkbox' name='profile_talent_entertainer' " . ("1" == $entertainer ? " checked" : "") . "> Entertainer
<br><input type='checkbox' name='profile_talent_crew' " . ("1" == $crew ? " checked" : "") . "> Crew
<br><br>Profile picture:<br>
$avatar
<br><br>
<input type='file' name='profile_talent_featured_image'>

<br><br>
$p_list<br><br>
<input type='submit' value='Save'>
</form>";

		return($output);
	}
	public static function billing_talent() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		// display links to other pages
		$output = self::get_header();
		/*
		global $post;
		if($_POST['action']=='billing_talent_save'){
			if ( isset( $_POST['billing_talent_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['billing_talent_nonce'],
					'billing_talent_save' )) self::billing_talent_save();

			}
		}

		// Set your secret key. Remember to switch to your live secret key in production.
		// See your keys here: https://dashboard.stripe.com/apikeys
		require_once 'vendor/autoload.php';
		$stripe = new \Stripe\StripeClient('sk_test_easytFNagYPM5weJonD7G06h');

		echo "";

		$stripe->subscriptions->create([
			'customer' => '{{CUSTOMER_ID}}',
			'items' => [['price' => '{{RECURRING_PRICE_ID}}']],
			'add_invoice_items' => [['price' => '{{ONE_TIME_PRICE_ID}}']],
		]);

		// get data from custom post type for this user
		$originalpost = $post;
		$custom = get_post_custom($post->ID);
		//$profile_talent_first_name = isset($custom['bolling_talent_first_name'][0]) ? esc_html($custom['profile_talent_first_name'][0]) : "";
*/
		return($output);
	}
	public static function media_talent() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		// display links to other pages
		$output = self::get_header();

		return($output);
	}
	public static function jobs_talent() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		// display links to other pages
		$output = self::get_header();

		return($output);
	}
	public static function dashboard_biz() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		// display links to other pages
		$output = self::get_header();

		return($output);
	}
	public static function profile_biz() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		global $current_user;

		$user_meta = get_user_meta($current_user->ID, 'entsweb_profile')[0];
		if (!($user_meta)) {
			// create biz custom post
			$args = array (
				'post_type'     => 'biz',
				'post_author'   => $current_user->ID,
				'post_status'   => 'publish',
			);
			$pid = wp_insert_post($args);
			update_user_meta( $current_user->ID, 'entsweb_profile', $pid);
		}
		else $pid = $user_meta;


		if ($_POST['action']=='profile_biz_save') {
			if ( isset( $_POST['profile_biz_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['profile_biz_nonce'],
					'profile_biz_save' )) self::profile_biz_save();
			}
		}
		// display links to other pages
		$output = self::get_header();

		// get data from custom post type for this user
		$custom = get_post_custom($pid);
		$profile_biz_hide = isset($custom['profile_biz_hide'][0]) ? esc_html($custom['profile_biz_hide'][0]) : "";
		$profile_biz_name = isset($custom['profile_biz_name'][0]) ? esc_html($custom['profile_biz_name'][0]) : "";
		$profile_biz_address_1 = isset($custom['profile_biz_address_1'][0]) ? esc_html($custom['profile_biz_address_1'][0]) : "";
		$profile_biz_address_2 = isset($custom['profile_biz_address_2'][0]) ? esc_html($custom['profile_biz_address_2'][0]) : "";
		$profile_biz_address_3 = isset($custom['profile_biz_address_3'][0]) ? esc_html($custom['profile_biz_address_3'][0]) : "";
		$profile_biz_city = isset($custom['profile_biz_city'][0]) ? esc_html($custom['profile_biz_city'][0]) : "";
		$profile_biz_county = isset($custom['profile_biz_county'][0]) ? esc_html($custom['profile_biz_county'][0]) : "";
		$profile_biz_postcode = isset($custom['profile_biz_postcode'][0]) ? esc_html($custom['profile_biz_postcode'][0]) : "";
		$profile_biz_country = isset($custom['profile_biz_country'][0]) ? esc_html($custom['profile_biz_country'][0]) : "";
		$profile_biz_type = isset($custom['profile_biz_type'][0]) ? esc_html($custom['profile_biz_type'][0]) : "";
		$profile_biz_phone = isset($custom['profile_biz_phone'][0]) ? esc_html($custom['profile_biz_phone'][0]) : "";
		$profile_biz_mobile = isset($custom['profile_biz_mobile'][0]) ? esc_html($custom['profile_biz_mobile'][0]) : "";
		$profile_biz_email = isset($custom['profile_biz_email'][0]) ? esc_html($custom['profile_biz_email'][0]) : "";
		$profile_biz_strapline = isset($custom['profile_biz_strapline'][0]) ? esc_html($custom['profile_biz_strapline'][0]) : "";
		$profile_biz_description = isset($custom['profile_biz_description'][0]) ? esc_html($custom['profile_biz_description'][0]) : "";
		$profile_biz_speciality = isset($custom['profile_biz_speciality'][0]) ? esc_html($custom['profile_biz_speciality'][0]) : "";
		$profile_biz_tags = isset($custom['profile_biz_tags'][0]) ? esc_html($custom['profile_biz_tags'][0]) : "";
		$ew_job_credits = isset($custom['ew_job_credits'][0]) ? esc_html($custom['ew_job_credits'][0]) : 0;

		$avatar_id = isset($custom['profile_biz_avatar'][0]) ? esc_html($custom['profile_biz_avatar'][0]) : "";
		if ("" == $avatar_id) $avatar = "";
		else $avatar = "<img src='" . wp_get_attachment_image_url( $avatar_id, 'thumbnail') . "' title=''>";

		// display form
		$output .= "
<form action='' method='post' id='profile_biz_save' enctype='multipart/form-data'>
<input type='hidden' name='action' value='profile_biz_save'>" .
		           wp_nonce_field( 'profile_biz_save', 'profile_biz_nonce' ) . "
<br><h2>General</h2>
<input type='checkbox' name='profile_biz_hide' " . ("1" == $profile_biz_hide ? " checked" : "") . "> Hide my profile<br>
Business Name: <input type='text' name='profile_biz_name' value='" . $profile_biz_name . "'>
<br>Address 1: <input type='text' name='profile_biz_address_1' value='" . $profile_biz_address_1 . "'>
<br>Address 2: <input type='text' name='profile_biz_address_2' value='" . $profile_biz_address_2 . "'>
<br>Address 3: <input type='text' name='profile_biz_address_3' value='" . $profile_biz_address_3 . "'>
<br>City: <input type='text' name='profile_biz_city' value='" . $profile_biz_city . "'>
<br>County: <input type='text' name='profile_biz_county' value='" . $profile_biz_county . "'>
<br>Postcode: <input type='text' name='profile_biz_postcode' value='" . $profile_biz_postcode . "'>
<br>Country: <input type='text' name='profile_biz_country' value='" . $profile_biz_country . "'>
<br>Business Type: <select name='profile_biz_type'>
<option value='Production' " . ("Production" == $profile_biz_type ? "selected" : "") . ">Production</option>
<option value='Agent' " . ("Agent" == $profile_biz_type ? "selected" : "") . ">Agent</option>
<option value='Other' " . ("Other" == $profile_biz_type ? "selected" : "") . ">Other</option>
</select>
<br>Phone: <input type='text' name='profile_biz_phone' value='" . $profile_biz_phone . "'>
<br>Mobile: <input type='text' name='profile_biz_mobile' value='" . $profile_biz_mobile . "'>
<br>Email: <input type='text' name='profile_biz_email' value='" . $profile_biz_email . "'>
<br>Strapline: <input type='text' name='profile_biz_strapline' value='" . $profile_biz_strapline . "'>
<br>Description: <textarea name='profile_biz_description'>$profile_biz_description</textarea>
<br>Speciality: <textarea name='profile_biz_speciality'>$profile_biz_speciality</textarea>
<br>Tags: <input type='text' name='profile_biz_tags' value='" . $profile_biz_tags . "'>
<br>Job Credits: " . $ew_job_credits;
        
        $site_url = get_site_url();
        $output .= "<div class='ew-credit-box'>";
        $output .= "<form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='377'>
                        <input type='submit' value='Purchase 1 credit - &pound;5'>
                        </form>";
        $output .= "&nbsp;&nbsp;&nbsp;";
		$output .= "<form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='378'>
                        <input type='submit' value='Purchase 10 credits - &pound;35'>
                        </form>";
		$output .= "&nbsp;&nbsp;&nbsp;";
        $output .= "<form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='379'>
                        <input type='submit' value='Purchase 50 credits - &pound;100'>
                        </form></div>";
        /*
        $output .= "<form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='380'>
                        <input type='submit' value='Purchase 1 FOC credit'>
                        </form></div>";
        */

$output .= "<br><br>Profile picture:<br>
$avatar
<br><br>
<input type='file' name='profile_biz_featured_image'>
<br><br>
<input type='submit' value='Save'>
</form>";

		return($output);
	}
	public static function billing_biz() {
		// display links to other pages
		$output = self::get_header();

		return($output);
	}
	public static function media_biz() {
		// display links to other pages
		$output = self::get_header();

		return($output);
	}
	public static function jobs_biz() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}

		global $current_user, $_POST;
		// delete production and all jobs associated with it
		if (isset($_POST['production_delete_nonce']) && $_POST['action'] == 'production_delete') {
			if (wp_verify_nonce( $_POST['production_delete_nonce'],'production_delete' )) {
				if ( isset( $_POST['production_id'] )
				     && "" != $_POST['production_id']
				     && "0" != $_POST['production_id'] ) {
					$production_id = sanitize_text_field( $_POST['production_id'] );
					update_post_meta( $production_id, 'production_deleted', "1" );
					$args = [
						'post_type' => 'jobs',
						'post_author' => $current_user->ID,
						'post_status' => 'publish',
						'meta_key' => 'production_id',
						'meta_value' => $production_id
					];
					$jobs = new WP_Query( $args );
					if ( $jobs->have_posts() ) {
						while ( $jobs->have_posts() ) {
							$jobs->the_post();
							$job_id = get_the_ID();
							update_post_meta( $job_id, 'job_deleted', "1" );
						}
					}
				}
			}
		}

		$user_meta = get_user_meta($current_user->ID, 'entsweb_profile')[0];
		if (!($user_meta)) {
			// create talent custom post
			$args = array (
				'post_type'     => 'biz',
				'post_author'   => $current_user->ID,
				'post_status'   => 'publish',
			);
			$pid = wp_insert_post($args);
			update_user_meta( $current_user->ID, 'entsweb_profile', $pid);
		}
		else $pid = $user_meta;

		// display links to other pages
		$output = self::get_header();
		$output .= "<br><br><h2>Jobs</h2><div style='background-color: #eee; display:flex; vertical-align: middle;'><span style='font-size:50px; padding:0 30px; vertical-align: middle;'>!</span><span style='padding-top:20px;'>To publish jobs and casting calls, first create a production. You can then add as many jobs to that production as you need.<br>Edit the production to see the associated jobs. Jobs are listed under the production details. You may need to scroll down to see them.</span></div>";

		$production_link = get_site_url() . "/productions";
		$delete_link = get_site_url() . "/jobs-biz";
		// get production list
        $args = [
                'post_type'     => 'production',
                'post_author'   => $current_user->ID,
                'post_status'   => 'publish',
                'meta_key'      => 'production_deleted',
                'meta_compare'  => 'NOT EXISTS'
        ];
		$productions = new WP_Query($args);
		if ($productions->have_posts()) {
			$output .= "<br><h2>Productions</h2><table><tr><th>Title</th><th>Date</th><th>No. Jobs</th><th style='width:10%;'></th><th style='width:10%;'></th></tr>";
			while ($productions->have_posts()) {
				$productions->the_post();
				$production_id = get_the_ID();
			    $custom = get_post_custom($production_id);
				$output .= "<tr><td>" . $custom['production_title'][0] . "</td>";
				$output .= "<td>" . $custom['production_date'][0] . "</td>";
				$output .= "<td><form action='$production_link' method='post'>" .
				           wp_nonce_field( 'production_biz', 'production_biz_nonce' ) . "
                                <input type='hidden' name='action' value='production_biz'>
                                <input type='hidden' name='production_id' value='$production_id'>
                                <input type='submit' value='Edit'></form></td>";
				$output .= "<td><form action='$delete_link' method='post' onSubmit='return confirm(\"Are you sure you wish to delete? The production and all associated jobs will be deleted. This cannot be undone.\");'>" .
				           wp_nonce_field( 'production_delete', 'production_delete_nonce' ) . "
                                <input type='hidden' name='action' value='production_delete'>
                                <input type='hidden' name='production_id' value='$production_id'>
                                <input type='submit' value='Delete' style='background-color:red;'></form></td></tr>";
			}
			$output .= "</table>";
		}
		else $output .= "You don't have any productions. Create one.";

		$output .= "<form action='$production_link'>" .
                        wp_nonce_field( 'production_biz', 'production_biz_nonce' ) . "
                        <input type='hidden' name='action' value='production_biz'>
                        <input type='hidden' name='production_id' value='0'>
                        <input type='submit' value='Create New Production'>
                        </form>";

		return($output);
	}
	public static function production_biz() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		global $current_user, $_POST, $_GET;

		if ($_POST['action'] && $_POST['action']=='production_save') {
			if ( isset( $_POST['production_save_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['production_save_nonce'],
					'production_save' )) self::production_save();
			}
		}
		elseif ($_POST['action'] && $_POST['action']=='job_delete') {
			if ( isset( $_POST['job_delete_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['job_delete_nonce'],
					'job_delete' )) {
					if ( isset( $_POST['job_id'] ) && "" != $_POST['job_id'] && "0" != $_POST['job_id'] ) {
						$job_id = sanitize_text_field( $_POST['job_id'] );
						update_post_meta( $job_id, 'job_deleted', "1" );
					}
                }
			}
		}
		elseif (isset($_POST['action']) && $_POST['action']=='production_biz') {
			if ( isset( $_POST['production_biz_nonce'] ) ) {
				if (!wp_verify_nonce( $_POST['production_biz_nonce'],
					'production_biz' )) return;
			}
			else return;
		}
		elseif (isset($_GET['production_id'])) $_POST['production_id'] = sanitize_text_field($_GET['production_id']);
		else return;

		// display links to other pages
		$output = self::get_header();

		$production_title = $production_hide = $form_production_id = $production_date = $production_description = "";


		if (isset($_POST['production_id']) && "0" != $_POST['production_id']) {
		    $production_id = sanitize_text_field($_POST['production_id']);
			$custom = get_post_custom($production_id);
			$production_hide = esc_html($custom['production_hide'][0]);
			$production_title = esc_html($custom['production_title'][0]);
			$production_description = esc_html($custom['production_description'][0]);
			$production_date = esc_html($custom['production_date'][0]);
		}
		else {
		    $custom = [];
		    $production_id = "0";
		}
		$form_production_id = "<input type='hidden' name='production_id' value='$production_id'>";

		// get data from custom post type for this user


		//$output .= "<h3>Productions</h3>";

		// get jobs list
		$jobs = unserialize($custom['jobs'][0]);
		if (!is_array($jobs)) {
			$jobs = array();
		}
		$production_link = get_site_url() . "/productions";
		$job_link = get_site_url() . "/production-jobs";
		$jobs_list = "";

		// get jobs list
		$args = [
			'post_type'     => 'jobs',
			'post_author'   => $current_user->ID,
			'post_status'   => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'      => 'jobs_deleted',
                    'compare'  => 'NOT EXISTS'
                ],
                [
                    'key'      => 'production_id',
                    'value'    => $production_id
                ]
            ]
		];
		$jobs = new WP_Query($args);
		if ($jobs->have_posts()) {
			$jobs_list .= "<br><h2>Jobs</h2><table><tr><th>Title</th><th style='width:10%;'></th><th style='width:10%;'></th></tr>";
			while ($jobs->have_posts()) {
				$jobs->the_post();
				$job_id = get_the_ID();
				$custom_job = get_post_custom($job_id);
				$job_title = $custom_job['job_title'][0];

				$jobs_list .= "<tr><td>$job_title</td>";
				$jobs_list .= "<td><form action='$job_link' method='post'><input type='hidden' name='job_id' value='$job_id'>" .
				           wp_nonce_field( 'production_job', 'production_job_nonce' ) . "
                                <input type='hidden' name='action' value='production_job'>
                                <input type='hidden' name='production_id' value='$production_id'>
                                <input type='hidden' name='production_title' value='$production_title'>
                                <input type='submit' value='Edit'></form></td>";
				$jobs_list .= "<td><form action='$production_link' method='post'><input type='hidden' name='job_id' value='$job_id'>" .
				           wp_nonce_field( 'job_delete', 'job_delete_nonce' ) . "
                                <input type='hidden' name='action' value='job_delete'>
                                <input type='submit' value='Delete' style='background-color:red;'></form></td></tr>";
			}
			$jobs_list .= "</table>";
		}
		else $jobs_list = "You don't have any jobs. Create one.";
		$pid = get_user_meta($current_user->ID, 'entsweb_profile')[0];
		$user_custom = get_post_custom($pid);
		$job_credits = (int)$user_custom['ew_job_credits'][0];
		if ("0" == $production_id) $jobs_list = "<p>Save the production, then you can add jobs to it.</p>";
		else {
		    if ( $job_credits > 0 ) {
			    $jobs_list .= "You have $job_credits credits left.<br><form action='$job_link' method='post'>
                        <input type='hidden' name='production_id' value='$production_id'>
                        <input type='hidden' name='production_title' value='$production_title'>
                        <input type='hidden' name='new' value='true'>
                        <input type='submit' value='Create New Job'>
                        </form>";
		    }
		    else {
		        $site_url = get_site_url();
		        $jobs_list .= "You don't have any credits. You need 1 credit for each job you wish to publish.<br>";
			    $jobs_list .= "<div class='ew-credit-box'>
                        <form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='377'>
                        <input type='submit' value='Purchase 1 credit - &pound;5'>
                        </form>";
			    $output .= "&nbsp;&nbsp;&nbsp;";
			    $jobs_list .= "<form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='378'>
                        <input type='submit' value='Purchase 10 credits - &pound;35'>
                        </form>";
			    $output .= "&nbsp;&nbsp;&nbsp;";
			    $jobs_list .= "<form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='379'>
                        <input type='submit' value='Purchase 50 credits - &pound;100'>
                        </form></div>";
			    /*
			    $jobs_list .= "<form action='$site_url' method='get'>
                        <input type='hidden' name='add-to-cart' value='380'>
                        <input type='submit' value='Purchase 1 FOC credit'>
                        </form></div>";
			    */
            }
		}
		// display form
		$output .= "
<form action='' method='post' id='production_save' enctype='multipart/form-data'>
<input type='hidden' name='action' value='production_save'>" .
		           wp_nonce_field( 'production_save', 'production_save_nonce' ) . "
<br><h2>Production</h2>
<input type='checkbox' name='production_hide' " . ("1" == $production_hide ? " checked" : "") . "> Hide this production<br>
Title: <input type='text' name='production_title' value='" . $production_title . "'>
<br>Start Date: <input type='date' name='production_date' value='" . $production_date . "'>
<br>Description: <textarea name='production_description'>$production_description</textarea>
$form_production_id<br><br>
<input type='submit' value='Save'>
</form>
<br><br>
$jobs_list";
		return($output);
	}
	public static function production_jobs_biz() {
		if (!is_user_logged_in() ) {
			return 'You must be logged in to see this page.';
		}
		global $current_user, $_POST;

		if (isset($_POST['action']) && $_POST['action']=='job_save') {
			if ( isset( $_POST['job_save_nonce'] ) ) {
				if (wp_verify_nonce( $_POST['job_save_nonce'],
					'job_save' )) self::job_save();

			}
		}
		$job_hide = $job_description = $job_title = "";

		if (isset($_POST['job_id']) && "0" != $_POST['job_id']) {
			$job_id = sanitize_text_field($_POST['job_id']);
			$custom = get_post_custom($job_id);
			$job_hide = esc_html($custom['job_hide'][0]);
			$job_title = esc_html($custom['job_title'][0]);
			$job_brief = esc_html($custom['job_brief'][0]);
			$job_description = esc_html($custom['job_description'][0]);
			$job_location = esc_html($custom['job_location'][0]);
			$job_start_date = esc_html($custom['job_start_date'][0]);
			$job_duration = esc_html($custom['job_duration'][0]);
			$job_pay = esc_html($custom['job_pay'][0]);
		}
		else {
			$custom = [];
			$job_id = "0";
		}
		$production_id = sanitize_text_field($_POST['production_id']);
		// display links to other pages
		$output = self::get_header();
		$production_link = get_site_url() . "/productions?production_id=$production_id";
		$production_title = sanitize_text_field($_POST['production_title']);
		$bread_crumb = "<a href='$production_link'>< $production_title</a>";

		// get business details
        $args = [
	        'post_type'     => 'business',
	        'post_author'   => $current_user->ID,
	        'post_status'   => 'publish'
        ];

		$business = new WP_Query($args);
		if ($business->have_posts()) {
            $business->the_post();
            $business_id = get_the_ID();
			$custom = get_post_custom($business_id);
			$business_name = esc_html($custom['profile_biz_name'][0]);
		}
		
		$output .= "<br><br>$bread_crumb<br>
<form action='' method='post' id='job_save' enctype='multipart/form-data'>
<input type='hidden' name='action' value='job_save'>" .
		           wp_nonce_field( 'job_save', 'job_save_nonce' ) . "
<input type='hidden' name='production_id' value='$production_id'>
<input type='hidden' name='production_title' value='$production_title'>
<input type='hidden' name='business_id' value='$business_id'>
<input type='hidden' name='business_name' value='$business_name'>
<input type='hidden' name='job_id' value='$job_id'>
<br><h2>Job</h2>
<input type='checkbox' name='job_hide' " . ("1" == $job_hide ? " checked" : "") . "> Hide this job<br>
Title: <input type='text' name='job_title' value='" . $job_title . "'>
<br>Location: <input type='text' name='job_location' value='" . $job_location . "'>
<br>Brief: <input type='text' name='job_brief' value='" . $job_brief . "'>
<br>Description: <textarea name='job_description'>$job_description</textarea>
<br>Start Date: <input type='date' name='job_start_date' value='" . $job_start_date . "'>
<br>Duration: <input type='text' name='job_duration' value='" . $job_duration . "'>
<br>Pay: <input type='text' name='job_pay' value='" . $job_pay . "'>
<br><br>
<input type='submit' value='Save'>
</form>";

		return($output);
	}
	public static function profile_talent_save() {
		if ( empty( $_POST ) ) {
			return;
		}
		if (!is_user_logged_in() ) {
		    return;
        }
		$current_user = get_current_user_id();
		$pid = get_user_meta($current_user, 'entsweb_profile')[0];

		$profile_talent_projects = array();
		for ($count = 0; $count < count($_POST['profile_talent_projects']); $count++) {
			if (!isset($_POST['profile_talent_projects']['delete'][$count])) {
				$profile_talent_projects['title'][] = $_POST['profile_talent_projects']['title'][$count];
				$profile_talent_projects['employer'][] = $_POST['profile_talent_projects']['employer'][$count];
				$profile_talent_projects['role'][] = $_POST['profile_talent_projects']['role'][$count];
				$profile_talent_projects['details'][] = $_POST['profile_talent_projects']['details'][$count];
			}
		}

		update_post_meta( $pid, 'profile_talent_hide',
			isset( $_POST['profile_talent_hide'] ) ? 1 : 0);
        $talent_first_name = sanitize_text_field( $_POST['profile_talent_first_name'] );
        $talent_last_name = sanitize_text_field( $_POST['profile_talent_last_name'] );
		update_post_meta( $pid, 'profile_talent_first_name', $talent_first_name );
		update_post_meta( $pid, 'profile_talent_last_name', $talent_last_name );
		update_post_meta( $pid, 'profile_talent_full_name', $talent_first_name . ' ' . $talent_last_name );

		$gender = sanitize_text_field( $_POST['profile_talent_gender'] );
		if ("Male" != $gender && "Female" != $gender && "Other" != $gender) $gender = "";
		update_post_meta( $pid, 'profile_talent_gender',
			$gender );
		update_post_meta( $pid, 'profile_talent_dob',
			sanitize_text_field( $_POST['profile_talent_dob'] ) );
		update_post_meta( $pid, 'profile_talent_height',
			sanitize_text_field( $_POST['profile_talent_height'] ) );
		update_post_meta( $pid, 'profile_talent_weight',
			sanitize_text_field( $_POST['profile_talent_weight'] ) );

		$ethnicity = sanitize_text_field( $_POST['profile_talent_ethnicity'] );
		if ("African" != $ethnicity && "Asian" != $ethnicity && "Hispanic" != $ethnicity && "Indian" != $ethnicity && "White" != $ethnicity) $ethnicity = "";
		update_post_meta( $pid, 'profile_talent_ethnicity',
			 $ethnicity );
		update_post_meta( $pid, 'profile_talent_nationality',
			sanitize_text_field( $_POST['profile_talent_nationality'] ) );
		update_post_meta( $pid, 'profile_talent_speciality',
			sanitize_text_field( $_POST['profile_talent_speciality'] ) );
		update_post_meta( $pid, 'profile_talent_tags',
			sanitize_text_field( $_POST['profile_talent_tags'] ) );

		$datetime1 = strtotime(date('Y-m-d'));
		$datetime2 = strtotime(date('Y-m-d', strtotime(sanitize_text_field( $_POST['profile_talent_dob'] ))));

		$secs = $datetime1 - $datetime2;// == <seconds between the two times>
		$age = intval(floor($secs / 31536000));

		update_post_meta( $pid, 'profile_talent_age',
			$age );
		update_post_meta( $pid, 'profile_talent_strapline',
			sanitize_text_field( $_POST['profile_talent_strapline'] ) );
		update_post_meta( $pid, 'profile_talent_projects',
				 $profile_talent_projects  );

		update_post_meta( $pid, 'profile_talent_actor',
			isset( $_POST['profile_talent_actor'] ) ? 1 : 0);
		update_post_meta( $pid, 'profile_talent_dancer',
			isset( $_POST['profile_talent_dancer'] ) ? 1 : 0);
		update_post_meta( $pid, 'profile_talent_singer',
			isset( $_POST['profile_talent_singer'] ) ? 1 : 0);
		update_post_meta( $pid, 'profile_talent_musician',
			isset( $_POST['profile_talent_musician'] ) ? 1 : 0);
		update_post_meta( $pid, 'profile_talent_entertainer',
			isset( $_POST['profile_talent_entertainer'] ) ? 1 : 0);
		update_post_meta( $pid, 'profile_talent_crew',
			isset( $_POST['profile_talent_crew'] ) ? 1 : 0);

        if (isset($_FILES['profile_talent_featured_image']) && $_FILES['profile_talent_featured_image']['size'] != '0') {
	        $wp_upload_dir = wp_upload_dir();
	        $filename      = $wp_upload_dir['path'] . '-' . date( 'Y-m-d-H-i' )
	                         . '-'
	                         . $_FILES['profile_talent_featured_image']['tmp_name'];
	        $filetype      = wp_check_filetype( basename( $filename ), NULL );

	        //$m = move_uploaded_file( $_FILES['tmp_name'], $wp_upload_dir['path'] . '/' . $filename);
	        $attachment = [
		        'guid'           => $wp_upload_dir['url'] . '/'
		                            . basename( $filename ),
		        'post_mime_type' => $filetype['type'],
		        'post_title'     => sanitize_file_name( $filename ),
		        'post_content'   => '',
		        'post_status'    => 'inherit'
	        ];
	        require_once( ABSPATH . 'wp-admin/includes/image.php' );
	        require_once( ABSPATH . 'wp-admin/includes/file.php' );
	        require_once( ABSPATH . 'wp-admin/includes/media.php' );
	        $image_id = media_handle_upload( 'profile_talent_featured_image',
		        $pid );

	        update_post_meta( $pid, 'profile_talent_avatar',
		        $image_id );
        }
	}
	public static function profile_biz_save() {
	if ( empty( $_POST ) ) {
		return;
	}
	if (!is_user_logged_in() ) {
		return;
	}
	$current_user = get_current_user_id();
	$pid = get_user_meta($current_user, 'entsweb_profile')[0];

	update_post_meta( $pid, 'profile_biz_hide',
		isset( $_POST['profile_biz_hide'] ) ? 1 : 0);
	$biz_name = sanitize_text_field( $_POST['profile_biz_name'] );
	update_post_meta( $pid, 'profile_biz_name', $biz_name);
	update_post_meta( $pid, 'profile_biz_address_1', sanitize_text_field( $_POST['profile_biz_address_1'] ));
	update_post_meta( $pid, 'profile_biz_address_2', sanitize_text_field( $_POST['profile_biz_address_2'] ));
	update_post_meta( $pid, 'profile_biz_address_3', sanitize_text_field( $_POST['profile_biz_address_3'] ));
	update_post_meta( $pid, 'profile_biz_city', sanitize_text_field( $_POST['profile_biz_city'] ));
	update_post_meta( $pid, 'profile_biz_county', sanitize_text_field( $_POST['profile_biz_county'] ));
	update_post_meta( $pid, 'profile_biz_postcode', sanitize_text_field( $_POST['profile_biz_postcode'] ));
	update_post_meta( $pid, 'profile_biz_country', sanitize_text_field( $_POST['profile_biz_country'] ));
	update_post_meta( $pid, 'profile_biz_phone', sanitize_text_field( $_POST['profile_biz_phone'] ));
	update_post_meta( $pid, 'profile_biz_mobile', sanitize_text_field( $_POST['profile_biz_mobile'] ));
	update_post_meta( $pid, 'profile_biz_email', sanitize_text_field( $_POST['profile_biz_email'] ));
	update_post_meta( $pid, 'profile_biz_description', sanitize_text_field( $_POST['profile_biz_description'] ));
	update_post_meta( $pid, 'profile_biz_speciality', sanitize_text_field( $_POST['profile_biz_speciality'] ));

	if ("Agent" == sanitize_text_field( $_POST['profile_biz_type'] )) $biz_type = "Agent";
	elseif ("Production" == sanitize_text_field( $_POST['profile_biz_type'] )) $biz_type = "Production";
	else $biz_type = "Other";
	update_post_meta( $pid, 'profile_biz_type', $biz_type);
	update_post_meta( $pid, 'profile_biz_tags', sanitize_text_field( $_POST['profile_biz_tags'] ) );
	update_post_meta( $pid, 'profile_biz_strapline', sanitize_text_field( $_POST['profile_biz_strapline'] ) );

	if (isset($_FILES['profile_biz_featured_image']) && $_FILES['profile_biz_featured_image']['size'] != '0') {
		$wp_upload_dir = wp_upload_dir();
		$filename      = $wp_upload_dir['path'] . '-' . date( 'Y-m-d-H-i' )
		                 . '-'
		                 . $_FILES['profile_biz_featured_image']['tmp_name'];
		$filetype      = wp_check_filetype( basename( $filename ), NULL );

		//$m = move_uploaded_file( $_FILES['tmp_name'], $wp_upload_dir['path'] . '/' . $filename);
		$attachment = [
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		];
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		$image_id = media_handle_upload( 'profile_biz_featured_image', $pid );
		update_post_meta( $pid, 'profile_biz_avatar', $image_id );


	}
	// update all jobs with business name
    $args = [
        'post_type' => 'jobs',
        'author_id' => $current_user
    ];
    $biz = new WP_Query($args);
    if ($biz->have_posts()) {
        while ($biz->have_posts()) {
            $biz->the_post();
            update_post_meta(get_the_id(), 'business_name', $biz_name);
            update_post_meta(get_the_id(), 'business_id', get_the_id());
        }
    }
		// update all productions with business name
		$args = [
			'post_type' => 'productions',
			'author_id' => $current_user
		];
		$biz = new WP_Query($args);
		if ($biz->have_posts()) {
			while ($biz->have_posts()) {
				$biz->the_post();
				update_post_meta(get_the_id(), 'business_name', $biz_name);
				update_post_meta(get_the_id(), 'business_id', get_the_id());
			}
		}
}
	public static function production_save() {
		if ( empty( $_POST ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		global $current_user;
		if ( "0" == $_POST['production_id'] ) {
			$args = [
				'post_type'   => 'production',
				'post_author' => $current_user->ID,
				'post_status' => 'publish',
			];
			$pid  = wp_insert_post( $args );
		} else {
			$pid = $_POST['production_id'];
		}
		$_POST['production_id'] = $pid;
		update_post_meta( $pid, 'production_hide',
			isset( $_POST['production_hide'] ) ? 1 : 0 );
		update_post_meta( $pid, 'production_title',
			sanitize_text_field( $_POST['production_title'] ) );
		update_post_meta( $pid, 'production_date',
			sanitize_text_field( $_POST['production_date'] ) );
		update_post_meta( $pid, 'production_description',
			sanitize_text_field( $_POST['production_description'] ) );

	}
	public static function job_save() {
	    global $_POST;
		if ( empty( $_POST ) ) {
			return;
		}
		if (!is_user_logged_in() ) {
			return;
		}
		global $current_user;
		if (!isset($_POST['job_id']) || "0" == $_POST['job_id']) {
			$args = [
				'post_type'     => 'jobs',
				'post_author'   => $current_user->ID,
				'post_status'   => 'publish'
			];
			$pid = wp_insert_post($args);
			$_POST['job_id'] = $pid;

            $uid = get_user_meta($current_user->ID, 'entsweb_profile')[0];
            $user_custom = get_post_custom($uid);
            $job_credits = (int)$user_custom['ew_job_credits'][0];
            $job_credits--;
            update_post_meta( $uid, 'ew_job_credits', $job_credits );

		}
		else $pid = $_POST['job_id'];

		update_post_meta( $pid, 'job_hide', isset( $_POST['job_hide'] ) ? 1 : 0);
		update_post_meta( $pid, 'job_title', sanitize_text_field( $_POST['job_title'] ));
		update_post_meta( $pid, 'job_location', sanitize_text_field( $_POST['job_location'] ));
		update_post_meta( $pid, 'job_brief', sanitize_text_field( $_POST['job_brief'] ));
		update_post_meta( $pid, 'job_description', sanitize_text_field( $_POST['job_description'] ));
		update_post_meta( $pid, 'job_start_date', sanitize_text_field( $_POST['job_start_date'] ));
		update_post_meta( $pid, 'job_duration', sanitize_text_field( $_POST['job_duration'] ));
		update_post_meta( $pid, 'job_pay', sanitize_text_field( $_POST['job_pay'] ));
		update_post_meta( $pid, 'production_id', sanitize_text_field( $_POST['production_id'] ));
		update_post_meta( $pid, 'production_title', sanitize_text_field( $_POST['production_title'] ));
		update_post_meta( $pid, 'business_name', sanitize_text_field( $_POST['business_name'] ));
		update_post_meta( $pid, 'business_id', $current_user->ID);
	}
	public static function billing_talent_save() {

	}


	/**
	 * Treat the creation of an API key the same as updating the API key to a new value.
	 *
	 * @param mixed  $option_name   Will always be 'wordpress_api_key', until something else hooks in here.
	 * @param mixed  $value         The option value.
	 */
	public static function added_option( $option_name, $value ) {
		if ( 'wordpress_api_key' === $option_name ) {
			return self::updated_option( '', $value );
		}
	}
	public static function main_function( $v1, $v2 ) {
		// If no key is configured, then there's no point in doing any of this.
		/*
		if ( ! self::get_api_key() ) {
			return $commentdata;
		}

		self::$last_comment_result = null;
		*/

	}
	public static function is_test_mode() {
		return defined('ENTSWEB_TEST_MODE') && ENTSWEB_TEST_MODE;
	}
	public static function entsweb_admin(){
		add_menu_page(
				'Dashboard',
				'EntsWeb',
				'manage_options',
				'entsweb-admin',
				 array('Entsweb','entsweb_dashboard') );
/*
		add_submenu_page(
			'entsweb-admin',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'entsweb-dashboard',
			array('Entsweb','entsweb_display_admin') );
*/

		add_submenu_page(
			'entsweb-admin',
			'Customers',
			'Customers',
			'manage_options',
			'entsweb-customers',
			array('Entsweb','entsweb_customers')
);
	}
	public static function entsweb_dashboard(){
		?>
		<div class="wrap"><h1>EntsWeb Dashboard</h1></div>
		<?php
	}
	public static function entsweb_customers(){
		?>
		<div class="wrap"><h1>Customers</h1></div>
		<?php
	}
	public static function signup() {
		if (is_user_logged_in()) {
		    if (current_user_can('administrator')) return;
		    $s = get_site_url() . "/account";
		    //$x = wp_redirect($s);
            echo "Thanks for signing up. Now <a href='$s'>go to your account</a> and enter your details to get started.";
		}
		else echo "Sign up today as a business for free.<br>We will be adding talent sign up options very soon.";
	}
	public static function ew_payment_complete( $order_id ) {
		$order = wc_get_order( $order_id );
		$user_id = $order->get_user_id();

		$new_credits = 0;
		foreach ( $order->get_items() as $item_id => $item  ) {
			$name = $item->get_name();
			$quantity = $item->get_quantity();

			if ("Job Credit x 1" == $name || "Job Credits x 1 FOC" == $name) $new_credits += $quantity;
			if ("Job Credits x 10" == $name) $new_credits += 10 * $quantity;
			if ("Job Credits x 50" == $name) $new_credits += 50 * $quantity;
			if ("Job Credits x 1 FOC" == $name) $new_credits += 1 * $quantity;
		}
		$pid = get_user_meta($user_id, 'entsweb_profile')[0];
		$custom = get_post_custom($pid);
		$current_credits = $custom['ew_job_credits'][0];
		if (!$current_credits) $current_credits = 0;
		$total_credits = $current_credits + $new_credits;

		update_post_meta( $pid, 'ew_job_credits', $total_credits);
	}

	public static function ew_change_user_type($user) {
	$u = get_user_by('id', $user['user_id']);
	if ($user['subscriptions'][0] == "81")
	{
		$u->remove_role( 'subscriber' );
		$u->set_role('biz');
		$user['role'] = "biz";
		// add 10 credits

		$user_meta = get_user_meta($user['user_id'], 'entsweb_profile')[0];
		if (!($user_meta)) {
			// create biz custom post
			$args = array (
				'post_type'     => 'biz',
				'post_author'   => $user['user_id'],
				'post_status'   => 'publish',
			);
			$pid = wp_insert_post($args);
			update_user_meta( $user['user_id'], 'entsweb_profile', $pid);
		}
		else $pid = $user_meta;


		$user_custom = get_post_custom($user['user_id']);
		$job_credits = (int)$user_custom['ew_job_credits'][0];
		$job_credits += 2;
		update_post_meta( $pid, 'ew_job_credits', $job_credits );
	}
    else {
        $u->remove_role( 'subscriber' );
        $u->set_role('talent');
        $user['role'] = "talent";
    }
    return ($user);
    }

    public static function my_front_end_login_fail( $username ) {
	    $referrer = $_SERVER['HTTP_REFERER'];  // where did the post submission come from?
	    // if there's a valid referrer, and it's not the default log-in screen
	    if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') ) {
		    wp_redirect( $referrer . "?login=failed");  // let's append some information (login=failed) to the URL for the theme to use
		    exit;
	    }
    }

	public static function auto_redirect_after_logout(){
		wp_safe_redirect( home_url() );
		exit;
	}
}
