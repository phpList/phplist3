<style>
    #globalhelp {
        display: none;
    }
</style>

<div class="tabbed">
    <ul>
        <li><a href="#opensource"><?php print s('Open Source') ?></a></li>
        <li><a href="#gethelp"><?php print s('How to get help') ?></a></li>
        <li><a href="#helpout"><?php print s('How to help out') ?></a></li>
        <li><a href="#hosted">phpList Hosted</a></li>
    </ul>

    <div id="opensource">
        <h3><?php print s('phpList is Open Source software') ?></h3>
        <p><?php print s('The concept behind open source is collaboration. A loosely organised network of many contributors where the whole is greater than the sum of its parts.') ?></p>
        <p><?php print s('If you are interested to know more about Open Source, you can visit the links below') ?></p>
        <ul>
            <li><a href="http://www.opensource.org">The Open Source Initiative</a></li>
            <li><a href="http://en.wikipedia.org/wiki/Open-source_software">Wikipedia article on Open Source
                    software</a></li>
            <li><a href="https://socialsourcecommons.org/tag/opensource">List of Open Source software</a></li>

        </ul>
    </div>
    <div id="gethelp">
        <h3><?php print s('Get help with phpList') ?></h3>
        <p><?php print s('To be written. In the meantime you can <a href="http://www.phplist.com/support">visit the support section on the phpList website</a>') ?>
            .</p>
    </div>

    <div id="helpout">
        <h3><?php print s('Help out with phpList') ?></h3>
        <p><?php print s('To be written. In the meantime you can <a href="http://www.phplist.com/developers">visit the developers section on the phpList website</a>') ?></p>
    </div>
    <div id="hosted">
        <!--iframe src="https://www.phplist.com/hosted.html" scrolling="no" style="margin: 0; width: 100%; height: 750px;"></iframe-->
        <?php
        ## using an IFRAME doesn't work any longer in FF.
        $contents = file_get_contents('https://www.phplist.com/hosted.html');
        print $contents;
        ?>


    </div>
</div>
