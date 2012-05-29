//Hulpfuncties
function getBrowserWidth() 
{
	return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth
}; 
function getMouseX(evt) 
{
	return evt.clientX ? evt.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) : evt.pageX;
} 
function getMouseY(evt) 
{
	return evt.clientY ? evt.clientY + (document.documentElement.scrollTop || document.body.scrollTop) : evt.pageY
} 


//Maak tooltip
function createTooltip(evt,tooltipText) 
{
	if (document.getElementById) 
	{
		evt = evt? evt: window.event;
		
		var browserWidth = getBrowserWidth();
		
		
		if(!document.getElementById('tooltip'))
		{
			//tooltip bestaat nog niet, maak er een
			tooltip = document.createElement('div');
			tooltip.onmouseout = removeTooltip;
			tooltip.onclick = function() {tooltip.style.visibility = "hidden"; window.onmousemove = null; };//verwijdert door te klikken
			tooltip.setAttribute('id','tooltip');
			document.body.appendChild(tooltip);
		}
		else
		{	//tooltip bestaat al
			tooltip = document.getElementById('tooltip');
			if(tooltip.style.visibility != 'hidden')
			{
				return;//als de tooltip al wordt weergegeven, ga dan niet de tooltip wijzigen
			}
		}
		
		tooltip.innerHTML = tooltipText;
		
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