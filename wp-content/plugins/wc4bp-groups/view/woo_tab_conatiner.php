<div id="<?php echo wc4bp_groups_manager::getSlug(); ?>" class="panel woocommerce_options_panel wc-metaboxes-wrapper">
	<?php if ( ! bp_is_active( 'groups' ) ): ?>
		<div id="message" class="inline notice woocommerce-message">
			<p><?php wc4bp_groups_manager::echo_translation( 'You need to Activate User Groups in the BuddyPress Settings.' ); ?></p>
		</div>
	<?php else: ?>
		<div class="toolbar toolbar-top">
			<?php $this->show_woo_tab_search_for_group(); ?>
		</div>
		<div class="<?php echo wc4bp_groups_manager::getSlug(); ?> wc4bp-group-container wc-metaboxes ui-sortable">
			<?php
			$added_groups = array();
			if ( ! empty( $groups ) ) {
				foreach ( $groups as $group ) {
					$added_groups[] = $group->group_id;
					$this->show_woo_tab_item_for_group( $post->ID, $group );
				}
			}
			?>
		</div>
		
		<div class="toolbar wc4bp-bottom-toolbar">
                <span class="expand-close">
                    <a href="#" class="expand_all"><?php wc4bp_groups_manager::echo_translation( 'Expand' ); ?></a> / <a href="#" class="close_all"><?php wc4bp_groups_manager::echo_translation( 'Close' ); ?></a>
                </span>
		</div>
		<input type="hidden" id="wc4bp_groups_existing_ids" value="<?php echo esc_attr( json_encode( $added_groups ) ); ?>">
		<?php
		woocommerce_wp_hidden_input( array(
			'id'    => '_wc4bp_groups_json',
			'class' => wc4bp_groups_manager::getSlug()
		) );
		?>
	<?php endif; ?>
</div>