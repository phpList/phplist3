<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (!defined('IN_WEBBLER') && !defined('WEBBLER')) {
    class date
    {
        public $type = 'date';
        public $name = '';
        public $description = 'Date';
        public $days = array();
        public $months = array();
        public $useTime = false;

        public function __construct($name = '')
        {
            $this->days = array(
                $GLOBALS['I18N']->get('Sunday'),
                $GLOBALS['I18N']->get('Monday'),
                $GLOBALS['I18N']->get('Tuesday'),
                $GLOBALS['I18N']->get('Wednesday'),
                $GLOBALS['I18N']->get('Thursday'),
                $GLOBALS['I18N']->get('Friday'),
                $GLOBALS['I18N']->get('Saturday'),
            );
            $this->months = array(
                '01' => $GLOBALS['I18N']->get('January'),
                '02' => $GLOBALS['I18N']->get('February'),
                '03' => $GLOBALS['I18N']->get('March'),
                '04' => $GLOBALS['I18N']->get('April'),
                '05' => $GLOBALS['I18N']->get('May'),
                '06' => $GLOBALS['I18N']->get('June'),
                '07' => $GLOBALS['I18N']->get('July'),
                '08' => $GLOBALS['I18N']->get('August'),
                '09' => $GLOBALS['I18N']->get('September'),
                '10' => $GLOBALS['I18N']->get('October'),
                '11' => $GLOBALS['I18N']->get('November'),
                '12' => $GLOBALS['I18N']->get('December'),
            );
            $this->name = $name;
            $this->getDate();
            $this->getTime();
        }

        public function setTime($time)
        {
            if (strpos($time, ':')) {
                list($hr, $min, $sec) = explode(':', $time);
            } else {
                $hr = date('h');
                $min = date('j');
                $sec = date('s');
            }
            if (!isset($_REQUEST[$this->name]) || !is_array($_REQUEST[$this->name])) {
                $_REQUEST[$this->name] = array();
            }
            $_REQUEST[$this->name]['hour'] = $hr;
            $_REQUEST[$this->name]['minute'] = $min;
        }

        public function setDateTime($datetime)
        {
            //0000-00-00 00:00:00
            list($date, $time) = explode(' ', $datetime);
            $this->setDate($date);
            $this->setTime($time);
        }

        public function setDate($date)
        {
            list($year, $month, $day) = explode('-', $date);
            if (!isset($_REQUEST[$this->name]) || !is_array($_REQUEST[$this->name])) {
                $_REQUEST[$this->name] = array();
            }
            $_REQUEST[$this->name]['year'] = $year;
            $_REQUEST[$this->name]['month'] = $month;
            $_REQUEST[$this->name]['day'] = $day;
        }

        public function getDate($value = '')
        {
            if (!$value) {
                $value = $this->name;
            }
            if (!$value) {
                $return = date('Y-m-d');
            }
            if (isset($_REQUEST[$value]['year']) && is_array($_REQUEST[$value]) && isset($_REQUEST[$value]['month']) && isset($_REQUEST[$value]['day'])) {
                $return = sprintf('%04d-%02d-%02d', $_REQUEST[$value]['year'], $_REQUEST[$value]['month'],
                    $_REQUEST[$value]['day']);
            } else {
                $return = date('Y-m-d');
            }
            // print "Date ".$value.' '.$return;
            return $return;
        }

        public function getTime($value = '')
        {
            if (!$value) {
                $value = $this->name;
            }
            if (isset($_REQUEST[$value]['hour']) && isset($_REQUEST[$value]['minute'])) {
                return sprintf('%02d:%02d', $_REQUEST[$value]['hour'], $_REQUEST[$value]['minute']);
            } else {
                return date('H:i');
            }
        }

        public function showInput($name, $fielddata, $value, $document_id = 0)
        {
            if (!$name) {
                $name = $this->name;
            }
            //    dbg("$name $fielddata $value $document_id");
            if (!is_array($value)) {
                $year = substr($value, 0, 4);
                $month = substr($value, 5, 2);
                $day = substr($value, 8, 2);
                $hour = substr($value, 11, 2);
                $minute = substr($value, 14, 2);
            } else {
                $year = $value['year'];
                $month = $value['month'];
                $day = $value['day'];
                $hour = $value['hour'];
                $minute = $value['minute'];
            }

            if (!$day && !$month && !$year) {
                $now = getdate(time());
                $day = $now['mday'];
                $month = $now['mon'];
                $year = $now['year'];
            }
            $html = '<div class="date">';

            $html .= " 
      <!-- $day / $month / $year -->".'
     <select name="' .$name.'[day]">';
            for ($i = 1; $i < 32; ++$i) {
                $sel = '';
                if ($i == $day) {
                    $sel = 'selected="selected"';
                }
                $html .= sprintf('
        <option value="%d" %s>%s</option>', $i, $sel, $i);
            }
            $html .= '
      </select>
      <select name="' .$name.'[month]">';
            reset($this->months);
            foreach ($this->months as $key => $val) {
                $sel = '';
                if ($key == $month) {
                    $sel = 'selected="selected"';
                }
                $html .= sprintf('
            <option value="%s" %s>%s</option>', $key, $sel, $val);
            }
            if ($year < 1800) {
                $year = date('Y');
            }
            if (DATE_START_YEAR) {
                $start = DATE_START_YEAR;
            } else {
                $start = $year - 3;
            }
            if (DATE_END_YEAR) {
                $end = DATE_END_YEAR;
            } else {
                $end = $year + 10;
            }

            $html .= '
      </select>
      <select name="' .$name.'[year]">';
            for ($i = $start; $i <= $end; ++$i) {
                $html .= '
          <option ';
                if ($i == $year) {
                    $html .= 'selected="selected"';
                }
                $html .= ">$i</option>";
            }
            $html .= '
      </select>';
            if ($this->useTime) {
                $html .= '
      <select name="' .$name.'[hour]">';
                for ($i = 0; $i <= 23; ++$i) {
                    $sel = '';
                    if ($i == $hour) {
                        $sel = 'selected="selected"';
                    }
                    $html .= sprintf('
          <option value="%d" %s>%02d</option>', $i, $sel, $i);
                }
                $html .= '
        </select>';
                $html .= '
        <select name="' .$name.'[minute]">';
                for ($i = 0; $i <= 59; $i += 15) {
                    $sel = '';
                    if ($i == $minute) {
                        $sel = 'selected="selected"';
                    }
                    $html .= sprintf('
          <option value="%d" %s>%02d</option>', $i, $sel, $i);
                }
                $html .= '
        </select>';
            }

            return $html.'</div>';
        }

        public function display($parent, $data, $leaf, $branch)
        {
            global $config;

            return formatDate($data);
        }

        public function store($itemid, $fielddata, $value, $table)
        {
            Sql_query(sprintf('replace into %s values("%s",%d,"%s")', $table, $fielddata['name'], $itemid,
                $this->getDate($value)));
        }
    }
}
