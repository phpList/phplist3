<script type="text/javascript" src="ui/default/js/jcarousellite_1.0.1.min.js" ></script>
<link rel="icon" href="./images/phplist.ico" type="image/x-icon" />
<link rel="apple-touch-icon" href="./images/phplist-touch-icon.png" />
<link rel="apple-touch-icon-precomposed" href="./images/phplist-touch-icon.png" />
</head>
<body class="<?php if(isset($GLOBALS['page'])) print $GLOBALS['page'];else print "home-not-auth"; ?>">
<div id="dialog"></div><div id="hiddendiv"></div>

<div id="container" class="container_24 container">
	<div id="header" class="grid_24 sixteen columns">
		<div id="logo"><a href="https://www.phplist.com" target="_blank"><img src="ui/default/images/branding.png" alt="phpList, email newsletter manager, logo" title="phpList, the world's most popular Open Source newsletter manager" /></a></div>
		<div id="logged">
<?php
  if (empty($_SESSION["adminloggedin"])) {
    print ('<a id="login" href="./?page=home">Login</a> ');
  } else {
    print ('<a id="logout" href="./?page=logout">Logout</a>');
  }
?>
		</div>
	</div>
	<!-- end .grid_24 -->
	<div class="clear"></div>
	<div id="navigation" class="grid_24 sixteen columns">
<div id="main-menu" class=" fourteen columns alpha">
<?php print topMenu(); ?>
</div>
<div class="two columns omega">
<span id="menu-button"><span>Menu</span></span>
</div>
</div>
<div id="globalhelp">
<?php
  $globalhelp = $pageinfo->content();
  if (!empty($globalhelp)) { ?>
    <div class="content">
        <div class="text">
            <?php print $globalhelp ?>
        </div>
    </div>
    <div class="buttonhelp open"><?php echo s('HELP') ?></div>
    <div class="buttonhelp close"><?php echo s('HELP') ?></div>
<?php } ?>
</div>
<div class="clear"></div>
<div id="content" class="grid_20 twelve columns">
<div id="wrapp">
<div id="progressbar"></div>
<!-- content start here -->
