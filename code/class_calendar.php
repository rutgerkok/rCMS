<?php
class Calendar
{
	protected $website_object;
	protected $database_object;
	protected $month = 0;
	protected $year = 0;
	
	function __construct($oWebsite,$oDB)
	{
		$this->website_object = $oWebsite;
		$this->database_object = $oDB;
		$this->month = date('n');
		$this->year = date('Y');
	}
	
	function set_month_and_year($month,$year)
	{
		$this->month = (int) $month;
		$this->year = (int) $year;
	}
	
	function get_monthlist($selected_month = -1,$name = 'month')
	{
		if($selected_month==-1) $selected_month = date('n'); //huidige maand
		
		$return_value = "<select name=\"$name\" id=\"$name\" class=\"button\" onchange=\"this.form.submit()\">\n";
		for($i=1;$i<=12;$i++)
		{
			if($i==$selected_month)
			{
				$return_value.="<option selected=\"selected\" value=\"$i\">".strftime("%B ", mktime(0, 0, 0, $i, 1, 2005))."</option>\n";
			}
			else
			{
				$return_value.="<option value=\"$i\">".strftime("%B ", mktime(0, 0, 0, $i, 1, 2005))."</option>\n";
			}
		}
		$return_value.= "</select>";
		
		return $return_value;
	}
	
	function get_yearlist($selected_year = -1,$name = 'year')
	{
		$current_year = date('Y');//huidige jaar
		
		if($selected_year==-1) $selected_year = $current_year; //huidige jaar
		
		$return_value = "<select name=\"$name\" id=\"$name\" class=\"button\" onchange=\"this.form.submit()\">\n";
		for($i=$current_year-3;$i<=$current_year+5;$i++)
		{
			if($i==$selected_year)
			{
				$return_value.="<option selected=\"selected\" value=\"$i\">$i</option>\n";
			}
			else
			{
				$return_value.="<option value=\"$i\">$i</option>\n";
			}
		}
		$return_value.= "</select>";
		
		return $return_value;
	}
	
	function get_calendar($table_width=-1)
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		
		$month = (int) $this->month;
		$year = (int) $this->year;
		
		//gegevens ophalen
		$sql = "SELECT `artikel_id`,`artikel_titel`,DAYOFMONTH(`artikel_verwijsdatum`) FROM `artikel` WHERE MONTH(`artikel_verwijsdatum`)=$month AND YEAR(`artikel_verwijsdatum`)=$year ORDER BY `artikel_verwijsdatum` DESC";
		$result = $oDB->query($sql);
		while(list($id,$title,$daynumber) = $oDB->fetch($result))
		{
			$events[$daynumber][] = array($id,$title);
		}
		unset($result);
		
		// kalender weergeven
			//maak weekdagen
		    for ($i=0; $i<7; $i++) 
		    {
		        $weekday[] = strftime("%a ", mktime(0, 0, 0, 6, $i +5, 2005));
		    }
		    $firstday = $this->first_day_in_month($month, $year);
		    $daycount = $this->days_in_month($month, $year);
		    $daysleft = $this->days_left($firstday, $daycount);
		
		    //begin de tabel
		    if($table_width==-1)
		    {
			    $return_value =  "\n". '<table><tr>';
			}
			else
			{
				$return_value =  "\n". '<table style="width:'.$table_width.'px"><tr>';
			}
		    //weekdagen
		    foreach ($weekday as $day) {
		        $return_value .= "<th style=\"width:2em\">$day</th>";
		    }
		    $return_value .= '</tr><tr>'. "\n";
		    for ($i = 0; $i < ($firstday + $daycount + $daysleft); $i ++) {
		        if ($i < $firstday || $i >= $firstday + $daycount)
		        {
		            $return_value .= '<td>&nbsp;</td>'. "\n";
	            }
		        else 
		        {
		            $daynumber = ($i - $firstday +1);
		            //kijk of er events zijn op die dag
		            if(isset($events[$daynumber]))
		            {	//zo ja, maak dan een tooltip
		            	$tooltip = '<strong>'.$daynumber.' '.strftime('%B',mktime(0, 0, 0, $month, 1, 2011)).' '.$year.'</strong><br />';
			            foreach($events[$daynumber] as $event)
		            	{
			            	$tooltip.= addslashes('<a href=\'index.php?p=view_article&amp;id='.$event[0].'\'>'.$event[1].'</a> <br />');
		            	}
		            	$return_value .= "<td onmouseover=\"createTooltip(event,'$tooltip')\"><span class=\"eventdate\">". $daynumber . '</span></td>' . "\n";
		            }
		            else
		            {	//nee? dan gewone cel
			            $return_value .= '<td >'. $daynumber . '</td>'. "\n";
		            }
			        
		            if (($i +1) % 7 == 0)
		                $return_value .= '</tr><tr>'. "\n";
		        }
		    }
		    $return_value .= '</tr></table>';
	    
	    return $return_value;
	}
	
	function get_datepicker()
	{
		$calendar = $this->get_calendar();
		$calendar = str_replace(
			array('href=',                  '<td '),
			array("target=\'_blank\' href=",'<td class="highlight" onclick="sendAndClose(this)" '), $calendar);//laat links openen in nieuw venster en geef een javascript functie mee aan de tabelcellen
		return $calendar;
	}

	private function days_in_month($month, $year) {
	    $timestamp = mktime(0, 0, 0, $month, 1, $year);
	    $daycount = idate('t', $timestamp);
	    return $daycount;
	}
	
	private function first_day_in_month($month, $year) 
	{
	    //geeft het dagnummer in de week terug van de eerste dag van de month (3 voor woensdag)
		$timestamp = mktime(0, 0, 0, $month, 1, $year);
	    $daynumber = idate('w', $timestamp);
	    return $daynumber;
	}
	
	private function days_left($firstday, $daycount) {
	    $mm = ($firstday + $daycount) % 7;
	    if ($mm != 0)
	        $mm = 7 - $mm;
	    return $mm;
	}
}
?>