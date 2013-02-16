<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Kruth <kruth@bfpi.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once('class.iCalReader.php');
require_once('class.daystatus.php');

/**
 * Plugin 'Google Kalender Monatsübersicht' for the 'ak_google_avail' extension.
 *
 * @author	Alexander Kruth <kruth@bfpi.de>
 * @package	TYPO3
 * @subpackage	tx_akgoogleavail
 */
class tx_akgoogleavail_pi1 extends tslib_pibase {
	public $prefixId      = 'tx_akgoogleavail_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_akgoogleavail_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'ak_google_avail';	// The extension key.
	public $pi_checkCHash = TRUE;
	
	/**
	 * The main method of the Plugin.
	 *
	 * @param string $content The Plugin content
	 * @param array $conf The Plugin configuration
	 * @return string The content that is displayed on the website
	 */
	public function main($content, array $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
    $this->pi_loadLL();
	
    $this->pi_initPIflexForm();
    $content = "";
    switch($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'mode')) {
    case "single":
      $content = $this->singleMode();
      break;
    case "multiple":
      $content = $this->multipleMode();
      break;
    }

		return $this->pi_wrapInBaseClass($content);
	}

  function singleMode() {
    $url = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'url');
    $bookedDays = $this->getBookedDays($url);
    $now = new DateTime("first day of this month");
    $now->setTime(0, 0, 0);

    $content = $this->legend();
    $content .= '<br class="clear"/>';
    for($i = 1; $i <= 12; $i++) {
      $content .= $this->drawMonth($now, $bookedDays);
      $now->modify("+1 month");
    }
    $content .= '<br class="clear"/>';
		return $content;
	}

  function multipleMode() {
    $params = t3lib_div::_GET($this->prefixId);
    $month = new DateTime("first day of this month");
    if(isset($params) && isset($params['month'])) {
      list($m, $y) = explode("-", $params['month']);
      $month = new DateTime("$y-$m-1");
    }
    $month->setTime(0, 0, 0);
    $content = '';
    $content .= $this->legend();
    $content .= $this->multipleTable($month);
    return $content;
  }

  function multipleSelectMonth($month) {
    $first_month = new DateTime("first day of this month");
    $last_month = new DateTime("next year");
    $month_period = new DatePeriod($first_month, new DateInterval('P1M'), $last_month);

    $content = '<form action="' . $this->pi_getPageLink($GLOBALS['TSFE']->id) . '" method="GET">';
    $content .= '<select name="' . $this->prefixId . '[month]" onchange="this.form.submit();">';
    $current_month_year = $month->format('m-Y');
    foreach($month_period as $month_date) {
      $month_year = $month_date->format('m-Y');
      $content .= '<option value="'. $month_year .'"';
      if($month_year == $current_month_year) {
        $content .= ' selected="selected"';
      }
      $content .= '>'. $this->formatFullMonth($month_date) .'</option>';
    }
    $content .= '</select>';
    $content .= '</form>';
    return $content;
  }

  function multipleTable($month) {
    $start = clone $month;
    $start->sub(new DateInterval('P3D'));
    $end = clone $month;
    $end->add(new DateInterval('P1M3D'));
    $content = '<table><tr><th></th>'.
      '<th colspan="3">'. $this->formatShortMonth($start) .'</th>'.
      '<th colspan="'. $month->format('t') .'">'. $this->multipleSelectMonth($month) .'</th>'.
      '<th colspan="3">'. $this->formatShortMonth($end) .'</th></tr>';
    $names = explode("\n", $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'names'));
    $urls = explode("\n", $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'urls'));
    $pages = explode(",", $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pages'));
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    $content .= '<tr><td></td>';
    foreach($period as $date) {
      $content .= '<td class="wday">'. $this->pi_getLL('wday_'. $date->format('N')) .'</td>';
    }
    $content .= '</tr>';
    for($i = 0; $i < count($names); $i++) {
      $content .= '<tr><td class="name">' .
        '<a href="'. $this->pi_getPageLink($pages[$i]) .'">'.
        $names[$i] .'</a></td>';
      $bookedDays = $this->getBookedDays($urls[$i]);
      foreach($period as $date) {
        $content .= $this->drawDay($date, $bookedDays);
      }
    }
    $content .= '</tr></tbody></table>';
    return $content;
  }

  function legend() {
    return <<<EOT
<div class="legend">
  <table>
    <tr>
      <td class="vacant day">&nbsp;</td>
      <th>frei</td>
      <td class="booked day">&nbsp;</td>
      <th>belegt</td>
    </tr>
  </table>
</div>
EOT;
  }

  function formatShortMonth($date) {
    return $this->pi_getLL("shortmonth_". $date->format('n')) .' '. $date->format('Y');
  }

  function formatFullMonth($date) {
    return $this->pi_getLL("month_". $date->format('n')) .' '. $date->format('Y');
  }

  function drawMonth($start, $bookedDays) {
    $date = clone $start;
    $ret = '<div class="month">
        <h3 class="monthname">' . $this->formatFullMonth($date) .'</h3>
        <table>
        <tr>';
    for($i = 1; $i <= 7; $i++) {
      $ret .= '<th>'. $this->pi_getLL('wday_'. $i) .'</th>';
    }
    $ret .= '</tr>';
    for($week = 1; $week <= 6; $week++) {
      $ret .= "<tr>";
      for($wday = 1; $wday <= 7; $wday++) {
        // Wochentag stimmt mit Spalte überein und Monat ist der gewünschte
        if(intval($date->format('N')) == $wday && 
            $date->format('n') == $start->format('n')) {
          $ret .= $this->drawDay($date, $bookedDays);
          $date->modify("+1 day");
        }
        else {
          $ret .= "<td></td>";
        }
      }
      $ret .= "</tr>";
    }
    $ret .= '</table></div>';
    return $ret;
  }

  function drawDay(&$date, &$bookedDays) {
    $class = "day ";
    $title = "Frei";
    $dateStr = $date->format("Y-m-d");
    if(array_key_exists($dateStr, $bookedDays)) {
      switch($bookedDays[$dateStr]) {
      case DayStatus::BOOKED:
        $class .= "booked";
        $title = "Belegt";
        break;
      case DayStatus::ARRIVAL:
        $class .= "arrival";
        $title = "Anreisetag";
        break;
      case DayStatus::DEPARTURE:
        $class .= "departure";
        $title = "Abreisetag";
        break;
      }
    }
    else {
      $class .= "vacant";
    }
    return '<td class="'. $class .'" title="'. $title .'">'. $date->format('j') ."</td>";
  }

  function getBookedDays($url) {
    $ical = new ICal($url);
    $bookedDays = array();
    $events = $ical->events();
    if(!isset($events)) {
      return $bookedDays;
    }
    // Nach Anreise sortieren
    usort($events, create_function('$a, $b', 'return strcmp($a[\'DTSTART\'], $b[\'DTSTART\']);'));
    foreach($events as $event) {
      $period = new DatePeriod(
        new DateTime($event['DTSTART']),
        new DateInterval('P1D'),
        new DateTime($event['DTEND'])
      );
      $days = iterator_to_array($period);
      // $days in Strings umwandeln, damit sie als Array-Keys benutzt werden können
      array_walk($days, create_function('&$date', '$date = $date->format("Y-m-d");'));
      reset($days);
      $arrivalDay = current($days);
      $departureDay = end($days);
      $this->setArrivalDate($bookedDays, $arrivalDay);
      // Alle Tage außer An- und Abreise durchgehen
      for($i = 1; $i < count($days) - 1; $i++) {
        $bookedDays[$days[$i]] = DayStatus::BOOKED;
      }
      if($departureDay != $arrivalDay)
        $this->setDepartureDate($bookedDays, $departureDay);
    }
    return $bookedDays;
  }

  function setArrivalDate(&$bookedDays, &$arrivalDay) {
    if(array_key_exists($arrivalDay, $bookedDays)) {
      switch($bookedDays[$arrivalDay]) {
      case DayStatus::ARRIVAL:
      case DayStatus::BOOKED:
        break;
      case DayStatus::DEPARTURE:
        $bookedDays[$arrivalDay] = DayStatus::BOOKED;
      }
    }
    else {
      $bookedDays[$arrivalDay] = DayStatus::ARRIVAL;
    }
  }

  function setDepartureDate(&$bookedDays, &$departureDay) {
    if(!array_key_exists($departureDay, $bookedDays)) {
      $bookedDays[$departureDay] = DayStatus::DEPARTURE;
    }
  }
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ak_google_avail/pi1/class.tx_akgoogleavail_pi1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ak_google_avail/pi1/class.tx_akgoogleavail_pi1.php']);
}

?>
