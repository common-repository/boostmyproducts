<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 if(isset($_POST['silicawt_hidden']) && $_POST['silicawt_hidden'] == 'Y') {
 		$nonce = check_admin_referer( 'silic-bmp' );
 		if($nonce)
 		{
 			$error = false;
	        //Form data sent
	        $silicawt_webservice_url = sanitize_text_field($_POST['silicawt_webservice_url']);
	        if(!preg_match("/http:\/\//",$silicawt_webservice_url))
	        {
	        	$error = true;
	        	?>
		        <div class="error"><p><strong><?php _e('L\'url doit contenir http://','boostmyproducts' ); ?></strong></p></div>
		        <?php	
	        }
	        else
	        {
	        	update_option('silicawt_webservice_url', $silicawt_webservice_url);
	        }
	        
	        $silicawt_client_code = sanitize_text_field($_POST['silicawt_client_code']);
	        update_option('silicawt_client_code', $silicawt_client_code);
	         
	        $silicawt_client_pwd = sanitize_text_field($_POST['silicawt_client_pwd']);
	        update_option('silicawt_client_pwd', $silicawt_client_pwd);
	        
	        if(!$error)
	        {
	        ?>
	        <div class="updated"><p><strong><?php _e('Options saved.','boostmyproducts' ); ?></strong></p></div>
	        <?php
	        }
 		}
 		else {
	        //Normal page display
	        $silicawt_webservice_url = get_option('silicawt_webservice_url');
	        $silicawt_client_code = get_option('silicawt_client_code');
	        $silicawt_client_pwd = get_option('silicawt_client_pwd');
	    }
    } else {
        //Normal page display
        $silicawt_webservice_url = get_option('silicawt_webservice_url');
        $silicawt_client_code = get_option('silicawt_client_code');
        $silicawt_client_pwd = get_option('silicawt_client_pwd');
    }

$url = str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);


?>
<div class="wrap">
    <?php    echo "<h2>" . __( 'Boostmyproducts Options', 'boostmyproducts' ) . "</h2>"; ?>
     
    <form name="silicawt_form" method="post" action="<?php echo $url ?>">
        <input type="hidden" name="silicawt_hidden" value="Y">
        <?=wp_nonce_field( 'silic-bmp');?>
        <?php    echo "<h4>" . __( 'Settings', 'boostmyproducts' ) . "</h4>"; ?>
        <p><?php _e("Url of webservice","boostmyproducts" ); ?> : <input type="text" name="silicawt_webservice_url" value="<?php echo $silicawt_webservice_url; ?>" size="20"></p>
        <p><?php _e("Your client code","boostmyproducts" ); ?> : <input type="text" name="silicawt_client_code" value="<?php echo $silicawt_client_code; ?>" size="20"></p>
        <p><?php _e("Your identification key","boostmyproducts" ); ?> : <input type="text" name="silicawt_client_pwd" value="<?php echo $silicawt_client_pwd; ?>" size="20"></p>
         
     
        <p class="submit">
        <input type="submit" name="Submit" value="<?php _e('Validate', 'boostmyproducts' ) ?>" />
        </p>
    </form>
</div>
<?php
?>