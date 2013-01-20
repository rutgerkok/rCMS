//Datum
	var popup;
	function showDatePicker()
	{
		/* opent een popup */
		if(popup) {popup.close();}
		popup = window.open('/datepicker.php','','menubar=no,width=230,height=200');
	}
	function receiveDate(eventDate)
	{
		var input = document.getElementById('article_eventdate');
		
		var year, month, day;
		year = eventDate.getFullYear();
		
		month = (eventDate.getMonth()+1)+'';
		if(month.length==1) month = '0'+month;
			
		day = eventDate.getDate()+'';
		if(day.length==1) day = '0'+day;
		
		input.value = year+'-'+month+'-'+day;
	}
	function isInputTypeSupported(inputType)
	{
		var input = document.createElement('input');
		input.setAttribute('type', inputType);
		return(input.type==inputType);
	}	
	function fieldsInit()
	{
		var dateField = document.getElementById('article_eventdate');
		
		var timeField = document.getElementById('article_eventtime');
		
		var dateButton = document.createElement('input');
		dateButton.setAttribute('type','button');
		dateButton.setAttribute('value',text_select_date); //kijk op kalender
		dateButton.className = 'button';
		dateButton.onclick = showDatePicker;	
		dateField.parentNode.appendChild(dateButton);
		
		if(isInputTypeSupported('date'))
		{
			dateField.setAttribute('type', 'date');
		}
		else
		{
			dateField.onclick = showDatePicker;//maak kalenderwidget verplicht
			dateField.onkeyup = function() {return false};
			dateButton.onclick = null;
		}
		if(isInputTypeSupported('time'))
		{
			timeField.setAttribute('type', 'time');
		}
	}
	
//Afbeelding
	function BrowseServer()
	{
		// You can use the "CKFinder" class to render CKFinder in a page:
		var finder = new CKFinder();
		finder.basePath = 'ckfinder/';	// The path for the installation of CKFinder (default = "/ckfinder/").
		finder.selectActionFunction = SetFileField;
		finder.popup();
	}
	
	// This is a sample function which is called when a file is selected in CKFinder.
	function SetFileField( fileUrl )
	{
		document.getElementById( 'article_featured_image' ).value = fileUrl;
	}