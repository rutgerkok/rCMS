<?php

class Calendar {

    protected $websiteObject;
    protected $databaseObject;
    protected $month = 0;
    protected $year = 0;

    function __construct($oWebsite, $oDB) {
        $this->websiteObject = $oWebsite;
        $this->databaseObject = $oDB;
        $this->month = date('n');
        $this->year = date('Y');
    }

    function set_month_and_year($month, $year) {
        $this->month = (int) $month;
        $this->year = (int) $year;
    }

    function get_monthlist($selected_month = -1, $name = 'month') {
        if ($selected_month == -1)
            $selected_month = date('n'); //huidige maand

        $returnValue = "<select name=\"$name\" id=\"$name\" class=\"button\" onchange=\"this.form.submit()\">\n";
        for ($i = 1; $i <= 12; $i++) {
            if ($i == $selected_month) {
                $returnValue.="<option selected=\"selected\" value=\"$i\">" . strftime("%B ", mktime(0, 0, 0, $i, 1, 2005)) . "</option>\n";
            } else {
                $returnValue.="<option value=\"$i\">" . strftime("%B ", mktime(0, 0, 0, $i, 1, 2005)) . "</option>\n";
            }
        }
        $returnValue.= "</select>";

        return $returnValue;
    }

    function get_yearlist($selected_year = -1, $name = 'year') {
        $current_year = date('Y'); //huidige jaar

        if ($selected_year == -1)
            $selected_year = $current_year; //huidige jaar

        $returnValue = "<select name=\"$name\" id=\"$name\" class=\"button\" onchange=\"this.form.submit()\">\n";
        for ($i = $current_year - 3; $i <= $current_year + 5; $i++) {
            if ($i == $selected_year) {
                $returnValue.="<option selected=\"selected\" value=\"$i\">$i</option>\n";
            } else {
                $returnValue.="<option value=\"$i\">$i</option>\n";
            }
        }
        $returnValue.= "</select>";

        return $returnValue;
    }

    function get_calendar() {
        $oWebsite = $this->websiteObject;
        $oDB = $this->databaseObject;

        $month = (int) $this->month;
        $year = (int) $this->year;

        //gegevens ophalen
        $sql = "SELECT `artikel_id`,`artikel_titel`,DAYOFMONTH(`artikel_verwijsdatum`) FROM `artikel` WHERE MONTH(`artikel_verwijsdatum`)=$month AND YEAR(`artikel_verwijsdatum`)=$year ORDER BY `artikel_verwijsdatum` DESC";
        $result = $oDB->query($sql);
        while (list($id, $title, $daynumber) = $oDB->fetchNumeric($result)) {
            $events[$daynumber][] = array($id, $title);
        }
        unset($result);

        // kalender weergeven
        //maak weekdagen
        for ($i = 0; $i < 7; $i++) {
            $weekday[] = strftime("%a ", mktime(0, 0, 0, 6, $i + 5, 2005));
        }
        $firstday = $this->first_day_in_month($month, $year);
        $daycount = $this->days_in_month($month, $year);
        $daysleft = $this->days_left($firstday, $daycount);

        //begin de tabel
        $returnValue = "\n" . '<table style="width:97%;max-width:291px"><tr>';
        //weekdagen
        foreach ($weekday as $day) {
            $returnValue .= "<th style=\"width:2em\">$day</th>";
        }
        $returnValue .= '</tr><tr>' . "\n";
        for ($i = 0; $i < ($firstday + $daycount + $daysleft); $i++) {
            if ($i < $firstday || $i >= $firstday + $daycount) {
                $returnValue .= '<td>&nbsp;</td>' . "\n";
            } else {
                $daynumber = ($i - $firstday + 1);
                //kijk of er events zijn op die dag
                if (isSet($events[$daynumber])) { //zo ja, maak dan een tooltip
                    $tooltip = '<strong>' . $daynumber . ' ' . strftime('%B', mktime(0, 0, 0, $month, 1, 2011)) . ' ' . $year . '</strong><br />';
                    foreach ($events[$daynumber] as $event) {
                        $tooltip.= '<a href="' . $oWebsite->getUrlPage("article", $event[0]) . '">' . htmlSpecialChars($event[1]) . '</a> <br />';
                    }
                    // Escape tooltip (it will go into the onmouseover, so it needs double escaping)
                    $tooltip = addslashes(htmlSpecialChars($tooltip));
                    $returnValue .= '<td onmouseover="createTooltip(event,\'' . $tooltip . '\')"><span class="eventdate">' . $daynumber . "</span></td>\n";
                } else { //nee? dan gewone cel
                    $returnValue .= '<td >' . $daynumber . '</td>' . "\n";
                }

                if (($i + 1) % 7 == 0)
                    $returnValue .= '</tr><tr>' . "\n";
            }
        }
        $returnValue .= '</tr></table>';

        return $returnValue;
    }

    function get_datepicker() {
        $calendar = $this->get_calendar();
        // Make links open in new window (those links are inside onmouseover,
        // so they will need to be escaped). Also adds a fancy hover effect.
        $calendar = str_replace(
                array('href=', '<td '), array(htmlSpecialChars('target="_blank" href='), '<td class="highlight" onclick="sendAndClose(this)" '), $calendar
        );
        return $calendar;
    }

    private function days_in_month($month, $year) {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        $daycount = idate('t', $timestamp);
        return $daycount;
    }

    private function first_day_in_month($month, $year) {
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
