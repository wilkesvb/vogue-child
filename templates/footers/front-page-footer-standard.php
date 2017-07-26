<footer id="colophon" class="site-footer site-footer-standard" role="contentinfo">
	
	<!-- removed footer widget area for front page -->

    <?php if ( get_theme_mod( 'vogue-footer-bottombar', false ) == 0 ) : ?>
		
		<div class="site-footer-bottom-bar <?php echo ( get_theme_mod( 'vogue-header-layout' ) == 'vogue-header-layout-two' ) ? sanitize_html_class( 'layout-circles' ) : sanitize_html_class( 'layout-plain' ); ?>">
		
			<div class="site-container">

				<div class="site-footer-bottom-bar-left">Copyright <?php echo date('Y') ?> <a href="/" style="font-size: inherit; color: #8FC97B;">My Vacay Valet</a>&nbsp;&nbsp;A Division of SHINE Enterprises</div>

				
		        
		    </div>
			
	        <div class="clearboth"></div>
		</div>
		
	<?php endif; ?>
	
</footer>