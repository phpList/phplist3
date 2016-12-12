	    </div>
    </div> <!-- end #content -->

    <div id="right-bar" class="grid_4 four columns">
    <?php echo $firstInstallButton?>
        <div id="context-menu" class="menutableright block">
            <h3>Navigation</h3>
            <?php print contextMenu(); ?>
        </div>
        <div id="recent" class="menutableright block">
            <?php print recentlyVisited(); ?>
        </div>
	    <div id="language">
            <?php print $languageswitcher; ?>
        </div>
	<div class="clear"></div>
<?php include dirname(__FILE__).'/rssfeed.php'; ?>  
	</div><!-- end #right-bar -->
	
	<div class="clear"></div>

</div> <!-- end #container -->

<div id="footer">
    <div class="container">
            <a target="_blank" href="https://phplist.com">&copy; phpList ltd.</a> - v<?php echo VERSION?>
        <div class="right">
            <a target="_blank" href="https://resources.phplist.com">Resources</a>&nbsp; |  
            <a target="_blank" href="http://twitter.com/phplist"><img src="ui/default/images/twitter.png" alt="phpList twitter" /></a>
        </div>
    </div>
</div>
<script language="Javascript" type="text/javascript" src="ui/default/js/all.min.js?v=<?php echo filemtime(dirname(__FILE__).'/js/all.min.js'); ?>"></script>


