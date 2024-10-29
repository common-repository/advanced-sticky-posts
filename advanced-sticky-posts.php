<?php
/**
 * @package Advanced Sticky Posts
 * @version 1.1.0
 */
/*
Plugin Name: Advanced Sticky Posts
Plugin URI: http://plugins.usethedorr.com/advanced-sticky-posts/
Description: Positional sticky posts. Stick 'em where you want!
Author: Usethedorr
Version: 1.0.2
Author URI: http://usethedorr.com/
*/


//
$advsp &= new advanced_sticky_posts();
function advanced_sticky_posts() {
	return $advsp;
}

if ( is_admin() ) {
    add_action( 'load-post.php', 'advanced_sticky_posts' );
}

/** 
 * The Class
 */
class advanced_sticky_posts
{

    function advanced_sticky_posts()
    {
    	add_filter('the_content', array(&$this, 'display_content'));
		add_filter('the_excerpt', array(&$this, 'display_content'));
		add_filter('comment_post', array(&$this, 'display_content'));
		add_action('loop_start', array(&$this, 'alt_loop'));
		add_action('admin_head', array(&$this, 'admin_styles'));
		add_action('edit_post', array(&$this, 'post_advsp'));
		add_action('save_post', array(&$this, 'post_advsp'));
        add_action( 'add_meta_boxes', array( &$this, 'add_advsp_pub_box' ) );
    }
    
    function admin_styles()
    {
    	echo '<link href="'.get_bloginfo('url').'/wp-content/plugins/advanced-sticky-posts/advanced-sticky-posts.css" rel="stylesheet" type="text/stylesheet"/>';
    }

    /**
     * Adds the meta box containers
     */
    function add_advsp_pub_box()
    {
    				
		remove_meta_box('linkxfndiv', 'link', 'normal');
		
		if ( function_exists('add_meta_box') ) {
			$post_types = get_post_types('','names');
			foreach ($post_types as $post_type ) {
				if($post_type != "page") {
			 		add_meta_box('add_advsp_pub_box', __( 'Advanced Sticky Posts'), array( &$this, 'render_advsp_box_content' ), $post_type, 'normal', 'low');
			 		/* post_type stickiness coming soon! */
			 		//add_meta_box('submitdiv', __( 'Publish'), array( &$this, 'render_advsp_publish_box' ), $post_type, 'side', 'high');
			 	}
			}
	    	// Link settings
	        add_meta_box('linkxfndiv', __('Link Relationship (XFN)'), array( &$this, 'render_advsp_link_box' ), 'link', 'normal', 'high');
    	}

    }
    
    /**
     * Helper Function
     */
    function array_remove_keys($array, $keys = array()) 
    { 
	    if(empty($array) || (! is_array($array))) { 
	        return $array; 
	    } 
	    if(is_string($keys)) { 
	        $keys = explode(',', $keys); 
	    } 
	    if(! is_array($keys)) { 
	        return $array; 
	    } 
	    $assocKeys = array(); 
	    foreach($keys as $key) { 
	        $assocKeys[$key] = true; 
	    } 
	    return array_diff_key($array, $assocKeys); 
	}
	 
    /**
     * Filter the Loop
     */
    function alt_loop($content) {
    
    	global $wpdb;
    	
    	$stickies = get_option('sticky_posts');
//    	print_r($stickies);
    	$count = $content->post_count;
    	$posts = $content->posts;

    	$new_posts = $posts;
    	
    	foreach($posts as $post) {
    		if(in_array($post->ID, $stickies)) {
    			// get 
    			$indx = array_keys($posts, $post->ID);
    			
    			//print_r($post->ID);
    			
    			// the stick post
    			$sticky = array_splice($new_posts, $indx, 1);
    			
    			// insert position
    			$pos 	= get_post_meta($post->ID, '_advsp_post_pos', true);
    			
    			
    			// remove sticky post
    			$new_posts = $this->array_remove_keys($new_posts, $indx);
    			// add it back in 
    			array_splice($new_posts, $pos, 0, $sticky);
    		}
    	}
    	
    	$content->posts = $new_posts;
    	
    	return $content;  	
    }
    
    /**
     * Render Meta Box content
     */
    function render_advsp_box_content() 
    {
	    global $post;
	    //Get post_type
	    $post_type = $post->post_type;
		$post_id = $post;
		
		if (is_object($post_id)) $post_id = $post_id->ID;

	 	$content 	= get_post_meta($post_id, '_advsp_content', true);
	 	$class 		= htmlspecialchars(stripcslashes(get_post_meta($post_id, '_advsp_class', true)));
	 	$pos 		= htmlspecialchars(stripcslashes(get_post_meta($post_id, '_advsp_pos', true)));
    	
    	//Get posts published posts of this post_type	
    	$tmp = wp_count_posts($post_type);
		$post_count = $tmp->publish;
		
		$post_pos 	= get_post_meta($post_id, '_advsp_post_pos', true);
		$post_pos = (int)$post_pos;
		
    	// show only on homepage
    	$hp 		= get_post_meta($post_id, '_advsp_hp', true);
    	$checked 	= !empty($hp) ? 'checked="checked"' : '';
    	
    	// get sticky
    	$sticky 	= get_post_meta($post_id, '_advsp_sticky', true);
    	$stuck 		= '';
    	if(is_sticky()) {
    		$stuck = 'checked="checked"';
		}
		
    	?>
    	
        <input type="hidden" name="advsp_edit" value="advsp_edit"/>
        <input type="hidden" name="nonce_aioseop_edit" value="<?php echo wp_create_nonce('edit-advsp-nonce'); ?>" />
        <div class="advsp-content-wrapper">
	        <p>
	        	<label for="advsp_content"><strong><?php echo __('Enter Your Content Here:'); ?></strong> <br/><em class="howto"><?php echo __('Example: My extra content goes here! Check this out... &lt;a href="http://your-link.com"&gt;Click Here&lt;/a&gt;.'); ?></em></label>
	        </p>
	        <p>
				<textarea name="advsp_content" id="advsp_content" class="form-input text" cols="80" rows="7"><?php echo $content; ?></textarea>
	        </p>
	        <!--<p class="howto"><a href=""><?php echo __('Need help?'); ?></a></p>-->
        </div>
        <div class="advsp-options">
	        <p>
	        	<label><strong><?php echo __('Position this phrase'); ?></strong> <em class="howto">(<?php echo __('optional'); ?>)</em></label>
	        	<select id="advsp_pos" name="advsp_pos">
	        	<?php if($pos == "1") { ?>
						<option value="1" selected><?php echo __('Before the post content'); ?></option>
						<option value="2"><?php echo __('After the post content'); ?></option>
				<?php } else { ?>
						<option value="1"><?php echo __('Before the post content'); ?></option>
						<option value="2" selected><?php echo __('After the post content'); ?></option>
				<?php } ?>
	        	</select>
	        </p>
	        <p>
	        	<label for="advsp_class"><strong><?php echo __('Phrase class:'); ?></strong> <em class="howto">(<?php echo __('optional'); ?>)</em></label>
	        	<input type="text" id="advsp_class" name="advsp_class" class="form-input text" value="<?php echo $class; ?>"/>
	        </p>
	        <p>
		        <label for="advsp_homepage"><strong><?php echo __('Show phrase only on the homepage:'); ?></strong> <input type="checkbox" id="advsp_hp" name="advsp_hp" class="advsp-checkbox" <?php echo $checked; ?>/></label>
	        </p>
	        <?php if($post_type == 'post') { ?>
	        <p>
        		<label for="advsp_sticky"><strong><?php echo __('Make this Post sticky on the homepage:'); ?></strong> <input type="checkbox" id="advsp_sticky" name="advsp_sticky" class="advsp-checkbox" <?php echo $stuck; ?>/></label>
       		</p>
       		<p id="advsp_post_pos_wrapper" <?php if(!is_sticky()) { ?>style="display: none;" <?php } ?>>
	        	<label for="advsp_post_pos"><strong><?php echo __('Position this post:'); ?></strong> <em class="howto">(<?php echo __('optional'); ?>)</em></label>
	        	<select id="advsp_post_pos" name="advsp_post_pos">
	        	<?php for($i = 0; $i < $post_count; $i++) { ?>
						<option value="<?php echo $i; ?>" <?php if($i == $post_pos) { ?> selected <?php } ?>>
						<?php echo $i + 1; ?>
						</option>
				<?php } ?>
	        	</select>
	        </p>
			<?php } ?>
        </div>
        <br class="clear"/>
        
        
        <script type="text/javascript">
    		jQuery('#advsp_sticky').click(function() {
    			jQuery('#sticky').attr("checked", jQuery(this).attr("checked"));
    			jQuery('#advsp_post_pos_wrapper').toggle();
    		});
    		jQuery('#sticky').click(function() {
    			jQuery('#advsp_sticky').attr("checked", jQuery(this).attr("checked"));
    			jQuery('#advsp_post_pos_wrapper').toggle();
    		});
    	</script>
        
    <?php
    }
    
    
    /** 
     * Save Data
     */
    function post_advsp($id) {
	    $awmp_edit = $_POST['advsp_edit'];
		$nonce = $_POST['nonce_aioseop_edit'];

	    if (isset($awmp_edit) && !empty($awmp_edit) && wp_verify_nonce($nonce, 'edit-advsp-nonce')) {
	    	
	    	// vars
		    $post_pos = $_POST["advsp_post_pos"];
		    $content = $_POST["advsp_content"];
		    $sticky = $_POST['advsp_sticky'];
		    $class = $_POST["advsp_class"];
		    $pos = $_POST["advsp_pos"];
		   	$hp = $_POST['advsp_hp'];
		    
		    // delete
			delete_post_meta($id, '_advsp_post_pos');		    
		    delete_post_meta($id, '_advsp_content');
		    delete_post_meta($id, '_advsp_sticky');
		    delete_post_meta($id, '_advsp_class');
		    delete_post_meta($id, '_advsp_pos');
		    delete_post_meta($id, '_advsp_hp');
			
			// set vars
		    if (isset($content) && !empty($content)) {
			    add_post_meta($id, '_advsp_content', $content);
		    }
		    if (isset($class) && !empty($class)) {
			    add_post_meta($id, '_advsp_class', $class);
		    }
		    if (isset($pos) && !empty($pos)) {
			    add_post_meta($id, '_advsp_pos', $pos);
		    }
			if (isset($hp) && !empty($hp)) {
			    add_post_meta($id, '_advsp_hp', $hp);
		    }
		    if (isset($sticky) && !empty($sticky)) {
			    add_post_meta($id, '_advsp_sticky', $sticky);
		    }
		    if (isset($post_pos) && !empty($post_pos)) {
			    add_post_meta($id, '_advsp_post_pos', $post_pos);
		    }
	    }
	}
	
	/** 
     * Display Content
     */
	function display_content($content)
	{
		global $id;
		
		$phrase = get_post_meta($id, '_advsp_content', true);
		$sticky = get_post_meta($id, '_advsp_sticky', true);
		$class = get_post_meta($id, '_advsp_class', true);
		$pos = get_post_meta($id, '_advsp_pos', true);
		$hp = get_post_meta($id, '_advsp_hp', true);
		
		
		$html = '<p';
		
		if(!empty($class))
			$html .= ' class="' . $class . '"';

		$html .= '>' . $phrase;
		$html .= '</p> ';
		
		if( $pos == "1") {
			$ret = $html.$content;
		} else {
			$ret = $content.$html;
		}
		
		//print_r(get_option('sticky_posts'));
		
		if(is_single()) {
			if(empty($hp)) {
				return $ret;
			}else {
				return $content;
			}
		}else {
			return $ret;
		}
	}
	
	/**
	 * Display xfn form fields.
	 */
	function render_advsp_link_box($link) {
	?>
	<table class="editform" style="width: 100%;" cellspacing="2" cellpadding="5">
		<tr>
			<th style="width: 20%;" scope="row"><label for="link_rel"><?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('rel:') ?></label></th>
			<td style="width: 80%;"><input type="text" name="link_rel" id="link_rel" size="50" value="<?php echo ( isset( $link->link_rel ) ? esc_attr($link->link_rel) : ''); ?>" /></td>
		</tr>
		<tr>
			<td colspan="2">
				<table cellpadding="3" cellspacing="5" class="form-table">
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('identity') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('identity') ?> </span></legend>
							<label for="me">
							<input type="checkbox" name="identity" value="me" id="me" <?php xfn_check('identity', 'me'); ?> />
							<?php _e('another web address of mine') ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('friendship') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('friendship') ?> </span></legend>
							<label for="contact">
							<input class="valinp" type="radio" name="friendship" value="contact" id="contact" <?php xfn_check('friendship', 'contact'); ?> /> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('contact') ?></label>
							<label for="acquaintance">
							<input class="valinp" type="radio" name="friendship" value="acquaintance" id="acquaintance" <?php xfn_check('friendship', 'acquaintance'); ?> />  <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('acquaintance') ?></label>
							<label for="friend">
							<input class="valinp" type="radio" name="friendship" value="friend" id="friend" <?php xfn_check('friendship', 'friend'); ?> /> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('friend') ?></label>
							<label for="friendship">
							<input name="friendship" type="radio" class="valinp" value="" id="friendship" <?php xfn_check('friendship'); ?> /> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('none') ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('physical') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('physical') ?> </span></legend>
							<label for="met">
							<input class="valinp" type="checkbox" name="physical" value="met" id="met" <?php xfn_check('physical', 'met'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('met') ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('professional') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('professional') ?> </span></legend>
							<label for="co-worker">
							<input class="valinp" type="checkbox" name="professional" value="co-worker" id="co-worker" <?php xfn_check('professional', 'co-worker'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('co-worker') ?></label>
							<label for="colleague">
							<input class="valinp" type="checkbox" name="professional" value="colleague" id="colleague" <?php xfn_check('professional', 'colleague'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('colleague') ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('geographical') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('geographical') ?> </span></legend>
							<label for="co-resident">
							<input class="valinp" type="radio" name="geographical" value="co-resident" id="co-resident" <?php xfn_check('geographical', 'co-resident'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('co-resident') ?></label>
							<label for="neighbor">
							<input class="valinp" type="radio" name="geographical" value="neighbor" id="neighbor" <?php xfn_check('geographical', 'neighbor'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('neighbor') ?></label>
							<label for="geographical">
							<input class="valinp" type="radio" name="geographical" value="" id="geographical" <?php xfn_check('geographical'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('none') ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('family') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('family') ?> </span></legend>
							<label for="child">
							<input class="valinp" type="radio" name="family" value="child" id="child" <?php xfn_check('family', 'child'); ?>  />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('child') ?></label>
							<label for="kin">
							<input class="valinp" type="radio" name="family" value="kin" id="kin" <?php xfn_check('family', 'kin'); ?>  />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('kin') ?></label>
							<label for="parent">
							<input class="valinp" type="radio" name="family" value="parent" id="parent" <?php xfn_check('family', 'parent'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('parent') ?></label>
							<label for="sibling">
							<input class="valinp" type="radio" name="family" value="sibling" id="sibling" <?php xfn_check('family', 'sibling'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('sibling') ?></label>
							<label for="spouse">
							<input class="valinp" type="radio" name="family" value="spouse" id="spouse" <?php xfn_check('family', 'spouse'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('spouse') ?></label>
							<label for="family">
							<input class="valinp" type="radio" name="family" value="" id="family" <?php xfn_check('family'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('none') ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('romantic') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('romantic') ?> </span></legend>
							<label for="muse">
							<input class="valinp" type="checkbox" name="romantic" value="muse" id="muse" <?php xfn_check('romantic', 'muse'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('muse') ?></label>
							<label for="crush">
							<input class="valinp" type="checkbox" name="romantic" value="crush" id="crush" <?php xfn_check('romantic', 'crush'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('crush') ?></label>
							<label for="date">
							<input class="valinp" type="checkbox" name="romantic" value="date" id="date" <?php xfn_check('romantic', 'date'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('date') ?></label>
							<label for="romantic">
							<input class="valinp" type="checkbox" name="romantic" value="sweetheart" id="romantic" <?php xfn_check('romantic', 'sweetheart'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('sweetheart') ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('No Follow') ?> </th>
						<td><fieldset><legend class="screen-reader-text"><span> <?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('No Follow') ?> </span></legend>
							<label for="nofollow">
							<input class="valinp" type="checkbox" name="advsp" value="nofollow" id="nofollow" <?php xfn_check('advsp', 'nofollow'); ?> />
							<?php /* translators: xfn: http://gmpg.org/xfn/ */ _e('nofollow') ?></label>
						</fieldset></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<p><?php _e('If the link is to a person, you can specify your relationship with them using the above form. If you would like to learn more about the idea check out <a href="http://gmpg.org/xfn/">XFN</a>.'); ?></p>
	<?php
	}
	/* Coming Soon! 
	function render_advsp_publish_box($post) {
			global $action;
		
			$post_type = $post->post_type;
			$post_type_object = get_post_type_object($post_type);
			$can_publish = current_user_can($post_type_object->cap->publish_posts);
		?>
		<div class="submitbox" id="submitpost">
		
		<div id="minor-publishing">
		
		<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
		<div style="display:none;">
		<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
		</div>
		
		<div id="minor-publishing-actions">
		<div id="save-action">
		<?php if ( 'publish' != $post->post_status && 'future' != $post->post_status && 'pending' != $post->post_status )  { ?>
		<input <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save Draft'); ?>" tabindex="4" class="button button-highlighted" />
		<?php } elseif ( 'pending' == $post->post_status && $can_publish ) { ?>
		<input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save as Pending'); ?>" tabindex="4" class="button button-highlighted" />
		<?php } ?>
		<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="draft-ajax-loading" alt="" />
		</div>
		
		<div id="preview-action">
		<?php
		if ( 'publish' == $post->post_status ) {
			$preview_link = esc_url( get_permalink( $post->ID ) );
			$preview_button = __( 'Preview Changes' );
		} else {
			$preview_link = get_permalink( $post->ID );
			if ( is_ssl() )
				$preview_link = str_replace( 'http://', 'https://', $preview_link );
			$preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
			$preview_button = __( 'Preview' );
		}
		?>
		<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php echo $preview_button; ?></a>
		<input type="hidden" name="wp-preview" id="wp-preview" value="" />
		</div>
		
		<div class="clear"></div>
		</div><?php // /minor-publishing-actions ?>
		
		<div id="misc-publishing-actions">
		
		<div class="misc-pub-section<?php if ( !$can_publish ) { echo ' misc-pub-section-last'; } ?>"><label for="post_status"><?php _e('Status:') ?></label>
		<span id="post-status-display">
		<?php
		switch ( $post->post_status ) {
			case 'private':
				_e('Privately Published');
				break;
			case 'publish':
				_e('Published');
				break;
			case 'future':
				_e('Scheduled');
				break;
			case 'pending':
				_e('Pending Review');
				break;
			case 'draft':
			case 'auto-draft':
				_e('Draft');
				break;
		}
		?>
		</span>
		<?php if ( 'publish' == $post->post_status || 'private' == $post->post_status || $can_publish ) { ?>
		<a href="#post_status" <?php if ( 'private' == $post->post_status ) { ?>style="display:none;" <?php } ?>class="edit-post-status hide-if-no-js" tabindex='4'><?php _e('Edit') ?></a>
		
		<div id="post-status-select" class="hide-if-js">
		<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ('auto-draft' == $post->post_status ) ? 'draft' : $post->post_status); ?>" />
		<select name='post_status' id='post_status' tabindex='4'>
		<?php if ( 'publish' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php _e('Published') ?></option>
		<?php elseif ( 'private' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php _e('Privately Published') ?></option>
		<?php elseif ( 'future' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e('Scheduled') ?></option>
		<?php endif; ?>
		<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e('Pending Review') ?></option>
		<?php if ( 'auto-draft' == $post->post_status ) : ?>
		<option<?php selected( $post->post_status, 'auto-draft' ); ?> value='draft'><?php _e('Draft') ?></option>
		<?php else : ?>
		<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e('Draft') ?></option>
		<?php endif; ?>
		</select>
		 <a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e('OK'); ?></a>
		 <a href="#post_status" class="cancel-post-status hide-if-no-js"><?php _e('Cancel'); ?></a>
		</div>
		
		<?php } ?>
		</div><?php // /misc-pub-section ?>
		
		<div class="misc-pub-section " id="visibility">
		<?php _e('Visibility:'); ?> <span id="post-visibility-display"><?php
		
		if ( 'private' == $post->post_status ) {
			$post->post_password = '';
			$visibility = 'private';
			$visibility_trans = __('Private');
		} elseif ( !empty( $post->post_password ) ) {
			$visibility = 'password';
			$visibility_trans = __('Password protected');
		} elseif ( $post_type == 'post' && is_sticky( $post->ID ) ) {
			$visibility = 'public';
			$visibility_trans = __('Public, Sticky');
		} else {
			$visibility = 'public';
			$visibility_trans = __('Public');
		}
		
		echo esc_html( $visibility_trans ); ?></span>
		<?php if ( $can_publish ) { ?>
		<a href="#visibility" class="edit-visibility hide-if-no-js"><?php _e('Edit'); ?></a>
		
		<div id="post-visibility-select" class="hide-if-js">
		<input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo esc_attr($post->post_password); ?>" />
		<?php if ($post_type == 'post'): ?>
		<input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked(is_sticky($post->ID)); ?> />
		<?php endif; ?>
		<input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo esc_attr( $visibility ); ?>" />
		
		
		<input type="radio" name="visibility" id="visibility-radio-public" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="visibility-radio-public" class="selectit"><?php _e('Public'); ?></label><br />
		<?php if ($post->post_type != 'page'): ?>
		<span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked(is_sticky($post->ID)); ?> tabindex="4" /> <label for="sticky" class="selectit"><?php _e('Stick this post to the front page') ?></label><br /></span>
		<?php endif; ?>
		<input type="radio" name="visibility" id="visibility-radio-password" value="password" <?php checked( $visibility, 'password' ); ?> /> <label for="visibility-radio-password" class="selectit"><?php _e('Password protected'); ?></label><br />
		<span id="password-span"><label for="post_password"><?php _e('Password:'); ?></label> <input type="text" name="post_password" id="post_password" value="<?php echo esc_attr($post->post_password); ?>" /><br /></span>
		<input type="radio" name="visibility" id="visibility-radio-private" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="visibility-radio-private" class="selectit"><?php _e('Private'); ?></label><br />
		
		<p>
		 <a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php _e('OK'); ?></a>
		 <a href="#visibility" class="cancel-post-visibility hide-if-no-js"><?php _e('Cancel'); ?></a>
		</p>
		</div>
		<?php } ?>
		
		</div><?php // /misc-pub-section ?>
		
		<?php
		// translators: Publish box date formt, see http://php.net/date
		$datef = __( 'M j, Y @ G:i' );
		if ( 0 != $post->ID ) {
			if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
				$stamp = __('Scheduled for: <b>%1$s</b>');
			} else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
				$stamp = __('Published on: <b>%1$s</b>');
			} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
				$stamp = __('Publish <b>immediately</b>');
			} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
				$stamp = __('Schedule for: <b>%1$s</b>');
			} else { // draft, 1 or more saves, date specified
				$stamp = __('Publish on: <b>%1$s</b>');
			}
			$date = date_i18n( $datef, strtotime( $post->post_date ) );
		} else { // draft (no saves, and thus no date specified)
			$stamp = __('Publish <b>immediately</b>');
			$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
		}
		
		if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
		<div class="misc-pub-section curtime misc-pub-section-last">
			<span id="timestamp">
			<?php printf($stamp, $date); ?></span>
			<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php _e('Edit') ?></a>
			<div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'),1,4); ?></div>
		</div><?php // /misc-pub-section ?>
		<?php endif; ?>
		
		<?php do_action('post_submitbox_misc_actions'); ?>
		</div>
		<div class="clear"></div>
		</div>
		
		<div id="major-publishing-actions">
		<?php do_action('post_submitbox_start'); ?>
		<div id="delete-action">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) ) {
			if ( !EMPTY_TRASH_DAYS )
				$delete_text = __('Delete Permanently');
			else
				$delete_text = __('Move to Trash');
			?>
		<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a><?php
		} ?>
		</div>
		
		<div id="publishing-action">
		<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="ajax-loading" alt="" />
		<?php
		if ( !in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) {
			if ( $can_publish ) :
				if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Schedule') ?>" />
				<?php submit_button( __( 'Schedule' ), 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' ) ); ?>
		<?php	else : ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>" />
				<?php submit_button( __( 'Publish' ), 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' ) ); ?>
		<?php	endif;
			else : ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />
				<?php submit_button( __( 'Submit for Review' ), 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' ) ); ?>
		<?php
			endif;
		} else { ?>
				<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
				<input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update') ?>" />
		<?php
		} ?>
		</div>
		<div class="clear"></div>
		</div>
		</div>
		
		<?php
	} //phew!	
	*/
}
?>