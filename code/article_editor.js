
// The date picker popup window
var popup;

// Set by initialize
var ckFinderBasePath;

// Constants
var FEATURED_IMAGE_INPUT_FIELD = 'article_featured_image';
var FEATURED_IMAGE_PREVIEW_AREA = 'article_editor_image';

//
// CKEDITOR
//

// Call this on page load
function initialize(newCkFinderBasePath) {
    ckFinderBasePath = newCkFinderBasePath;
}

// Opens CKFinder
function browseServer() {
    var finder = new CKFinder();
    finder.basePath = ckFinderBasePath;
    finder.selectActionFunction = setFileField;
    finder.popup();
}

// Updates the url with the given value
function setFileField(fileUrl) {
    var urlBox = document.getElementById(FEATURED_IMAGE_INPUT_FIELD);
    urlBox.value = fileUrl;
    updateImage(fileUrl);
}

// Updates the preview image with the contents of the given textfield
function updateImage(fileUrl) {
    if(fileUrl) {
        document.getElementById(FEATURED_IMAGE_PREVIEW_AREA).innerHTML = 
        '<img src="' + fileUrl + '" />';
    } else {
        document.getElementById(FEATURED_IMAGE_PREVIEW_AREA).innerHTML = "";
    }
}

// Clears image field
function clearImage() {
    setFileField("");
}

//
// DATE PICKEr
//

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