<?php

class translation extends phplistTest
{
    public $name = 'Translation';
    public $purpose = 'Test translated texts are loaded correctly';

    public function runtest()
    {
        $test = new phplist_I18N();
        $test->language = 'nl';

        $dutch = file_get_contents(dirname(__FILE__).'/../locale/nl/phplist.po');
        $lines = explode("\n", $dutch);
        $orig = $trans = '';
        $translations = array();
        foreach ($lines as $line) {
            if (preg_match('/^msgid "(.*)"$/', $line, $regs)) {
                $translations[$orig] = $trans;
                $orig = $regs[1];
                $trans = '';
            }
            if (preg_match('/^msgstr "(.*)"$/', $line, $regs)) {
                $trans .= $regs[1];
            }
        }

        $result = array('good' => 0, 'bad' => 0);
        foreach ($translations as $orig => $trans) {
            if (!empty($orig) && !empty($trans) && $orig != $trans) {
                if (strtolower($test->gettext($orig)) == strtolower($trans)) {
                    $result['good'] += 1;
          //print "<h3>SUCCESS ON $orig</h3>";
          //print "<p>$trans</p>";
          //print '<p>'.$test->get($orig).'</p>';
                } else {
                    $result['bad'] += 1;
                    print "<h3>FAIL ON $orig</h3>";
                    print "<p>Should be <b>$trans</b></p>";
                    print '<p>Is currently <b>'.$test->gettext($orig).'</b></p>';
                }
            }
        }
        var_dump($result);
    }
}
