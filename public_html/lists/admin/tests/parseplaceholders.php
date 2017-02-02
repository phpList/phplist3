<?php

require_once dirname(__FILE__).'/../sendemaillib.php';

class parseplaceholders extends phplistTest
{
    public $name = 'parsePlaceholders';
    public $purpose = 'Test placeholder parsing';

    public function runtest()
    {
        $placeholderTests = array(
            'Basic 1' => array(
                'values'   => array('NAME' => 'First Name', 'LASTNAME' => 'Last Name'),
                'template' => ' [NAME] [LASTNAME] ',
                'result'   => ' First Name Last Name ',
            ),
            'Basic with fallback' => array(
                'values'   => array('SALUTATION' => '', 'LASTNAME' => 'Last Name'),
                'template' => ' Dear [SALUTATION%%Mr/Ms] [LASTNAME] ',
                'result'   => ' Dear Mr/Ms Last Name ',
            ),
            'Basic Case' => array(
                'values'   => array('saluTaTION' => '', 'LastName' => 'Last Name'),
                'template' => ' Dear [SALuTation%%Mr/Ms] [lASTnAme] ',
                'result'   => ' Dear Mr/Ms Last Name ',
            ),
            'Foreign Char' => array(
                'values'   => array('Företag' => 'XXX'),
                'template' => ' [Företag] [F&ouml;retag] [F&ouml;RETAG] ',
                'result'   => ' XXX XXX XXX ',
            ),
            'Foreign Char2' => array(
                'values'   => array('Företag' => 'XXX'),
                'template' => ' [F&ouml;RETAG%%Company name] ',
                'result'   => ' XXX ',
            ),
            'Empty value' => array(
                'values'   => array('Empty' => ''),
                'template' => ' [EMPTY%%Fallback value] ',
                'result'   => ' Fallback value ',
            ),
            'Foreign Char3' => array(
                'values'   => array('PAíS COUNTRY' => 'São Paulo'),
                'template' => ' [PA&iacute;S COUNTRY%%Fallback value] [PAíS COUNTRY%%Fallback value] ',
                'result'   => ' São Paulo São Paulo ',
            ),
            'Foreign Char Fallback' => array(
                'values'   => array('PAíS COUNTRY' => ''),
                'template' => ' [PA&iacute;S COUNTRY%%Fallback value] [PAíS COUNTRY%%Fallback value] ',
                'result'   => ' Fallback value Fallback value ',
            ),
            'Multiple' => array(
                'values' => array(
                    'Name'         => '',
                    'PAíS COUNTRY' => 'São Paulo',
                    'Nome'         => 'Your real name',
                    'GRUPO BAND'   => 'Loahded dipers',
                ),
                'template' => '<p>Dear [NAME%%Friend]</p>
<p>&nbsp;</p>
<p>Your band name is [GRUPO BAND%%Unknown band name]</p>
<p>and you live in [PA&iacute;S COUNTRY%%No idea actually]</p>
<p>[PA&iacute;S COUNTRY]</p>
<p>Your real name is [NOME]</p>
<p>&nbsp;</p>',
                'result' => '<p>Dear Friend</p>
<p>&nbsp;</p>
<p>Your band name is Loahded dipers</p>
<p>and you live in São Paulo</p>
<p>São Paulo</p>
<p>Your real name is Your real name</p>
<p>&nbsp;</p>',
            ),

        );
        echo '<P>PHP '.PHP_VERSION.' running on '.PHP_OS.' - Testing placeholder parsing</P>';

        $resultString = '';
        $nFoundWrong = 0;

        $boolean = array('FALSE', 'TRUE');

        foreach ($placeholderTests as $placeholder => $test) {
            $testresult = parsePlaceHolders($test['template'], $test['values']);
            if ($testresult != $test['result']) {
                $resultString .= "$placeholder parses incorrectly <BR/> <pre>".htmlspecialchars($testresult).'</pre> instead of <pre>'.htmlspecialchars($test['result']).'</pre>';
                ++$nFoundWrong;
            }
        }

        if ($nFoundWrong > 0) {
            echo "<P>The following $nFoundWrong placeholders were evaluated wrong:<BR/>$resultString</P>";

            return false;
        } else {
            echo '<P>All placeholders evaluated correctly.</P>';

            return true;
        }
    }
}
