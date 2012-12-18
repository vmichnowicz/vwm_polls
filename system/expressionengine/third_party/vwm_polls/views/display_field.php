<input type="hidden" name="<?php echo $field_name; ?>" id="<?php echo $field_name; ?>" value="<?php echo $data['json']; ?>" />

<div id="vwm_polls_tabs_<?php echo $field_id; ?>">
	
	<!-- Tabs -->
	<ul class="tabs">
		<li><a href="#vwm_polls_options_container_<?php echo $field_id; ?>">Options</a></li>
		<li><a href="#vwm_polls_settings_container_<?php echo $field_id; ?>">Settings</a></li>
		<?php if ($total_votes): ?>
			<li><a href="#vwm_polls_results_container_<?php echo $field_id; ?>">Results</a></li>
		<?php endif; ?>
	</ul>

	<!-- Poll Options -->
	<div id="vwm_polls_options_container_<?php echo $field_id; ?>">

		<h3><?php echo lang('poll_options'); ?></h3>

		<table class="mainTable" id="vwm_polls_options_<?php echo $field_id; ?>" cellpadding="0px" cellspacing="0px" border="0px">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th><?php echo lang('option_color'); ?></th>
					<th><?php echo lang('option_type'); ?></th>
					<th><?php echo lang('option_text'); ?></th>
					<th>&nbsp;</td>
				</tr>
			</thead>
			<tfoot class="vwm_polls_new_option">
				<tr class="vwm_polls_option">
					<td class="drag">
						<input type="button" value="" title="Add poll option" class="vwm_polls_new_option" />
						<input type="hidden" name="vwm_polls_field_id" value="<?php echo $field_id; ?>" />
					</td>
					<td class="color"><input type="hidden" name="vwm_polls_new_option_id" value="new" /><input type="text" name="vwm_polls_new_option_color" value="FFFFFF" class="color" /></td>
					<td class="type">
						<div class="pill">
							<div class="defined">
								<label>
									<?php echo form_radio( array('name' => 'vwm_polls_new_option_type', 'value' => 'defined', 'checked' => TRUE)); ?>
									<?php echo lang('type_defined'); ?>
								</label>
							</div>
							<div class="other">
								<label>
									<?php echo form_radio( array('name' => 'vwm_polls_new_option_type', 'value' => 'other')); ?>
									<?php echo lang('type_other'); ?>
								</label>
							</div>
						</div>
					</td>
					<td>
						<input type="text" name="vwm_polls_new_option_text" placeholder="<?php echo lang('option_text_placeholder'); ?>" />
					</td>
					<td class="remove"><input type="button" value="" title="Remove poll option" name="vwm_polls_remove" class="vwm_polls_remove_option" /></td>
				</tr>
			</tfoot>
			<tbody id="vwm_polls_options_tbody">
				<?php $order = 0; // Echo unique order with $order not the database order field ?>
				<?php foreach ($options as $option): ?>
					<tr class="vwm_polls_option">
						<td class="drag"></td>
						<td class="color"><input type="hidden" name="vwm_polls_options[<?php echo $field_id; ?>][<?php echo $order; ?>][id]" value="<?php echo $option['id']; ?>" /><input type="text" name="vwm_polls_options[<?php echo $field_id; ?>][<?php echo $order; ?>][color]" value="<?php echo $option['color']; ?>" style="background-color: #<?php echo $option['color']; ?>" class="color"/></td>
						<td class="type">
							<div class="pill">
								<div class="defined">
									<label>
										<?php echo form_radio( array('name' => 'vwm_polls_options[' . $field_id . ']['.$order.'][type]', 'value' => 'defined', 'checked' => $option['type'] === 'defined' ? TRUE : FALSE) ); ?>
										<?php echo lang('type_defined'); ?>
									</label>
								</div>
								<div class="other">
									<label>
										<?php echo form_radio( array('name' => 'vwm_polls_options[' . $field_id . ']['.$order.'][type]', 'value' => 'other', 'checked' => $option['type'] === 'other' ? TRUE : FALSE) ); ?>
										<?php echo lang('type_other'); ?>
									</label>
								</div>
							</div>
						</td>
						<td><input type="text" name="vwm_polls_options[<?php echo $field_id; ?>][<?php echo $order; ?>][text]" value="<?php echo $option['text']; ?>" placeholder="<?php echo lang('option_text_removed'); ?>" /></td>
						<td class="remove"><input type="button" value="" title="Remove poll option" class="vwm_polls_remove_option" /></td>
					</tr>
				<?php $order++; 
					endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- Poll Settings -->
	<div id="vwm_polls_settings_container_<?php echo $field_id; ?>">

		<h3><?php echo lang('poll_settings'); ?></h3>

		<table class="mainTable vwm_polls_settings" cellpadding="0px" cellspacing="0px" border="0px">
			<thead>
				<tr>
					<th><?php echo lang('setting'); ?></th>
					<th><?php echo lang('value'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo lang('member_groups_can_vote', 'member_groups_can_vote'); ?></td>
					<td>
						<?php echo form_dropdown("member_groups_can_vote[$field_id]", array('ALL' => lang('all'), 'NONE' => lang('none'), 'SELECT' => lang('select')), $data['member_groups_can_vote'], 'id="member_groups_can_vote"'); ?>
					</td>
				</tr>
				<tr>
					<td>
						<?php echo lang('select_member_groups_can_vote', "select_member_groups_can_vote_$field_id"); ?>
					</td>
					<td>
						<?php echo form_multiselect("select_member_groups_can_vote[$field_id][]", $member_groups, is_array($data['select_member_groups_can_vote']) ? $data['select_member_groups_can_vote'] : NULL); ?>
					</td>
				</tr>
				<tr>
					<td><?php echo lang('multiple_votes', 'multiple_votes'); ?></td>
					<td><?php echo form_dropdown('multiple_votes[' . $field_id . ']', array(lang('no'), lang('yes')), $data['multiple_votes'], 'id="multiple_votes"'); ?></td>
				</tr>
				<tr>
					<td><?php echo lang('multiple_options', 'multiple_options'); ?></td>
					<td><?php echo form_dropdown('multiple_options[' . $field_id . ']', array(lang('no'), lang('yes')), $data['multiple_options'], 'id="multiple_options"'); ?></td>
				</tr>
				<tr>
					<td><?php echo lang('multiple_options_min', 'multiple_options_min'); ?></td>
					<td><input type="text" name="multiple_options_min[<?php echo $field_id; ?>]" id="multiple_options_min" value="<?php echo $data['multiple_options_min']; ?>" /></td>
				</tr>
				<tr>
					<td><?php echo lang('multiple_options_max', 'multiple_options_max'); ?></td>
					<td><input type="text" name="multiple_options_max[<?php echo $field_id; ?>]" id="multiple_options_max" value="<?php echo $data['multiple_options_max']; ?>" /></td>
				</tr>
				<tr>
					<td><?php echo lang('options_order', 'options_order'); ?></td>
					<td><?php echo form_dropdown('options_order[' . $field_id . ']', array('asc' => lang('order_asc'), 'desc' => lang('order_desc'), 'alphabetical' => lang('order_alphabetical'), 'reverse_alphabetical' => lang('order_reverse_alphabetical'), 'random' => lang('order_random'), 'custom' => lang('order_custom')), $data['options_order'], 'id="options_order"'); ?></td>
				</tr>
				<tr>
					<td><?php echo lang('results_chart_type', 'results_chart_type'); ?></td>
					<td><?php echo form_dropdown('results_chart_type[' . $field_id . ']', array('bar' => lang('chart_bar'), 'pie' => lang('chart_pie')), $data['results_chart_type'], 'id="results_chart_type"'); ?></td>
				</tr>
				<tr>
					<td><?php echo lang('results_chart_width', 'results_chart_width'); ?></td>
					<td><input type="text" name="results_chart_width[<?php echo $field_id; ?>]" id="results_chart_width" value="<?php echo $data['results_chart_width']; ?>" /></td>
				</tr>
				<tr>
					<td><?php echo lang('results_chart_height', 'results_chart_height'); ?></td>
					<td><input type="text" name="results_chart_height[<?php echo $field_id; ?>]" id="results_chart_height" value="<?php echo $data['results_chart_height']; ?>" /></td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php if ($total_votes): ?>

		<!-- Poll Results -->
		<div id="vwm_polls_results_container_<?php echo $field_id; ?>">
			<h3><?php echo lang('poll_results'); ?></h3>

			<table class="mainTable vwm_polls_results" cellpadding="0px" cellspacing="0px" border="0px">
				<thead>
					<tr>
						<th><?php echo lang('option'); ?></th>
						<th><?php echo lang('votes'); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="2"><img src="<?php echo $chart; ?>" alt="" /></td>
					</tr>
					<tr>
						<td colspan="2"><?php echo lang('total_votes'); ?>: <?php echo $total_votes; ?></td>
					</tr>
				</tfoot>
				<tbody>
					<?php foreach ($options as $option): ?>
						<tr>
							<td><?php echo $option['text']; ?></td>
							<td>
								<?php if ($option['votes']): ?>
									<?php if (isset($option['other_votes'])): ?><a href="javascript:void(0);"><?php endif; ?>
									<?php echo $option['votes']; ?> Votes (<?php echo round($option['votes'] / $total_votes * 100, 1); ?>%)
									<?php if (isset($option['other_votes'])): ?></a><?php endif; ?>
									<?php if (isset($option['other_votes'])): ?>
										<ul>
											<?php foreach ($option['other_votes'] as $other): ?>
												<li><?php echo $other; ?></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								<?php else: ?>
									<?php echo lang('no_votes'); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>