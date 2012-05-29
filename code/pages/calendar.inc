<h2><?php echo $this->translations[43] ?></h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = new Database($this);
$oCalendar = new Calendar($this,$oDB);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter


//geef de komende twaalf maanden weer
$year = date('Y');
$month = date('n');
for($i = 0; $i<12; $i++)
{
	$oCalendar->set_month_and_year($month,$year);
	
	$text_to_display.= '<div style="float:left;height:13em;margin:5px">';
	$text_to_display.= '<h3>'.ucfirst( strftime('%B',mktime(0, 0, 0, $month, 1, 2011)) ).' '.$year.'</h3>';//huidige maand en jaar
	$text_to_display.= $oCalendar->get_calendar();
	$text_to_display.= '</div>';
	
	$month+=1;
	if($month>12)
	{
		$month-=12;
		$year+=1;
	}
}
unset($oCalendar);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;
?>