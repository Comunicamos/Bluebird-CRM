var messages = [];
var contacts = [];

$(document).ready(function(){
	var first_name = cj('#first_name');
	var last_name = cj('#last_name');
	var city = cj('#city');
	var phone = cj('#phone');
	var state = cj('#state');
	var street_address = cj('#street_address');
	var reset = cj('#reset');
	var filter = cj('#filter');
	var assign = cj('#assign');
	
placeholderSupport = ("placeholder" in document.createElement("input"));

if(!placeholderSupport ){
	 console.log('no placeholder Support');
	 $('[placeholder]').focus(function() {
	  var input = $(this);
	  if (input.val() == input.attr('placeholder')) {
	    input.val('');
	    input.removeClass('placeholder');
	  }
	}).blur(function() {
	  var input = $(this);
	  if (input.val() == '' || input.val() == input.attr('placeholder')) {
	    input.addClass('placeholder');
	    input.val(input.attr('placeholder'));
	  }
	}).blur().parents('form').submit(function() {
	  $(this).find('[placeholder]').each(function() {
	    var input = $(this);
	    if (input.val() == input.attr('placeholder')) {
	      input.val('');
	    }
	  })
	});
}else{
	 console.log('placeholder Support');
}

	reset.click(function() {
		city.val("");
		last_name.val("");
		first_name.val("");
		phone.val("");
		street_address.val("");
		cj('.contactList').hide();
		cj('.contactList .contactItem').remove();
		cj('.showContactNumResult span').empty();
		cj('.contactsContainer .linkContact').slideUp('fast');
		cj('.showContactNumResult').slideUp('fast');
		return false;
	});
	
	filter.live('click', function() {
		cj.ajax({
			url: '/civicrm/imap/ajax/contacts',
			data: {
				state: '1031', //always use nystate for now
				city: city.val(),
				phone: phone.val(),
				street_address: street_address.val(),
				first_name: first_name.val(),
				last_name: last_name.val()
			},
			success: function(data,status) {
				console.log(data);
				if(data != null || data != ''){
					contacts = cj.parseJSON(data);
					buildContactList();
				}
			}
		});
		return false;
	});

	assign.click(function() {
		var messageIds = cj('input[name=message_uid]');
		var messageId = -1;
		// cj.each(messageIds, function(idx, val) {
		// 	if(cj(val).attr('checked')) {
		// 		messageId = cj(val).val();
		// 	}
		// });
		// if(messageId == -1) {
		// 	alert("Please select a message.");
		// 	return false;
		// }
		
		var contactIds = cj('input[name=contact_id]');
		var contactId = -1;
		cj.each(contactIds, function(idx, val) {
			if(cj(val).attr('checked')) {
				contactId = cj(val).val();
			}
		});
		if(contactId == -1) {
			alert("Please select a contact.");
			return false;
		}

		cj.ajax({
			url: '/civicrm/imap/ajax/assignMessage',
			data: {
				messageId: messageId,
				contactId: contactId
			},
			success: function(data, status) {
				cj.each(messages,	function (idx, val) { 
                	if(val.uid == messageId) {
                		delete messages[idx];
                		buildMessageList();
                	}
                });
				alert("Assigned email (UID: " + messageId + ") to contact (ID: " + contactId + ").");
			}
		});
		return false;
	});

	pullMessageHeaders();

	// add a delete conform popup
	cj( "#delete-confirm" ).dialog({
		modal: true,
		width: 250,
		autoOpen: false,
		resizable: false	
	});
	
	//
	cj(".delete").live('click', function() {
		var messageId = cj(this).parent().parent().attr('data-id');
		var imapId = cj(this).parent().parent().attr('data-imap_id');

		cj( "#delete-confirm" ).dialog({
			buttons: {
				"Delete": function() {
					cj( this ).dialog( "close" );
					cj.ajax({
						url: '/civicrm/imap/ajax/deleteMessage',
						data: {id: messageId,
					    imapId: imapId },
						success: function(data,status) {
							cj("#"+messageId+'_'+imapId).remove();
							// update count on top
							var old_total = parseInt(cj("#total_number").html(),10);
							cj("#total_number").html(old_total-1);
							//	makeListSortable();
						} 
					});
				},
				Cancel: function() {
					cj( this ).dialog( "close" );
				}
			}
		});
		cj( "#delete-confirm" ).dialog('open');
	});

	// add a find match popup
	cj( "#find-match-popup" ).dialog({
		modal: true,
		height: 500,
		width: 950,
		autoOpen: false,
		resizable: false,
		title: 'Loading Data'
	});

	// what happens when we click find match
	cj(".find_match").live('click', function() {
		var messageId = cj(this).parent().parent().attr('data-id');
		var imapId = cj(this).parent().parent().attr('data-imap_id');
		cj.ajax({
			url: '/civicrm/imap/ajax/message',
			data: {id: messageId,
				   imapId: imapId },
			success: function(data,status) {
				console.log(data);
				messages = cj.parseJSON(data);
				cj('#message_left_header').html('').append("<strong>From: </strong>"+messages.fromName +"  <i>&lt;"+ messages.fromEmail+"&gt;</i><br/><strong>Subject: </strong>"+messages.subject+"<br/>");
				cj('#message_left_email').html(messages.details);
				cj("#find-match-popup").dialog({
					title:  "Reading: "+messages.subject
				});
				cj( "#find-match-popup" ).dialog('open');
 				cj( "#tabs" ).tabs();
			}
		});
	});

	cj(".imapper-contact-box").live('click', function() {
		var radioButton = cj(this).find(".imapper-contact-select-button");
		radioButton.attr('checked', 'checked');
	});

});

function pullMessageHeaders() {
	cj.ajax({
		url: '/civicrm/imap/ajax/unmatchedMessages',
		success: function(data,status) {
			messages = cj.parseJSON(data);
			buildMessageList();
		}
	});
}
function makeListSortable(){
	cj("#sortable_results").dataTable(); 
	console.log('makeListSortable called ');
}

function buildMessageList() {
	if(messages == '' || messages == null)
		return;
	var messagesHtml = '';
	var total_results =0;
	$.each(messages, function(key, value) {
		total_results++;
		messagesHtml += '<tr id="'+value.uid+'_'+value.imap_id+'" data-id="'+value.uid+'" data-imap_id="'+value.imap_id+'" class="imapper-message-box"> <td class="checkboxieout" ><input type="checkbox" name="" value="" /></td>';
		if( value.from_name != ''){
			messagesHtml += '<td class="name">'+value.from_name +'</td>';
		}else {
			messagesHtml += '<td class="name"> N/A </td>';
		}
		messagesHtml += '<td class="email">'+value.from_email +'</td>';
		messagesHtml += '<td class="subject">'+value.subject +'</td>';
		messagesHtml += '<td class="date">'+value.date +'</td>';
		messagesHtml += '<td class="Actions"><span class="find_match"><a href="#">Find match</a></span> | <span class="delete"><a href="#">Delete</a></span></td> </tr>';
	});
	cj('#imapper-messages-list').html(messagesHtml);
	cj("#total_number").html(total_results);
	makeListSortable();
}

function buildContactList() {
	var contactsHtml = '';
	$.each(contacts, function(key, value) {
		contactsHtml += '<div class="imapper-contact-box" data-id="'+value.contact_id+'">';
		contactsHtml += '<div class="imapper-address-select-box">';
		contactsHtml += '<input type="radio" class="imapper-contact-select-button" name="contact_id" value="'+value.contact_id+'" />';
		contactsHtml += '</div>';
		contactsHtml += '<div class="imapper-address-box">';
		contactsHtml += value.display_name + '<br />';
		contactsHtml += value.street_address + '<br />';
		contactsHtml += value.city + ', ' + value.abbreviation + ' ' + value.postal_code;
		contactsHtml += '</div></div>';
		contactsHtml += '<div class="clear"></div>';
	});
	cj('#imapper-contacts-list').html(contactsHtml);
}