
// The date picker popup window
var popup;

function showDatePicker()
{
    // Check for previous popup window, close if needed
    if(popup) {
        popup.close();
    }
    popup = window.open('/datepicker.php','','menubar=no,width=230,height=200');
}

// Called from the date picker
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

// Checks whether <input type="inputType" /> is supported
function isInputTypeSupported(inputType)
{
    var input = document.createElement('input');
    input.setAttribute('type', inputType);
    return(input.type==inputType);
}
