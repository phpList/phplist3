<?php

$id = sprintf('%d', $_GET['id']);
echo previewTemplate($id, $_SESSION['logindetails']['id'],
    '<h4 style="color:#369;">'.$GLOBALS['I18N']->get('Sample Newsletter Content').'</h4>'.'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum a quam nec neque interdum venenatis. Duis sed lacus vel elit vehicula facilisis. Phasellus nec quam a justo dapibus iaculis fermentum vitae velit. Donec quis lorem sapien.</p><hr size=1 style="color:#69C" /><br /><p><b style="color:#369"> Donec iaculis ultrices volutpat? </b></p><p><img src="images/sampleimage.jpg" style="float:right;margin-left:10px;border:1px solid #999" />Etiam sodales tortor a sapien sagittis id blandit tortor lacinia. <a href="#" style="color:#369;text-decoration:underline">Integer in elit magna</a>. Phasellus vestibulum nulla ante. Etiam augue magna, venenatis ut ornare eget, tempor pulvinar urna. Maecenas molestie elementum leo vel vehicula. In sed porttitor ligula. Quisque vulputate tortor at tellus gravida in molestie ipsum cursus. Fusce posuere mauris at mauris feugiat quis volutpat velit vestibulum.</p>');

$status = ' ';
