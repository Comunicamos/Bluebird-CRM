<div class="crm-content-block imapperbox" id="Activities">
	<div class='full'>
	<h1>Matched Messages <small id='total_results'><span id="total_number">Loading</span> results</small></h1>
	</div>
	<div id='top'></div>
	<div class='full'>
		<table id="sortable_results" class="">
			<thead>
				<tr class='list_header'>
					<th class='imap_checkbox_header checkbox' ><input class='checkbox_switch'  type="checkbox" name="" value="" /></th>
					<th class='imap_name_header'>Sender’s Info</th>
					<th class='imap_subject_header'>Subject</th>
					<th class='imap_date_header'>Date Forwarded</th>
					<th class='imap_match_type_header hidden'>Match Type</th>
					<th class='imap_forwarded_header'>Forwarded By</th>
					<th class='imap_action_headers'>Actions</th>
				</tr>
			</thead>
			<tbody id='imapper-messages-list'>
				<td valign="top" colspan="7" class="dataTables_empty"><span class="loading_row"><span class="loading_message">Loading Message data <img src="/sites/default/themes/Bluebird/images/loading.gif"/></span></span></td>
			</tbody>
		</table>
		<div class='page_actions'>
			<input type="button" class="multi_tag" value="Tag Selected" name="multi_tag">
			<span style="padding-right:20px;"></span>
			<input type="button" class="multi_clear" value="Remove Selected From List" name="multi_clear">
		</div>
	</div>
	<div id="find-match-popup" title="Loading Data"  style="display:none;">
		<div id="message_left">
			<div id="message_left_header">
			</div>
			<div id="message_left_email">
			</div>
		</div>
		<div id="message_right">
			<div id="tabs">
				<ul>
					<li><a href="#tab1">Find Contact</a></li>
				</ul>
				<div id="tab1">
					<input type="hidden" class="hidden" id="id" name="id">
					<label for="first_name">
						<span class="label_def">First Name: </span>
						<input type="text" placeholder="First Name" class="form-text first_name" name="first_name">
					</label>
					<label for="last_name">
						<span class="label_def">Last Name: </span>
						<input type="text" placeholder="Last Name" class="form-text last_name" name="last_name">
					</label>
					<label for="email_address">
						<span class="label_def">Email: </span>
						<input type="text" placeholder="Email Address" class="email-address email_address" name="email_address">
					</label>
					<label for="dob">
						<span class="label_def">DOB: </span>
						<input type="text" placeholder="yyyy-mm-dd" class="form-text dob" name="dob">
					</label>
					<label for="phone">
						<span class="label_def">Phone #: </span>
						<input type="text" placeholder="Phone Number" class="form-text phone" name="phone">
					</label>
					<label for="street_address">
						<span class="label_def">St. Address: </span>
						<input type="text" placeholder="Street Address" class="form-text street_address" name="street_address">
					</label>
					<label for="city">
						<span class="label_def">City: </span>
						<input type="text" placeholder="City" class="form-text city" name="city">
					</label>
					<label for="state">
						<span class="label_def">State: </span>
						<select class="form-select state" id="state" name="state">
							<option value="">- select -</option>
							<option value="1000">Alabama</option>
							<option value="1001">Alaska</option>
							<option value="1052">American Samoa</option>
							<option value="1002">Arizona</option>
							<option value="1003">Arkansas</option>
							<option value="1060">Armed Forces Americas</option>
							<option value="1059">Armed Forces Europe</option>
							<option value="1061">Armed Forces Pacific</option>
							<option value="1004">California</option>
							<option value="1005">Colorado</option>
							<option value="1006">Connecticut</option>
							<option value="1007">Delaware</option>
							<option value="1050">District of Columbia</option>
							<option value="1008">Florida</option>
							<option value="1009">Georgia</option>
							<option value="1053">Guam</option>
							<option value="1010">Hawaii</option>
							<option value="1011">Idaho</option>
							<option value="1012">Illinois</option>
							<option value="1013">Indiana</option>
							<option value="1014">Iowa</option>
							<option value="1015">Kansas</option>
							<option value="1016">Kentucky</option>
							<option value="1017">Louisiana</option>
							<option value="1018">Maine</option>
							<option value="1019">Maryland</option>
							<option value="1020">Massachusetts</option>
							<option value="1021">Michigan</option>
							<option value="1022">Minnesota</option>
							<option value="1023">Mississippi</option>
							<option value="1024">Missouri</option>
							<option value="1025">Montana</option>
							<option value="1026">Nebraska</option>
							<option value="1027">Nevada</option>
							<option value="1028">New Hampshire</option>
							<option value="1029">New Jersey</option>
							<option value="1030">New Mexico</option>
							<option value="1031">New York</option>
							<option value="1032">North Carolina</option>
							<option value="1033">North Dakota</option>
							<option value="1055">Northern Mariana Islands</option>
							<option value="1034">Ohio</option>
							<option value="1035">Oklahoma</option>
							<option value="1036">Oregon</option>
							<option value="1037">Pennsylvania</option>
							<option value="1056">Puerto Rico</option>
							<option value="1038">Rhode Island</option>
							<option value="1039">South Carolina</option>
							<option value="1040">South Dakota</option>
							<option value="1041">Tennessee</option>
							<option value="1042">Texas</option>
							<option value="1058">United States Minor Outlying Islands</option>
							<option value="1043">Utah</option>
							<option value="1044">Vermont</option>
							<option value="1057">Virgin Islands</option>
							<option value="1045">Virginia</option>
							<option value="1046">Washington</option>
							<option value="1047">West Virginia</option>
							<option value="1048">Wisconsin</option>
							<option value="1049">Wyoming</option>
						</select>
					</label>
					<input type="button" class="imapper-submit" id="filter" value="Search" name="filter">
					<div id="imapper-contacts-list" class="contacts-list"></div>
					<input type="button" class="imapper-submit" id="reassign" value="Reassign" name="reassign">
				</div>
			</div>
		</div>
	</div>
	<div id="help-popup" title="Search Help" style="display:none;">
		<h3>Hidden Columns</h3>
		<ul>
			<li><strong>ManuallyMatched : </strong> <span class="matchbubble marginL5 A" title="This email was manually matched">M</span> When an Matched message was manually matched by a user</li>
			<li><strong>AutomaticallyMatched : </strong> <span class="matchbubble marginL5 H" title="This email was automatically matched">H</span> When an Matched message was automatically matched by imapper </li>
		</ul>
	</div>
	<div id="delete-confirm" title="Delete Message from Matched Messages?" style="display:none;">
		<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Selected messages will be removed permanently. Are you sure?</p>
	</div>
	<div id="clear-confirm" title="Remove Message from From List?" style="display:none;">
		<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Selected messages will be removed from this list. They will remain matched to the assigned Sender, and will not be deleted from Bluebird. Proceed?</p>
	</div>
	<div id="loading-popup" title="please wait" style="display:none;">
		<p> <img src="/sites/default/themes/Bluebird/nyss_skin/images/header-search-active.gif"/> Loading message details.</p>
	</div>
	<div id="reloading-popup" title="please wait" style="display:none;">
		<p> <img src="/sites/default/themes/Bluebird/nyss_skin/images/header-search-active.gif"/>  ReLoading messages.</p>
	</div>
	<div id="fileBug-popup" title="We're here to help"  style="display:none;">
		<p>Step #1. Please explain your problem in the text box and click "Report Problem".</p>
		<p>Step #2. Please contact the support line at x2011</p>
		<textarea rows="4" name="description" id="description"></textarea>
	</div>
	<div id="tagging-popup" title="Tagging" style="display:none;">
		<div id="message_left_tag">
			<div id="message_left_header_tag"> over
			</div>
			<div id="message_left_email_tag">
			</div>
		</div>
		<div id="message_right_tag">
			<div id="tabs_tag">
				<ul>
					<li><a href="#tab1_tag">Tag Contact</a></li>
					<li><a href="#tab2_tag">Tag Activity</a></li>
				</ul>
				<input type="hidden" class="hidden" id="contact_tag_ids" name="contact_tag_ids">
				<input type="hidden" class="hidden" id="contact_ids" name="contact_ids">
				<input type="hidden" class="hidden" id="activity_tag_ids" name="activity_tag_ids">
				<input type="hidden" class="hidden" id="activity_ids" name="activity_ids">
				<div id="tab1_tag">
					<div id='TagContact'>
						<input type="text" id="contact_tag_name"/>
					</div>
				</div>
				<div id="tab2_tag">
					<div id='TagActivity'>
						<input type="text" id="activity_tag_name"/>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
