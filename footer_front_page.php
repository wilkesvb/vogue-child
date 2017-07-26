<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package Vogue
 */
?>
		<div class="clearboth"></div>
	</div><!-- #content -->
	
	<?php if ( get_theme_mod( 'vogue-footer-layout', false ) == 'vogue-footer-layout-standard' ) : ?>
		
		<?php get_template_part( '/templates/footers/front-page-footer-standard' ); ?>
		
	<?php else : ?>
		
		<?php get_template_part( '/templates/footers/footer-social' ); ?>
		
	<?php endif; ?>
	
</div><!-- #page -->

<?php wp_footer(); ?>



<script type="text/javascript" async="async" defer="defer" data-cfasync="false" src="https://mylivechat.com/chatinline.aspx?hccid=93234800"></script>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-41157947-2', 'auto');
  ga('send', 'pageview');

</script>

</body>
</html>
