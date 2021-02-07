<div align="center">
    <table class="about" border="1" cellspacing="5" cellpadding="5">
        <tr>
            <td colspan="2" class="abouthead"><?php echo s('About').' '.NAME ?></td>
        </tr>
        <tr>
            <td><?php echo s('Announcements'); ?></td>
            <td><?php echo subscribeToAnnouncementsForm() ?></td>
        </tr>
        <tr>
            <td class="poweredby" valign="top">
                <?php echo s('Version')?>
            </td>
            <td>
                Powered by <a href="https://www.phplist.com" target="_blank">phplist</a>
                <?php if (!empty($_SESSION['adminloggedin'])) {
                    echo ', version '. VERSION ;
                }?> 
                (<a href="https://github.com/phpList/phplist3/" target="_blank">source)</a>
                <a href="https://phplist.com/poweredby" target="_blank">
                    <img src="../images/power-phplist.png" alt="Powered by phpList" border="0"/></a>
            </td>
        </tr>
        <tr>
            <td><?php echo s('Legal'); ?></td>
            <td>
                <?php echo s('phpList is licensed with the %sGNU Affero Public License (AGPL)%s',
                    '<a href="http://www.gnu.org/licenses/agpl.html" target="_blank">', '</a>') ?>.<br/>
                Copyright &copy; 2000-<?php echo date('Y') ?> <a href="http://phplist.com" target="_blank">phpList
                    Ltd.</a><br/><br/>
             </td>
        </tr>
        <tr>
            <td><?php echo s('Certification'); ?></td>
            <td>
                <p><?php echo s('Certified Secure by ') ?><a href="https://www.httpcs.com/"
                                                             title="Web Vulnerability Scanner" target="_blank"><img
                            src="images/LogoCertifiedHTTPCS.png" height="20" width="100" border="0"
                            alt="Certified Secure by HTTPCS, Web Vulnerability Scanner"
                            title="Certified Secure by HTTPCS, Web Vulnerability Scanner"/></a>
                </p>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Developers') ?>
             </td>
            <td>        
                <ul>
                    <li>Michiel Dethmers, phpList Ltd</li>
                    <li><a href="https://twitter.com/samtuke" title="Sam Tuke's Twitter feed">Sam Tuke</a></li>
                    <li><a href="http://www.dcameron.me.uk/phplist" target="_blank">Duncan Cameron</a></li>                   
                </ul>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Contributors') ?>
            </td>
            <td>
               <ul>
                    <li><a href="http://www.dcameron.me.uk/phplist" target="_blank">Duncan Cameron</a>, Forum Moderator,
                        QA Engineer
                    </li>
                    <li><a href="http://dragonrider.co.uk/phplist" target="_blank">Dragonrider</a>, Forum Moderator</li>
                    <li>Check the phpList <a href="https://discuss.phplist.org/" target="_blank">community forum</a>.</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Design') ?>
            </td>
            <td>
                <ul>
                    <li><a href="http://eyecatching.tn/" target="_blank">Tarek Djebali</a></li>
                    <li><a href="https://harpitoweb.com" target="_blank">Mariela Zárate</a></li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Design implementation') ?>
             </td>
            <td>        
                <ul>
                    <li><a href="http://eyecatching.tn/" target="_blank">Tarek Djebali</a></li>
                    <li><a href="https://harpitoweb.com" target="_blank">Mariela Zárate</a></li>
                </ul>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Documentation') ?>
             </td>
            <td>        
                <p>Created by the phpList <a href="https://www.phplist.org/documentation/">Documentation community</a></p>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Translations') ?>
             </td>
            <td>        
                <p><?php echo s('The translations are provided by the <a href="https://translate.phplist.org/about/contributors/">phpList translation community</a>') ?></p>
                <p><?php echo s('The <a href="http://translate.phplist.com/" target="translate">translation site</a> runs <a href="https://weblate.org/en/" target="translatehouse">Weblate</a> an Open Source translation tool.') ?></p>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Acknowledgements') ?>
             </td>
            <td>
                <p>
                    <?php echo s('The developers wish to thank the many contributors to this system, who have helped out with bug reports, suggestions, donations, feature requests, sponsoring, translations and many other contributions.') ?>
                </p>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo s('Portions of the system include') ?>
             </td>
            <td>
                <ul>
                    <li><a href="http://www.webbler.net" target="_blank">Webbler</a> code, by <a
                            href="https://phplist.com" target="_blank">Michiel Dethmers</a></li>
                    <li><a href="http://www.fckeditor.net/" target="_blank">FCKeditor</a>, by Frederico Caldeira Knabben
                        and team
                    </li>
                    <li>the <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">phpmailer</a> class</li>
                    <li>the <a href="http://jquery.com" target="_blank">jQuery</a> Javascript library by the <a href="http://jquery.org/team">jQuery Team</a>
                </ul>
            </td>
        </tr>

        <?php
        $pluginsHTML = '
    <tr>
    <td width="50" valign="top">' .$GLOBALS['I18N']->get('Plugins').'</td>
    <td valign="top">
      <ul class="aboutplugins">
';
        // list plugins and allow them to add details
        $pg = '';
        if (isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins']) && count($GLOBALS['plugins'])) {
            foreach ($GLOBALS['plugins'] as $pluginName => $plugin) {
                $pg .= '<li><strong>'.$plugin->name.'</strong>';
                if (!empty($_SESSION['adminloggedin'])) {
                    $pg .= 'version '.$plugin->version;
                }
                if ($plugin->authors) {
                    $pg .= ' <span class="pluginauthor">by '.$plugin->authors.'</span>';
                }
                if ($plugin->displayAbout()) {
                    $pg .= ' <span class="pluginabout"'.$plugin->displayAbout().'</span>';
                }
                $pg .= '</li>';
            }
            $pluginsHTML .= $pg.'
          </ul>
        </td>
      </tr>
  ';
            if ($pg != '') {
                echo $pluginsHTML;
            }
        }
        ?>
     <td><?php echo s('Sponsor phpList') ?>
             </td>
            <td>
                <ul>
                    <li>Support phpList financially on <a href="https://www.patreon.com/phpList" target="_blank">Patreon</a></li>
                </ul>
            </td>
        </tr>
    </table>
</div>
