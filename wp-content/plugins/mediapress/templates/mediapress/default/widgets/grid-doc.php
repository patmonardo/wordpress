<?php
// Exit if the file is accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * mediapress/widgets/loop.php
 * Default fallback media loop
 */
$query = mpp_widget_get_media_data( 'query' ); ?>

<?php if ( $query->have_media() ) : ?>

	<div class="mpp-container mpp-widget-container mpp-media-widget-container mpp-media-video-widget-container">
		<div class='mpp-g mpp-item-list mpp-media-list mpp-video-list'>

			<?php while ( $query->have_media() ) : $query->the_media(); ?>
				<?php $media = mpp_get_media(); ?>
				<div class="<?php mpp_media_class( 'mpp-widget-item mpp-widget-media-item ' . mpp_get_grid_column_class( 1 ) ); ?>">

					<?php do_action( 'mpp_before_media_widget_item' ); ?>

					<div class="mpp-item-meta mpp-media-meta mpp-media-widget-item-meta mpp-media-meta-top mpp-media-widget-item-meta-top">
						<?php do_action( 'mpp_media_widget_item_meta_top' ); ?>
					</div>
					<?php
					if ( ! mpp_is_doc_viewable( $media ) ) {
						$url   = mpp_get_media_src( '', $media );
						$class = 'mpp-no-lightbox';

					} else {
						$url   = mpp_get_media_permalink( $media );
						$class = '';
					}
					?>
					<div class='mpp-item-entry mpp-media-entry mpp-photo-entry'>

						<a href="<?php echo esc_url( $url ) ?>" <?php mpp_media_html_attributes( array(
							'class'            => "mpp-item-thumbnail mpp-media-thumbnail {$class}",
							'data-mpp-context' => 'widget',
						) ); ?>>
							<img src="<?php mpp_media_src( 'thumbnail' ); ?>" alt="<?php echo esc_attr( mpp_get_media_title() ); ?> "/>
						</a>

					</div>

                    <a href="<?php echo esc_url( $url ); ?>" <?php mpp_media_html_attributes(
	                    array(
		                    'class'            => "mpp-item-title mpp-media-title {$class}",
		                    'data-mpp-context' => 'widget',
	                    ) ); ?> >
                        <?php mpp_media_title(); ?>
                    </a>

					<div class="mpp-item-actions mpp-media-actions mpp-photo-actions">
						<?php mpp_media_action_links(); ?>
					</div>

					<div class="mpp-item-meta mpp-media-meta mpp-media-widget-item-meta mpp-media-meta-bottom mpp-media-widget-item-meta-bottom">
						<?php do_action( 'mpp_media_widget_item_meta' ); ?>
					</div>

					<?php do_action( 'mpp_after_media_widget_item' ); ?>

				</div>

			<?php endwhile; ?>

			<?php mpp_reset_media_data(); ?>

		</div>
	</div>
<?php endif; ?>