
// Set by initialize
var ckFinderBasePath;

// Constants
var FEATURED_IMAGE_INPUT_FIELD = 'article_featured_image';
var FEATURED_IMAGE_PREVIEW_AREA = 'article_editor_image';

//
// CKEDITOR
//

// Call this on page load
function initializeCkFinder(newCkFinderBasePath) {
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
