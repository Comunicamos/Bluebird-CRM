/*
* BBTree JS 0.3
* Now with infinite looping!
* And modular abilities!
* Last Updated: 1-7-2013
* Coded: Dan Pozzie (dpozzie@gmail.com)
*/

//alias for basic functionality, also provides pathing for calling function types
var BBTree = {
	startInstance: function(config)
	{	
		//Check remote timestamp first
		//then check cookie timestamp	
		//if cookies found skip getAjaxData
		//if cookie is found, send json to separate.
		//Config - BBTree.startInstance({writeSets: [291,296], treeTypeSet: 'edit'}); 
		callTree.setCurrentSettings(config);
	    //have to use a queue with ajax data
	    cj({})
	    	.queue(BBTree.getAjaxData)
	    	.queue(BBTree.writeTree);
	    //IDEA HERE IS TO GET THE DATA ASYNCHRONOUSLY THE FIRST TIME, IF DATA DOESN'T EXIST.
	    //AND THEN WRITE IT. THIS IS IMPORTANT BECAUSE WE DON'T WANT TO READ THE DATA EVERY TIME
	    //YOU GO AND FIND NEW CONTACTS. YOU WANT TO READ THE CONTACT DATA, NOT THE ENTIRE TREE
	    //AND THEN APPLY THE CONTACT DATA
	    //ALSO REMEMBER TO SET entity_counts: 1
	    //COOKIES REMEMBER CHECK COOKIES

	},
	initContainer: function()
	{
		callTree.treeSetupPage();
	},
	getAjaxData: function(next)
	{
		callTree.callTreeAjax(function(){next();});
	},
	writeTree: function(next)
	{
		//set loadingDot?
		callTree.writeParsedData();//written, but hidden
		//START HERE
		//MAKE SURE PARSED DATA IS HIDDEN BEFORE WRITTEN
		//so that I can unhide data to show it when done
		next();
	}
	/*
		Dynamically Added
		----------------
		SeparateTreeAjax
		----------------
		BBTree["rawJsonData"] = {};
		BBTree["parsedJsonData"] = {};
		BBTree["pulledData"] = {};
	*/
};
//
var callTree =  {
	defaultSettings: {
		pageSettings:{
			wrapper: '#BBTreeContainer',
			idName: 'BBTreeContainer',
			tagHolder: 'BBTree',
			container: 'div',
			hiddenTag: 'hidden'
		},
		displaySettings: { //Sets the default when the page has to be refreshed internally
			writeSets: [291], //Set [one], or [other] to show only one, use [291,296] for both (when you want to show KW & IC)
			treeCodeSet: 291, //IssueCodes = 291, KW = 296. Sets default tree to show first.
			treeTypeSet: 'tagging' //Sets default type to appear: edit, modal or tagging versions... adds 'boxes/checks'
		},
		callSettings:{
			ajaxUrl: '/civicrm/ajax/tag/tree',
			ajaxSettings:{
				entity_type: 'civicrm_contact',
				entity_id: 0,
				call_uri: window.location.href,
				entity_counts: 0
			},
			callback: false
		}
	},
	setCurrentSettings: function(config){
		if(config)
		{
			callTree["pulledConfig"] = {};
			cj.each(
				config,function(i, value){
	            	callTree.pulledConfig[i] = value;
	       	});
	    }
	    cj.extend(true, callTree.defaultSettings, callTree.pulledConfig); //sets the inital settings
		callTree['currentSettings'] = callTree.defaultSettings; //this is what EVERYTHING is based off of...
	},
	treeSetupPage: function(){ 
		//overwrites defaults technically can post these to a cookie and rewrite ::TODO::
		if(cj(callTree.currentSettings.pageSettings.wrapper).length == 0) //needs to append a div right after the function is called
		{
			document.write('<div id="BBTreeContainer"></div>'); //container needs to hold the size and shape
		}
		//make this a function to build x trees with y attributes, and everyone is hidden but the first
		callTree.buildBoxes(); //sends # of boxes to buildBoxes
		//cj(callTree.defaultSettings.pageSettings.wrapper).append('<div class="BBTree '+ this.config.displaySettings.treeTypeSet.toLowerCase() +'"></div>');
	},
	callTreeAjax: function(callback){
		var pageCID = getPageCID();
		cj.extend(callTree.currentSettings.callSettings.ajaxSettings.entity_id,pageCID); //overwrites CID if page is different. Check Add Contact?
		cj.ajax({
			url: callTree.currentSettings.callSettings.ajaxUrl,
			data: {
				entity_type: callTree.currentSettings.callSettings.ajaxSettings.entity_type,
				entity_id: callTree.currentSettings.callSettings.ajaxSettings.entity_id,
				call_uri: callTree.currentSettings.callSettings.ajaxSettings.call_uri,
				entity_counts: callTree.currentSettings.callSettings.ajaxSettings.entity_counts
			},
			dataType: 'json',
			success: function(data, status, XMLHttpRequest) {
				if(data.code != 1) {
					alert('Error');
				}
				else{
					callTree.separateTreeAjax(data.message);
					callback();
				}
			} 
		});
	},
	separateTreeAjax: function(data){
		//if cookie is found, send json to here.
		/*
		var dataStore = $.cookie("basket-data", JSON.stringify($("#ArticlesHolder").data()));
		var data=JSON.parse($.cookie("basket-data"))
		*/
		BBTree["rawJsonData"] = {}; //add new data properties
		BBTree["parsedJsonData"] = {};

		cj.each(data, function(i,tID){
			if(cj.inArray(parseFloat(tID.id), callTree.currentSettings.displaySettings.writeSets)>-1) //Checks against Allowed Sets
			{
				BBTree.rawJsonData[tID.id] = {'name':tID.name, 'children':tID.children};
				var displayObj = callTree.writeTreeInit(tID);
				callTree.parseTreeAjax(tID, displayObj);
			}
		});
	},
	writeTreeInit: function(tID){
		//start testing
		console.log('WriteStart');
		returnTime();
		var displayObj = new Object;
		displayObj.tLvl = 0;
		displayObj.treeTop = tID.id;
		var tagLabel = addTagLabel(tID.id); //writes the identifying tag label
		displayObj.output = '<dl class="lv-'+displayObj.tLvl+'" id="'+tagLabel+'" tLvl="'+displayObj.tLvl+'">';
		displayObj.output += '<dt class="lv-'+displayObj.tLvl+' issueCode-'+tID.id;
		if(cj.inArray(parseFloat(tID.id), callTree.currentSettings.displaySettings.writeSets)>-1) //only writes the 
		{
			if(callTree.currentSettings.callSettings.ajaxSettings.entity_id != 0)
			{
				displayObj.output += isItemMarked(tID.is_checked,'checked');
			}
			displayObj.output += ' ' + isItemMarked(tID.is_reserved,'isReserved');
		}
		displayObj.output += '" id="'+tagLabel+'" description="'+tID.description+'" tLvl="'+displayObj.tLvl+'" tID="'+tID.id+'">';
		displayObj.output += '<div class=" '+isItemChildless(tID.children.length)+'"></div><div class="tag">'+tID.name+'</div>';
		displayObj.output += addControlBox(tagLabel, displayObj.treeTop) + '</dt>';
		console.log('endWrite');
		returnTime();
		return displayObj;
	},
	parseTreeAjax: function(tID, displayObj){
		var treeData = callTree.parseJsonInsides(tID, displayObj);
		BBTree.parsedJsonData[tID.id] = {'name':tID.name, 'data':treeData};
	},
	parseJsonInsides: function(tID, displayObj){
		if(tID.children.length >= 0) //as long as you're greater than or equal to 0, to start. opening the first tag will ++ it
		{
			cj.each(tID.children, function(i, cID){
				callTree.openChildJsonTag(cID, displayObj);
				callTree.writeJsonTag(cID, displayObj);
				callTree.closeChildJsonTag(tID, displayObj);
			});
		}
		if(displayObj.tLvl == 0){ //means you've reached the end!
			return(displayObj.output);	
		}
	},
	writeJsonTag: function(tID, displayObj){	
		var tagLabel = addTagLabel(tID.id);
		var isChecked = isItemMarked(tID.is_checked,'checked');
		displayObj.output += '<dt class="lv-'+displayObj.tLvl+' issueCode-'+tID.id+' '+isItemMarked(tID.is_reserved,'isReserved')+'" id="'+tagLabel+'" description="'+tID.description+'" tLvl="'+displayObj.tLvl+'"  tID="'+tID.id+'"><div class="treeButton"></div><div class="tag">'+tID.name+'</div>' + addControlBox(tagLabel, displayObj.treeTop)  + '</dt>';
		if(tID.children.length > 0)
		{
			cj.each(tID.children, function(i, cID){
				var isCChecked = isItemMarked(cID.is_checked,'checked');
				tagLabel = addTagLabel(cID.id);
				callTree.openChildJsonTag(cID,displayObj);
				displayObj.output += '<dt class="lv-'+displayObj.tLvl+' issueCode-'+cID.id+' '+isItemMarked(cID.is_reserved,'isReserved')+'" id="'+tagLabel+'" description="'+cID.description+'" tLvl="'+displayObj.tLvl+'" cID="'+cID.id+'"><div class="treeButton"></div><div class="tag">'+cID.name+'</div>' + addControlBox(tagLabel, displayObj.treeTop)  + '</dt>';
				callTree.parseJsonInsides(cID,displayObj);	
				callTree.closeChildJsonTag(tID, displayObj);
			});
		}
		return displayObj.output;
	},
	openChildJsonTag: function(tID, displayObj){
		var tagLabel = addTagLabel(tID.id);
		displayObj.tLvl++;
		displayObj.output += '<dl class="lv-'+displayObj.tLvl+'" id="'+tagLabel+'" tLvl="'+displayObj.tLvl+'" style="">';	
	},
	closeChildJsonTag: function(tID, displayObj){
		displayObj.tLvl--;
		displayObj.output += '</dl>';
	},
	buildBoxes: function() //reads from currentSettings to make the boxes to put lists in
	{
		cj.each(callTree.currentSettings.displaySettings.writeSets, function(i, className){
			var treeBox = '<div class="'+ callTree.currentSettings.pageSettings.tagHolder +' '+ callTree.currentSettings.displaySettings.treeTypeSet.toLowerCase() + ' ';
			if(className != callTree.currentSettings.displaySettings.treeCodeSet) //hide all boxes that aren't 'default' 
			{
				treeBox += 'hidden ';
			}
			treeBox += addTagLabel(className);
			treeBox += '"></div>';
			cj(callTree.currentSettings.pageSettings.wrapper).append(treeBox);
		});	
	},
	writeParsedData: function()//write the tree to the CORRECT div
	{
		callTree.writeTabs();
		cj.each(callTree.currentSettings.displaySettings.writeSets, function(i, className){
			var treeTarget = callTree.currentSettings.pageSettings.wrapper + ' ';
			treeTarget += '.'+ callTree.currentSettings.pageSettings.tagHolder;
			treeTarget += '.'+ callTree.currentSettings.displaySettings.treeTypeSet.toLowerCase();
			treeTarget += '.'+ addTagLabel(className);
			cj(treeTarget).append(BBTree.parsedJsonData[className].data);
		});
	},
	writeTabs: function()
	{
		//need to figure out how to 
		if(cj('.crm-tagTabHeader ul li').length > 0)
		{
			cj('.crm-tagTabHeader ul').html('');
		}
		cj.each(callTree.currentSettings.displaySettings.writeSets, function(i, className){
			var tabInfo = {id: callTree.currentSettings.displaySettings.writeSets[i], name: BBTree.parsedJsonData[callTree.currentSettings.displaySettings.writeSets[i]].name, position: i, length: callTree.currentSettings.displaySettings.writeSets.length, isActive: ''};

			if(className == callTree.currentSettings.displaySettings.treeCodeSet) //hide all boxes that aren't 
			{
				tabInfo.isActive = 'active';
			}
			var tabHTML = '<li class="tab '+ tabInfo.isActive+ ' ' +addTagLabel(tabInfo.id) + '" onclick="swapTrees(this);return false;">'+tabInfo.name+'</li>';
			cj('.crm-tagTabHeader ul').append(tabHTML);
		});
		
	},
	swapTrees: function(tab)
	{

	}
	//still need a reload tree option.
	//make sure to capture which ones are 'open'
	//write a different addControlBox function that functions based on the parameters sent
};


var BBTreeEdit = {
	//ON EDIT, make sure to update the saved data.

}
var BBTreeTag = {

}

//simple functions, oft repeated grouped by type
function getActiveTab()
{

}
//finds Page CID
function getPageCID()
{
	var cid = 0 ;
	var cidpre = /cid=\d*/.exec(document.location.search);
	var cidsplit = /\d.*/.exec(cidpre);
	if(cidsplit)
	{
		cid = cidsplit[0];
	}
	return cid;
}
function addTagLabel(tag)
{
	return 'tagLabel_' + tag;
}
function removeTagLabel(tag)
{
	return tag.replace('tagLabel_', '');
}
//checks to see if the user can add or remove tags
function getUserEditLevel()
{
	cj.ajax({
		url: '/civicrm/ajax/entity_tag/checkUserLevel/',
		data: {
			call_uri: window.location.href
		},
		dataType: 'json',
		success: function(data, status, XMLHttpRequest) {
			return data.code; //true = can use
		}
	});
}
//marks item as checked or reserved
function isItemMarked(value, type)
{
	if(value == true)
	{
		return(type);
	}
	else {
		return '';
	}
}
//if has children, return either arrow or nothing
function isItemChildless(childLength)
{
	if(childLength > 0)
	{
		return('treeButton');
	}
	else
	{
		return '';
	}
}
//adds Control Box
function addControlBox(tagLabel, treeTop) {
	var floatControlBox = '';
	var tagMouse = 'dt#'+tagLabel;
	floatControlBox = '<span class="fCB" style="padding:1px 0;float:right;">';
	floatControlBox += '<ul>';
	if(treeTop == '291')
	{

		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; float:left;" title="Add New Tag" onclick="makeModalAdd(\''+ tagLabel +'\')"></li>';
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -17px 0px; float:left;" title="Remove Tag" onclick="makeModalRemove(\''+ tagLabel +'\')"></li>';
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -34px 0px; float:left;" title="Move Tag" onclick="makeModalTree(\''+ tagLabel +'\')"></li>';
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -50px 0px; float:left;" title="Update Tag" onclick="makeModalUpdate(\''+ tagLabel +'\')"></li>';
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -66px 0px; float:left;" title="Merge Tag" onclick="makeModalMerge(\''+ tagLabel +'\')"></li>';
	}
	if(treeTop == '296')
	{
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -17px 0px; float:left;" title="Remove Keyword" onclick="makeModalRemove(\''+ tagLabel +'\')"></li>';
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -50px 0px; float:left;" title="Update Keyword" onclick="makeModalUpdate(\''+ tagLabel +'\')"></li>';
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -66px 0px; float:left;" title="Merge Keyword" onclick="makeModalKWMerge(\''+ tagLabel +'\')"></li>';
		floatControlBox += '<li style="height:16px; width:16px; margin:auto 1px; background-position: -107px 0px; float:left;" title="Convert Keyword" onclick="makeModalConvert(\''+ tagLabel +'\')"></li>';
	}
	floatControlBox += '</span>';
	if(tagMouse == 'dt#tagLabel_291')
	{
		return '<span class="fCB" style="padding:1px 0;float:right;"><ul><li class="printTag" style="height:16px; width:16px; margin:auto 1px; float:left;" onClick="printTags()"> </li><li style="height:16px; width:16px; margin:auto 1px; float:left;" title="Add New Tag" onclick="makeModalAdd(\''+ tagLabel +'\')"></li></ul></span>'; 
	}else if(tagMouse == 'dt#tagLabel_296')
	{
		return '<span class="fCB" style="padding:1px 0;float:right;"><ul><li class="printTag" style="height:16px; width:16px; margin:auto 1px; float:left;" onClick="printTags()"> </li><li style="height:16px; width:16px; margin:auto 1px; float:left;" title="Add New Tag" onclick="makeModalAdd(\''+ tagLabel +'\')"></li></ul></span>'; 
	} else { return(floatControlBox); }
}
//remove at the end
function returnTime()
{
	var time = new Date();
	var rTime = time.getMinutes() + ':' + time.getSeconds() + ':' + time.getMilliseconds();
	console.log(rTime);
}
