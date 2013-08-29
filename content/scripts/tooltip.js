/**
 * Gets the width of the browser.
 */
function getBrowserWidth() 
{
    return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth
}; 
/**
 * Gets the mouse x.
 * @param evt The mouse event.
 */
function getMouseX(evt) 
{
    return evt.clientX ? evt.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) : evt.pageX;
} 
/**
 * Gets the mouse y.
 * @param evt The mouse event.
 */
function getMouseY(evt) 
{
    return evt.clientY ? evt.clientY + (document.documentElement.scrollTop || document.body.scrollTop) : evt.pageY
} 

//Maak tooltip
function createTooltip(evt,tooltipText) 
{
    evt = evt? evt: window.event;
		
    var browserWidth = getBrowserWidth();
		
    if(!document.getElementById('tooltip')) {
        // No tooltip yet, create one
        tooltip = document.createElement('div');
        tooltip.onmouseout = removeTooltip;
        // Let it destory itself by clicking
        tooltip.onclick = function() {
            tooltip.style.visibility = "hidden";
            window.onmousemove = null;
        };
        tooltip.setAttribute('id','tooltip');
        document.body.appendChild(tooltip);
    } else {
        // Get existing tooltip
        tooltip = document.getElementById('tooltip');
        if(tooltip.getAttribute("data-original-text") == tooltipText && tooltip.style.visibility != "hidden") {
            // Don't reposition the same tooltip on every mouse move
            return;
            // Note: we can't simply compare the innerHTML, since browsers
            // like to change that to their own format
        }
    }
    tooltip.innerHTML = tooltipText;
    
    tooltip.setAttribute("data-original-text", tooltipText);
		
    tooltipWidth = tooltip.offsetWidth; 
		

    tooltipY = getMouseY(evt)+10;
    tooltipX = getMouseX(evt) - (tooltipWidth/4); 
    if (tooltipX < 2) 
        tooltipX = 2; 
    else if (tooltipX + tooltipWidth > browserWidth)
        tooltipX = browserWidth-tooltipWidth; 
		
    tooltipX += 'px';
    tooltipY += 'px';  
    tooltip.style.left = tooltipX; 
    tooltip.style.top = tooltipY; 
    tooltip.style.visibility = "visible";
}

//Verwijder tooltip
function removeTooltip(evt)
{
    //verwijdert de tooltip als dat zin heeft
    evt = evt? evt: window.event;
	
    tooltip = document.getElementById('tooltip');
    //tooltip.style.visibility = "hidden"; 
    tooltipX = parseInt(tooltip.style.left);
    tooltipY = parseInt(tooltip.style.top);
    mouseX = getMouseX(evt);
    mouseY = getMouseY(evt);
    if(mouseX-tooltipX<0||mouseX-tooltipX>tooltip.offsetWidth||mouseY-tooltipY<0||mouseY-tooltipY>tooltip.offsetHeight)
    {
        tooltip.style.visibility = "hidden"; 
        window.onmousemove = null;
    }
    else
    {
        window.onmousemove = removeTooltip;
    }
}