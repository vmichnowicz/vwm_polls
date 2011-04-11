# VWM Polls

* [Website](http://github.com/vmichnowicz/vwm_polls)
* Version: 0.1-beta

## Description

VWM Polls is a free ExpressionEngine polling module and fieldtype.
It requires PHP 5.2 or greater.

## Example Template Code

	{exp:channel:entries channel="polls" status="closed|open"}

		<h3>{title}</h3>

		{exp:vwm_polls:poll entry_id="{entry_id}" field_id="{poll_1:field_id}" redirect="{path=main/index/{url_title}/results}"}
			{if can_vote && last_segment != "results"}

				{if already_voted}
					You already voted in this poll, however, I will allow you to vote again &mdash; cuz I am a nice guy.
				{/if}

				<ul>
					{options}
						<li {if user_vote}class="user_vote"{/if}>
							<label for="option_{id}">
								<input type="{input_type}" name="{input_name}" id="option_{id}" value="{id}" />
								<span>{text}</span>
								{if type == "other"}
									<input type="text" name="{other_name}" />
								{/if}
							</label>
						</li>
					{/options}
				</ul>
				<input type="submit" name="submit" value="Vote" />
				<a href="{path="index/{url_title}/results}">View Results</a>
			{if:else}
				<img src={chart}" />
			{/if}

		{/exp:vwm_polls:poll}

		<hr/>

	{/exp:channel:entries}

## Notes

The `field_id` attribute is important to note.
In the example code `poll_1` refers to the field name that you give the field in the EE control panel.
If you do not name your field `field_1`, then you will have to change this value accordingly.

It is also important to show both "closed" and "open" polls.
VWM Polls looks at the status of the entry to see if the poll should be open or closed.