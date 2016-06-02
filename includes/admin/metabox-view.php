<div id="rcp-metabox-field-restrict-by" class="rcp-metabox-field">
	<p><strong><?php _e( 'Member access options', 'rcp' ); ?></strong></p>
	<p>
		<?php _e( 'Select who should have access to this content.', 'rcp' ); ?>
		<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" data-tip="<?php _e( '<strong>Subscription level</strong>: a subscription level refers to a membership option. For example, you might have a Gold, Silver, and Bronze membership level. <br/><strong>Access Level</strong>: refers to a tiered system where a member\'s ability to view content is determined by the access level assigned to their account. A member with an access level of 5 can view content assigned to access levels of 5 and lower.', 'rcp' ); ?>"></span>
	</p>
	<p>
		<select id="rcp-restrict-by">
			<option value="subscription-level"><?php _e( 'Members of subscription level(s)', 'rcp' ); ?></option>
			<option value="access-level"><?php _e( 'Members with an access level', 'rcp' ); ?></option>
		</select>
	</p>
</div>
<div id="rcp-metabox-field-levels" class="rcp-metabox-field">
	<label for="rcp_subscription_level_any">
		<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_any" value="any" checked="checked"/>
		&nbsp;<?php _e( 'Members of any subscription level(s)', 'rcp' ); ?><br/>
	</label>
	<label for="rcp_subscription_level_any_paid">
		<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_any_paid" value="any-paid"/>
		&nbsp;<?php _e( 'Members of any non-free subscription level(s)', 'rcp' ); ?><br/>
	</label>
	<label for="rcp_subscription_level_specific">
		<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_specific" value="specific"/>
		&nbsp;<?php _e( 'Members of specific subscription levels', 'rcp' ); ?><br/>
	</label>
	<p class="rcp-subscription-levels" style="display:none;">
		<?php foreach( rcp_get_subscription_levels() as $level ) : ?>
			<label for="rcp_subscription_level_<?php echo $level->id; ?>">
				<input type="checkbox" name="rcp_subscription_level[]" class="rcp_subscription_level" id="rcp_subscription_level_<?php echo $level->id; ?>" value="<?php echo esc_attr( $level->id ); ?>" disabled="disabled" data-price="<?php echo esc_attr( $level->price ); ?>"/>
				&nbsp;<?php echo $level->name; ?><br/>
			</label>
		<?php endforeach; ?>
	</p>
</div>
<div id="rcp-metabox-field-access-levels" class="rcp-metabox-field" style="display:none;">
	<p>
		<select name="rcp_access_level" id="rcp-access-level-field">
			<?php foreach( rcp_get_access_levels() as $key => $access_level ) : ?>
				<option id="rcp_access_level<?php echo $key; ?>" value="<?php echo esc_attr( $key ); ?>"><?php printf( __( '%s and higher', 'rcp' ), $key ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
</div>
<div id="rcp-metabox-field-options" class="rcp-metabox-field">

	<p><strong><?php _e( 'Additional options', 'rcp' ); ?></strong></p>
	<p>
		<label for="rcp-show-excerpt">
			<input type="checkbox" name="rcp_show_excerpt" id="rcp-show-excerpt" value="1" checked="checked"/>
			<?php _e( 'Show excerpt to members without access to this content.', 'rcp' ); ?>
		</label>
	</p>
	<p>
		<label for="rcp-hide-in-feed">
			<input type="checkbox" name="rcp_hide_from_feed" id="rcp-hide-in-feed" value="1"/>
			<?php _e( 'Hide this content and excerpt from RSS feeds.', 'rcp' ); ?>
		</label>
	</p>
	<p>
		<select name="rcp_user_level" id="rcp-user-level-field">
			<?php foreach( array( 'All', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber' ) as $role ) : ?>
				<option value="<?php echo esc_attr( $role ); ?>"><?php echo $role; ?></option>
			<?php endforeach; ?>
		</select>
		<span><?php _e( 'Require member to have capabilities from this user role or higher.', 'rcp' ); ?></span>
	</p>
	<p>
		<?php printf(
			__( 'Optionally use [restrict paid="true"] ... [/restrict] shortcode to restrict partial content. %sView documentation for additional options%s.', 'rcp' ),
			'<a href="' . esc_url( 'http://docs.pippinsplugins.com/article/36-restricting-post-and-page-content' ) . '" target="_blank">',
			'</a>'
		); ?>
	</p>
</div>