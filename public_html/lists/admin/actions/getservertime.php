<?php

$currentTime = Sql_Fetch_Row_Query('select now()');

//# let's not show seconds
$status = substr($currentTime[0], 0, -3);
