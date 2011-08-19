
var senderXMLHttpObj=getXMLHttpRequestObject();
var receiverXMLHttpObj=getXMLHttpRequestObject();

var rkc_sid;
var rkc_ch = '';
var rkc_csp = '';
var last_msg = 0;
var tout = 0

function rdt(url)
{
	window.location = url;
}

function getXMLHttpRequestObject()
{
	var xmlobj;
	
	// Check for existing requests
	if (xmlobj != null && xmlobj.readyState != 0 && xmlobj.readyState != 4)
	{
		xmlobj.abort();
	}
	try {
		// instantiate object for Mozilla, Nestcape, etc.
		xmlobj = new XMLHttpRequest();
	}
	catch(e) {
		try {
			// instantiate object for Internet Explorer
			xmlobj = new ActiveXObject('Microsoft.XMLHTTP');
		}
		catch(e)
		{
			// Ajax is not supported by the browser
			xmlobj = null;
			return false;
		}
	}
	
	return xmlobj;
}

//
//
//

// check status of receiver object
function receiverStatusChecker()
{
	// if request is completed
	if (receiverXMLHttpObj.readyState == 4)
	{
		if (receiverXMLHttpObj.status == 200)
		{
			// if status == 200 display chat data
			displayChatData(receiverXMLHttpObj);
		}
		else
		{
//			alert('Failed to get response :'+ receiverXMLHttpObj.statusText);
			return false;
		}
	}
}

// Check status of sender object
function senderStatusChecker()
{
	// check if request is completed
	if (senderXMLHttpObj.readyState == 4)
	{
		if (senderXMLHttpObj.status == 200)
		{
			// if status == 200 display chat data
			displayChatData(senderXMLHttpObj);			
		}
		else
		{
//			alert('Failed to get response :'+ senderXMLHttpObj.statusText);
			return false;
		}
	}
}

// get messages from database each 5 seconds
function getChatData()
{
//	clearTimeout(tout);
	receiverXMLHttpObj.open('POST','/chat/'+rkc_ch+'/get/'+rkc_sid+'/'+last_msg+'/',true);
	
	receiverXMLHttpObj.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	receiverXMLHttpObj.setRequestHeader("Content-length", 1024);
	
	receiverXMLHttpObj.send(null);
	receiverXMLHttpObj.onreadystatechange = receiverStatusChecker;
	tout = setTimeout('getChatData()',4000);
}

// display messages
function displayChatData(reqObj)
{
	if(!mdiv)
	{
		var mdiv = hdr_ref('rkc_messages');
		var mm = hdr_ref('rkc_members');
	}
	
	// XML
	if (!reqObj.responseXML)
	{
		return;
	}
	
	var xmldoc = reqObj.responseXML;
	var msg_nodes = xmldoc.getElementsByTagName("message");
	var mmb_nodes = xmldoc.getElementsByTagName("member");
	
	var n_messages = msg_nodes.length;
	var n_mmb = mmb_nodes.length;
	
	for (i = 0; i < (n_messages); i++)
	{
		var i_item = msg_nodes[i].getElementsByTagName("smsg");
		mdiv.innerHTML += unescape(i_item[0].firstChild.nodeValue);
		
		last_msg = msg_nodes[i].getAttribute('id');
		last_sid = msg_nodes[i].getAttribute('sid');
		
		if (rkc_sid != last_sid)
		{
			rkc_sid = last_sid;
		}
	}
	
	// Members
	while(mm.firstChild) mm.removeChild(mm.firstChild);
	
	for (i = 0; i < n_mmb; i++)
	{
		var i_nick = mmb_nodes[i].getElementsByTagName("nick");
		var i_prof = mmb_nodes[i].getElementsByTagName("prof");
		
		li = document.createElement("div");
		a = document.createElement("a");
		a.appendChild(document.createTextNode(i_nick[0].firstChild.nodeValue));
		a.setAttribute("href",i_prof[0].firstChild.nodeValue);
		li.appendChild(a);
		mm.appendChild(li);
	}
	
	mdiv.scrollTop = mdiv.scrollHeight;
}

// send user message
function sendMessage()
{
	var message_obj = hdr_ref('message');
	var message = message_obj.value;
	
	if (message == '')
	{
		message_obj.focus();
		return false;
	}
	
	// open socket connection
	senderXMLHttpObj.open('POST','/chat/'+rkc_ch+'/send/'+rkc_sid+'/'+last_msg+'/',true);
	
	// set form http header
	send_param_message = 'message='+escape(message);
	
	senderXMLHttpObj.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	senderXMLHttpObj.setRequestHeader("Content-length", send_param_message.length);
	senderXMLHttpObj.send(send_param_message);
	senderXMLHttpObj.onreadystatechange = senderStatusChecker;
	
	message_obj.focus();
	message_obj.value = '';
}

//
//
//

function intitializeChat(csp, ch, sid)
{
	if (document.getElementById && document.getElementsByTagName && document.createElement)
	{
		rkc_ch = ch;
		rkc_sid = sid;
		rkc_csp = csp;
		
		csp_obj = hdr_ref(rkc_csp);
		
		var container = document.createElement('div');
		container.className = 'ie-widthfix float-holder';
		
		var mgsdiv = document.createElement('div');
		mgsdiv.setAttribute('id','rkc_messages');
		mgsdiv.className = 'sub-color box3 rkc_of rkc_left';
		mgsdiv.style.height = '250px';
		
		// members
		var membersdiv = document.createElement('div');
		membersdiv.setAttribute('id','rkc_members');
		membersdiv.className = 'sub-color box3 rkc_of rkc_right';
		membersdiv.style.height = '250px';
		
		// main
		container.appendChild(mgsdiv);
		container.appendChild(membersdiv);
		csp_obj.appendChild(container);
		
		// create message form
		var cntmsg = document.createElement('div');
		var mbox = document.createElement('input');
		var mbutton = document.createElement('input');
		
		cntmsg.className = 'm6-top';
		
		mbox.setAttribute('type','text');
		mbox.setAttribute('id','message');
		mbox.setAttribute('name','message');
		mbox.setAttribute('autocomplete', 'off');
		mbox.style.width = '490px';
		
		// create 'send' button
		mbutton.setAttribute('type','button');
		mbutton.className = 'sep-left bold';
		mbutton.setAttribute('value',sbtn_value);
		
		mbox.onkeypress = function(e) { if (((e = e || event).which || e.keyCode) == 13) { sendMessage(); } }
		mbutton.onclick = sendMessage;
		
		// append elements
		cntmsg.appendChild(mbox);
		cntmsg.appendChild(mbutton);
		csp_obj.appendChild(cntmsg);
		
		//
		getChatData();
		
		hdr_ref('message').focus();
	}
}
