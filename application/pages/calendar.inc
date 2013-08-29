<h2><?php echo $this->t("calendar.calendar_for_twelve_months") ?></h2>
<?php
$textToDisplay='';

//OBJECTEN
$oDB = $this->getDatabase();
$oCalendar = new Calendar($this,$oDB);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter


//geef de komende twaalf maanden weer
$year = date('Y');
$month = date('n');
for($i = 0; $i<12; $i++)
{
	$oCalendar->set_month_and_year($month,$year);
	
	$textToDisplay.= '<div style="float:left;height:13em;margin:5px">';
	$textToDisplay.= '<h3>'.ucfirst( strftime('%B',mktime(0, 0, 0, $month, 1, 2011)) ).' '.$year.'</h3>';//huidige maand en jaar
	$textToDisplay.= $oCalendar->get_calendar();
	$textToDisplay.= '</div>';
	
	$month+=1;
	if($month>12)
	{
		$month-=12;
		$year+=1;
	}
}
unset($oCalendar);

//FOUTAFHANDELING
if(!$this->errorsDisplayed)
{
	$this->echoErrors();
}

//WEERGEVEN
echo $textToDisplay;
?>