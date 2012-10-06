<?php
global $wpdb;

	/*
		Plugin Name: helloModi Social Discount
		Plugin URI: http://hellomodi.com/
		Description: Get your store more social media attention
		Version: 1.0
		Author: helloModi Team
		Author URI: http://hellomodi.com
		License: GNU General Public License v2
	*/

		add_action('wp_print_styles','hello_modi');
		function hello_modi()
		{
			echo '<link rel="stylesheet" type="text/css" href="'. pluginFolderLocation() .'helloModi.css">';
		}

		function pluginFolderLocation(){
			return plugins_url().'/'.basename(dirname(__FILE__)).'/';
		}

		/* Runs when plugin is activated */
		register_activation_hook(__FILE__,'hello_modi_install'); 

		/* Runs on plugin deactivation*/
		register_deactivation_hook( __FILE__, 'hello_modi_remove' );

		function hello_modi_install() {
			/* Creates new database field */
			add_option("hello_modi_site_name", "HelloModi", '', 'yes');
			add_option("hello_modi_site_url", "http://www.hellomodi.com", '', 'yes');
			add_option("hello_modi_chosen_coupon", 'Default', '', 'yes');
			add_option("hello_modi_coupon_copy", "off", '', 'yes');
			add_option("hello_modi_text_edit", "off", '', 'yes');
			add_option("hello_modi_data", 'Default', '', 'yes');
			add_option("hello_modi_platform", 'none', '', 'yes');
		}

		function hello_modi_remove() {
			/* Deletes the database field. */
			delete_option('hello_modi_site_name');
			delete_option('hello_modi_site_url');
			delete_option('hello_modi_chosen_coupon');
			delete_option('hello_modi_coupon_copy');
			delete_option('hello_modi_text_edit');
			delete_option('hello_modi_data');
			delete_option('hello_modi_platform');
		}

		function soap_client($modisess){
			require_once "nusoap/lib/nusoap.php";
			$client = new nusoap_client("http://hellomodi.com/smpc/modiserver.php");
			$error = $client->getError();

			if ($error) {
				echo "<h2>Constructor error</h2><pre>" . $error . "</pre>";
			}
			$result = $client->call("get_sess", array("session" => $modisess));
			return $result;

		}


		/* A simple short-code function. */
		function hello_modi_func( $atts ){	

			$shortCodeOutput .= '';

			if(!isset($_SESSION['modi_sess'])){
				$arr = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz1234567890'); 
				shuffle($arr); 
				$arr = array_slice($arr, 0, 35); 
				$str = implode('', $arr); 
				$_SESSION['modi_sess'] = $str;
			}
			else{
				$str = $_SESSION['modi_sess'];
			}

			$siteName = get_option('hello_modi_site_name');
			$siteURL = get_option('hello_modi_site_url');
			$textEdit = get_option('hello_modi_text_edit');
			$fbstring = get_option('hello_modi_data');

			$val = soap_client($str);

			if(empty($val)) $shortCodeOutput .= '<a id="modiButton" href="#" onClick="smPopup()">';
			$shortCodeOutput .= '<div id="modiBox">';
			$shortCodeOutput .= '<img src="'. pluginFolderLocation() .'Modi.png" />';
			$shortCodeOutput .= '<div id="modiText">';

			if(!empty($val)) {
				if(get_option('hello_modi_coupon_copy') == "off") {
					$shortCodeOutput .= "Coupon Code:<br/> ";
					$shortCodeOutput .= "<span id='couponCode'>" . get_option('hello_modi_chosen_coupon') . "</span>";
				} 
				else {
					if (!isset($_SESSION['code']) && get_option('hello_modi_platform') == 'WPeCommerce') $_SESSION['code']= addCouponWPEComm();
					if (!isset($_SESSION['code']) && get_option('hello_modi_platform') == 'WooCommerce') $_SESSION['code']= addCouponWoo();

					$shortCodeOutput .= "Coupon Code:<br/> ";
					$shortCodeOutput .= "<span id='couponCode'>" . $_SESSION['code'] . "</span>";
				}
			}
			else {
				$shortCodeOutput .= "Click here for your discount";
			}
			$shortCodeOutput .= '</div>';
			$shortCodeOutput .= '</div>';
			if(empty($val)) $shortCodeOutput .= '</a>';

			$thisURL = urlencode(currentURL());

			$shortCodeOutput .= '<script type="text/javascript">	
			<!--

			function smPopup() {
				window.open( "http://www.hellomodi.com/smpc/index.php?m_id='.bin2hex($fbstring).'&m_te='.bin2hex($textEdit).'&m_sn='.bin2hex($siteName).'&m_su='.bin2hex($siteURL).'&ms='.$str.'&url='.$thisURL.'",
					"myWindow", 
					"status = 1, height = 372, width = 560, resizable = 0" )
}

		//-->

</script>

';
return $shortCodeOutput;

}
add_shortcode( 'modi', 'hello_modi_func' );


if ( is_admin() ){

	/* Call the html code */
	add_action('admin_menu', 'hello_modi_admin_menu');

	function hello_modi_admin_menu() {
		add_options_page('Hello Modi', 'Hello Modi', 'administrator',
			'hello-modi', 'hello_modi_html_page');
	}
}
/* Options page HTML */
function hello_modi_html_page() {

	?>
	<?php  
	global $wpdb;
	global $table_prefix;
	if (!isset($wpdb->wpsc_coupon_codes)) {
		$wpdb->wpsc_coupon_codes = $table_prefix . 'wpsc_coupon_codes';
	}

	?>
	<div>

		<script type="text/javascript">
		function getinfo()
		{
			document.getElementById("info").innerHTML = "Please save this page to see following options";
		}

		</script>

		<h2>Hello Modi Options</h2>
		
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"];?>">
			<?php wp_nonce_field('update-options'); ?>
			
			Which shopping cart platform are you using?<br/>
			<input type="radio" name="hello_modi_platform" id="hello_modi_platform" value="none" onchange="getinfo()" <?php if(get_option('hello_modi_platform') == 'none') echo "checked=''";  ?>/> None<br/>
			<input type="radio" name="hello_modi_platform" id="hello_modi_platform" value="WPeCommerce" onchange="getinfo()" <?php if(get_option('hello_modi_platform') == 'WPeCommerce') echo "checked=''";  ?>/> WP eCommerce<br/>
			<input type="radio" name="hello_modi_platform" id="hello_modi_platform" value="WooCommerce" onchange="getinfo()" <?php if(get_option('hello_modi_platform') == 'WooCommerce') echo "checked=''";  ?>/> WooCommerce <br/><br/>
			
			<b>
				<div id="info" style="color:red;">
				<?php 
				if(get_option('hello_modi_platform') == 'none') echo "Please choose a Shopping Cart platform above.";

				if(get_option('hello_modi_platform') == 'WooCommerce') echo "Place the short code - [modi] - on your 'Cart' page";  

				if(get_option('hello_modi_platform') == 'WPeCommerce') echo "Place the short code - [modi] - on your 'CheckOut' page";


				?></div></b>
				<br/><br/>

				<?php if(get_option('hello_modi_platform') != 'none') { ?>

				Enter the words which will link back to your store from the users post
				<br/>
				<input name="hello_modi_site_name" type="text" id="hello_modi_site_name" size="50" value="<?php echo get_option('hello_modi_site_name'); ?>" />
				<br/><br/>
				Enter Site URL:
				<br/>
				<input name="hello_modi_site_url" type="text" id="hello_modi_site_url" size="50" value="<?php echo get_option('hello_modi_site_url'); ?>" />
				<br/><br/>
				<?php 
				pullCoupons();
				?>
				<br/><br/>
				Would you like to create a new secure coupon for each customer?<br/>
				<input type="radio" name="hello_modi_coupon_copy" id="hello_modi_data" value="on"  <?php if(get_option('hello_modi_coupon_copy') == 'on') echo "checked=''";  ?>/> Yes<br/>
				<input type="radio" name="hello_modi_coupon_copy" id="hello_modi_data" value="off"  <?php if(get_option('hello_modi_coupon_copy') == 'off') echo "checked=''";  ?>/> No<br/>					
				<br/><br/>
				Allow users to write their own Facebook Post?
				<br/>
				<input type="radio" name="hello_modi_text_edit" id="hello_modi_text" value="on"  <?php if(get_option('hello_modi_text_edit') == 'on') echo "checked=''";  ?>/> Yes<br/>
				<input type="radio" name="hello_modi_text_edit" id="hello_modi_text" value="off"  <?php if(get_option('hello_modi_text_edit') == 'off') echo "checked=''";  ?>/> No<br/>
				<br/>
				Enter the forced Facebook Post
				<br/>
				<textarea id="hello_modi_data" name="hello_modi_data" rows="4" cols="65" style="resize: none;" ><?php echo get_option('hello_modi_data');?></textarea>

				<?php } ?>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="hello_modi_site_name" />
				<input type="hidden" name="page_options" value="hello_modi_site_url" />
				<input type="hidden" name="page_options" value="hello_modi_chosen_coupon" />
				<input type="hidden" name="page_options" value="hello_modi_coupon_copy" />
				<input type="hidden" name="page_options" value="hello_modi_text_edit" />
				<input type="hidden" name="page_options" value="hello_modi_data" />
				<input type="hidden" name="page_options" value="hello_modi_platform" />

				<p>
					<input type="submit" value="<?php _e('Save Changes') ?>" />
				</p>

			</form>

		</div>
		<?php
	}
	
	add_action('init', 'modi_setoptions');
	function modi_setoptions() {
		if(!empty($_POST['hello_modi_data'])) {
			update_option("hello_modi_data",$_POST['hello_modi_data']);
		}
		if(!empty($_POST['hello_modi_coupon_copy'])){
			update_option("hello_modi_coupon_copy",$_POST['hello_modi_coupon_copy']);
		}
		if(!empty($_POST['hello_modi_chosen_coupon'])){
			update_option("hello_modi_chosen_coupon",$_POST['hello_modi_chosen_coupon']);
		}
		if(!empty($_POST['hello_modi_site_name'])){
			update_option("hello_modi_site_name",$_POST['hello_modi_site_name']);
		}
		if(!empty($_POST['hello_modi_site_url'])){
			update_option("hello_modi_site_url",$_POST['hello_modi_site_url']);
		}
		if(!empty($_POST['hello_modi_text_edit'])){
			update_option("hello_modi_text_edit",$_POST['hello_modi_text_edit']);
		}
		if(get_option('hello_modi_platform') != $_POST['hello_modi_platform'] && !empty($_POST['hello_modi_platform'])) {
			update_option("hello_modi_chosen_coupon",'Default');
		}
		if(!empty($_POST['hello_modi_platform'])){
			update_option("hello_modi_platform",$_POST['hello_modi_platform']);
			
		}
	}
	
	function pullCoupons() {
		if(get_option('hello_modi_platform') == 'WPeCommerce') pullCouponsWPEComm();
		if(get_option('hello_modi_platform') == 'WooCommerce') pullCouponsWoo();
		
	}
	
	function pullCouponsWoo() {
		global $wpdb;
		$result = $wpdb->get_results("SELECT post_title FROM $wpdb->posts WHERE post_type = 'shop_coupon'" ) ;
		
		echo "<span style='color:red;'>*</span>Choose the coupon to use when a user successfully posts to Facebook<br/>";
		echo '<select name="hello_modi_chosen_coupon">';
		$selected = get_option('hello_modi_chosen_coupon');
		
		foreach ( $result as $row ) 
		{
			if (preg_match('/^modi/', $row->post_title)) {
				
			}
			else {
				if ($selected == $row->post_title) {
					echo '<option selected="selected" value="'.$row->post_title.'">'.$row->post_title.'</option>';
					
				} else {
					echo '<option value="'.$row->post_title.'">'.$row->post_title.'</option>';
					
				}
			}
		}
		if ($selected == 'Default') echo '<option selected="selected" value="Default">Not Set</option>';
		echo "</select>";
		
	}
	
	function pullCouponsWPEComm () {
		global $wpdb;
		
		$result = $wpdb->get_results("SELECT coupon_code FROM $wpdb->wpsc_coupon_codes" ) ;
		
		echo "Choose the coupon to use when a user successfully posts to Facebook<br/>";
		echo '<select name="hello_modi_chosen_coupon">';
		$selected = get_option('hello_modi_chosen_coupon');
		
		foreach ( $result as $row ) 
		{
			if (preg_match('/^modi/', $row->coupon_code)) {
				
			}
			else {
				if ($selected == $row->coupon_code) {
					echo '<option selected="selected" value="'.$row->coupon_code.'">'.$row->coupon_code.'</option>';
					
				} else {
					echo '<option value="'.$row->coupon_code.'">'.$row->coupon_code.'</option>';
					
				}
			}
		}
		if ($selected == 'Default') echo '<option selected="selected" value="Default">Not Set</option>';
		echo "</select>";
	}
	
	function createCode($length = 6) {
		$characters = '23456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
		$code = '';
		for ($p = 0; $p < $length; $p++) {
			
			$code .= $characters[mt_rand(0, strlen($characters)-1)];
		}
		return "modi".$code;
	}
	
	function addCouponWoo() {
		global $wpdb;
		global $table_prefix;
		$newCode = createCode();
		
		$likeCoupon = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_title = '".get_option('hello_modi_chosen_coupon')."'" );
		$IDnum = $wpdb->get_results( "SELECT ID FROM $wpdb->posts ORDER BY ID DESC" );
		$thisID = $IDnum[0]->ID + 1;
		
		$likeCouponMeta = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = ".$likeCoupon[0]->ID );
		
		$insert = $wpdb->insert(
			$wpdb->posts,
			array(
				'post_author' => $likeCoupon[0]->post_author,
				'post_title' => $newCode,
				'post_status' => $likeCoupon[0]->post_status,
				'comment_status' => $likeCoupon[0]->comment_status,
				'ping_status' => $likeCoupon[0]->ping_status,
				'post_parent' => 0,
				'post_type' => $likeCoupon[0]->post_type,
				'comment_count' => $likeCoupon[0]->comment_count,
				'menu_order' => $likeCoupon[0]->menu_order
				)
			);
		
		
		$size = count($likeCouponMeta);
		while($size >= 1) {
			$insert = $wpdb->insert(
				$wpdb->postmeta,
				array(
					'post_id' => $thisID,
					'meta_key' => $likeCouponMeta[$size]->meta_key,
					'meta_value' => $likeCouponMeta[$size]->meta_value
					)
				);
			$size--;
		}
		
		
		//echo "<pre>";
		//var_dump($likeCouponMeta);
		//echo "</pre>";
		
		
		
		return $newCode;
	}
	
	function addCouponWPEComm(){
		global $wpdb;
		global $table_prefix;
		
		$likeCoupon = $wpdb->get_results( "SELECT * FROM $wpdb->wpsc_coupon_codes WHERE coupon_code = '".get_option('hello_modi_chosen_coupon')."'" );
		echo get_option('hello_modi_chosen_coupon');
		
		$newCode = createCode();
		$start_date = date( 'Y-m-d' ) . " 00:00:00";
		$end_date = date( 'Y-m-d' ) . " 23:59:59";
		
		$insert = $wpdb->insert(
			$table_prefix.'wpsc_coupon_codes',
			array(
				'coupon_code' => $newCode,
				'value' => $likeCoupon[0]->value,
				'is-percentage' => $likeCoupon[0]->is-percentage, 
				'use-once' => 1,
				'is-used' => 0,
				'active' => 1,
				'every_product' => $likeCoupon[0]->every_product,
				'start' => $start_date,
				'expiry' => $end_date,
				'condition' => $likeCoupon[0]->condition
				),
			array(
				'%s',
				'%f',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
				)
			);
		
		
		return $newCode;
	}
	
	function currentURL() {
		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
		
	}
	
	
	?>