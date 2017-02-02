<?php

class cleanListname extends phplistTest
{
    public $name = 'clean Listname';
    public $purpose = 'Check cleaning of names of Lists';

    private $tests = array(

        'simple' => array(
            'orig'   => 'Before <h1>Text</h1> After',
            'result' => 'Before <h1>Text</h1> After',
        ),
        'simplebr' => array(
            'orig'   => 'Before <br><h1>Text</h1> After<br/>',
            'result' => 'Before <br><h1>Text</h1> After<br/>',
        ),
        'simple2' => array(
            'orig'   => '<p>Before</p> <br><h1>Text</h1> After<br/>',
            'result' => '<p>Before</p> <br><h1>Text</h1> After<br/>',
        ),
        'alltags' => array(
            'orig'   => '<br><p>P</p><h1>H1</h1><h2>H2</h2><h3>H3</h3><b>B</b><i>I</i>',
            'result' => '<br><p>P</p><h1>H1</h1><h2>H2</h2><h3>H3</h3><b>B</b><i>I</i>',
        ),
        'invalidtags' => array(
            'orig'   => '<img src="image.png" /><a href="https://www.phplist.com">Link Text</a><p>P</p>',
            'result' => 'Link Text<p>P</p>',
        ),
        'validattr' => array(
            'orig'   => '<p class="myclass" style="Nice" title="Some title">P</p>',
            'result' => '<p class="myclass" style="Nice" title="Some title">P</p>',
        ),
        'invalidattr' => array(
            'orig'   => '<p class="myclass" style="Nice" title="Some title" onclick="alert(1)" onerror="alert(2)">P</p>',
            'result' => '<p class="myclass" style="Nice" title="Some title">P</p>',
        ),
        'badlyformatted' => array(
            'orig'   => '<p class="myclass  style=" Nice" title=  "Some title"   >P</p>',
            'result' => '',
        ),
    );

    public function runtest()
    {
        $pass = 1;
        foreach ($this->tests as $test) {
            $result = cleanListName($test['orig'], array('PHPSESSID', 'uid'));
            echo htmlspecialchars($result).' <strong>should be</strong> '.htmlspecialchars($test['result']);
            $pass = $pass && $result == $test['result'];
            if ($pass) {
                echo $GLOBALS['img_tick'];
            } else {
                echo $GLOBALS['img_cross'];
            }
            echo '<br/>';
        }

        return $pass;
    }
}
