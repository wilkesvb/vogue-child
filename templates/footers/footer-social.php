<footer id="colophon" class="site-footer site-footer-social" role="contentinfo">
	
	<div class="site-footer-icons">
        <div class="site-container">
        	
        	<?php if ( ! get_theme_mod( 'vogue-footer-hide-social' ) ) : ?>
	            
	            <?php
				if ( get_theme_mod( 'vogue-social-email', false ) ) :
				    echo '<a href="' . esc_url( 'mailto:' . antispambot( get_theme_mod( 'vogue-social-email' ), 1 ) ) . '" title="' . __( 'Send Us an Email', 'vogue' ) . '" class="footer-social-icon footer-social-email"><i class="fa fa-envelope-o"></i></a>';
				endif;

				if ( get_theme_mod( 'vogue-social-skype', false ) ) :
				    echo '<a href="skype:' . esc_html( get_theme_mod( 'vogue-social-skype' ) ) . '?userinfo" title="' . __( 'Contact Us on Skype', 'vogue' ) . '" class="footer-social-icon footer-social-skype"><i class="fa fa-skype"></i></a>';
				endif;

				if ( get_theme_mod( 'vogue-social-linkedin', false ) ) :
				    echo '<a href="' . esc_url( get_theme_mod( 'vogue-social-linkedin' ) ) . '" target="_blank" title="' . __( 'Find Us on LinkedIn', 'vogue' ) . '" class="footer-social-icon footer-social-linkedin"><i class="fa fa-linkedin"></i></a>';
				endif;

				if ( get_theme_mod( 'vogue-social-tumblr', false ) ) :
				    echo '<a href="' . esc_url( get_theme_mod( 'vogue-social-tumblr' ) ) . '" target="_blank" title="' . __( 'Find Us on Tumblr', 'vogue' ) . '" class="footer-social-icon footer-social-tumblr"><i class="fa fa-tumblr"></i></a>';
				endif;

				if ( get_theme_mod( 'vogue-social-flickr', false ) ) :
				    echo '<a href="' . esc_url( get_theme_mod( 'vogue-social-flickr' ) ) . '" target="_blank" title="' . __( 'Find Us on Flickr', 'vogue' ) . '" class="footer-social-icon footer-social-flickr"><i class="fa fa-flickr"></i></a>';
				endif;
				
				
				if ( !get_theme_mod( 'vogue-social-email', false ) && 
					!get_theme_mod( 'vogue-social-skype', false ) && 
					!get_theme_mod( 'vogue-social-linkedin', false ) && 
					!get_theme_mod( 'vogue-social-tumblr', false ) && 
					!get_theme_mod( 'vogue-social-flickr', false ) ) {
					echo '<a class="footer-social-icon footer-social-email"><i class="fa fa-envelope-o"></i></a><a class="footer-social-icon footer-social-skype"><i class="fa fa-skype"></i></a><a class="footer-social-icon footer-social-linkedin"><i class="fa fa-linkedin"></i><span>' . __( '+ more', 'vogue' ) . '</span></a>';
				} ?>
			
			<?php endif; ?>
			
        	<div class="site-footer-social-ad"><i class="fa fa-map-marker"></i> <?php echo wp_kses_post( get_theme_mod( 'vogue-website-site-add', __( 'Cape Town, South Africa', 'vogue' ) ) ) ?>
        	
        </div>
    </div>
    
</footer>

<?php if ( get_theme_mod( 'vogue-footer-bottombar', false ) == 0 ) : ?>
	
	<div class="site-footer-bottom-bar">
	
		<div class="site-container">
			
			<?php do_action ( 'vogue_footer_bottombar_left' ); ?>
			
	        <?php wp_nav_menu( array( 'theme_location' => 'footer-bar','container' => false, 'fallback_cb' => false, 'depth'  => 1 ) ); ?>
	        
	        <?php do_action ( 'vogue_footer_bottombar_right' ); ?>
                
	    </div>
		
        <div class="clearboth"></div>
	</div>
	
<?php endif; ?>