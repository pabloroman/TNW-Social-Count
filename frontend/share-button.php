<?php

	function tnwsc_frontend_init()
	{
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'tnswc-js', plugins_url( 'js/tnwsc.js', __FILE__ ), array('jquery'), '1.0' );
		wp_enqueue_style( 'tnswc-css', plugins_url( 'css/tnwsc.css', __FILE__ ) , array(), '1.0' );
	}

    function tnwsc_button($network, $permalink, $title = '', $excerpt = '') 
    {        
        switch($network) {			
			case 'ycombinator':
				$url = 'http://news.ycombinator.com/submitlink?'.http_build_query(array(
                	'op' => 'basic',
                    'u' => $permalink,
                    't' => $title));
			break;
			
            case 'instapaper':
                $url = 'http://www.instapaper.com/hello2?'.http_build_query(array(
                    'url' => $permalink,
                    'title' => $title,
                    'description' => $excerpt));
            break;

            case 'reddit':
                $url = 'http://www.reddit.com/submit?'.http_build_query(array(
                    'url' => $permalink,
                    'title' => $title));
            break;
		
            case 'readitlater':
                $url = 'https://readitlaterlist.com/save?'.http_build_query(array(
                    'url' => $permalink,
                    'title' => $title));
            break;
            
            case 'facebook':
            	$url = 'https://www.facebook.com/sharer/sharer.php?'.http_build_query(array(
                    'u' => $permalink));            
            break;
            
            case 'googleplus':
            	$url = 'https://plus.google.com/share?url='.$permalink;
            break;
            
            case 'twitter':
            	$url = 'https://twitter.com/intent/tweet?text='.rawurlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')).'&'.http_build_query(array(
                    'url' => $permalink,
                    'via' => $via));
            break;
            
            case 'linkedin':
            	$url = 'http://www.linkedin.com/shareArticle?'.http_build_query(array(
            		'mini' => 'true',
                    'url' => $permalink,
                    'title' => $title,
                    'summary' => $excerpt));
            break;
                    
            case 'digg':
				$url = 'http://digg.com/submit?'.http_build_query(array(
                    'url' => $permalink,
                    'title' => $title));
            break;
            case 'stumbleupon':
            	$url = 'http://www.stumbleupon.com/submit?'.http_build_query(array(
                    'url' => $permalink,
                    'title' => $title));
            break;
           
        }
?>
        <a href="<?php echo $url; ?>" class="popup-link icon <?php echo $network; ?>"></a>
<?php
    }
    



	function render_tnwsc_button() {
	
		global $post;
		$twitter_count = get_post_meta($post->ID, 'tnwsc_twitter', true);
		$facebook_count = get_post_meta($post->ID, 'tnwsc_facebook', true);
		$linkedin_count = get_post_meta($post->ID, 'tnwsc_linkedin', true);
		$googleplus_count = get_post_meta($post->ID, 'tnwsc_google', true);
		
		$total_shares = (int) ($twitter_count + $facebook_count + $linkedin_count + $googleplus_count);
		
		$the_title = get_the_title();
		$the_permalink = get_permalink();
		$the_excerpt = get_the_excerpt();
?>
	
<div class="toolbar-social">
            
    <div class="social-widget">
		<div id="sharing-widget">
           	<a class="button icon-share"><?php _e('Share'); ?></a>
           	<span class="share-count"><?php echo $total_shares; ?></span>
		</div>
    </div>

	<div class="tooltip-wrapper" style="display: none">
        <div class="arrow"></div>
        <div id="tooltip-close"></div>
        <div class="tooltip">
            <table>
                <tr>
                	<td><div><?php tnwsc_button('twitter', $the_permalink, $the_title); ?><?php echo ($twitter_count > 0) ? '<div class="digit">'.$twitter_count.'</div>' : ''; ?></div></td>
                	<td><div><?php tnwsc_button('facebook', $the_permalink, $the_title); ?><?php echo ($facebook_count > 0) ? '<div class="digit">'.$facebook_count.'</div>' : ''; ?></div></td>
                	<td><div><?php tnwsc_button('googleplus', $the_permalink, $the_title); ?><?php echo ($googleplus_count > 0) ? '<div class="digit">'.$googleplus_count.'</div>' : ''; ?></div></td>
                	<td><div><?php tnwsc_button('linkedin', $the_permalink, $the_title); ?><?php echo ($linkedin_count > 0) ? '<div class="digit">'.$linkedin_count.'</div>' : ''; ?></div></td>
                    
                </tr>
                <tr>
                	<td><div><?php tnwsc_button('reddit', $the_permalink, $the_title); ?></div></td>
                	<td><div><?php tnwsc_button('digg', $the_permalink, $the_title); ?></div></td>
                	<td><div><?php tnwsc_button('stumbleupon', $the_permalink, $the_title); ?></div></td>
                    <td><div><?php tnwsc_button('ycombinator', $the_permalink, $the_title); ?></div></td>
                </tr>
                <tr>
                	<td colspan="4" class="separator"></td>
                </tr>
                <tr>
                	<td colspan="4" align="left" valign="top" class="read-later"><?php _e('Read later:'); ?>
                	<?php tnwsc_button('instapaper', $the_permalink, $the_title, $the_excerpt); ?>
                    <?php tnwsc_button('readitlater', $the_permalink, $the_title); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" align="left" valign="top" class="share-link">
                    	<label class="hidden" for="cp-link"><?php _e('Copy-paste link'); ?></label>
                    	<input type="text" value="<?php echo $the_permalink; ?>" id="cp-link" onclick="this.select();" />
                    </td>
                </tr>
            </table>
        </div>
    </div>         
</div>

<?php } ?>