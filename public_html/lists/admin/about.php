<div align="center">
    <table class="about" border="1" cellspacing="5" cellpadding="5">
        <tr>
            <td colspan="2" class="abouthead"><?php echo NAME ?></td>
        </tr>
        <tr>
            <td class="poweredby" valign="top">
                Powered by <a href="https://www.phplist.com" target="_blank">phplist</a>, version <?php echo VERSION ?>
                <a href="https://www.phplist.com" target="_blank"><br/><br/>
                    <img src="../images/power-phplist.png" alt="" width="70" height="30" border="0"/></a>
                <p>&nbsp;</p>
                <p><?php echo s('Certified Secure by ') ?><a href="https://www.httpcs.com/"
                                                             title="Web Vulnerability Scanner" target="_blank"><img
                            src="images/LogoCertifiedHTTPCS.png" height="20" width="100" border="0"
                            alt="Certified Secure by HTTPCS, Web Vulnerability Scanner"
                            title="Certified Secure by HTTPCS, Web Vulnerability Scanner"/></a>
                </p>
            </td>
            <td class="top">
                <?php echo s('phpList is licensed with the %sGNU Affero Public License (AGPL)%s',
                    '<a href="http://www.gnu.org/licenses/agpl.html" target="_blank">', '</a>') ?>.<br/>
                Copyright &copy; 2000-<?php echo date('Y') ?> <a href="http://phplist.com" target="_blank">phpList
                    Ltd.</a><br/><br/>
                <h3><?php echo s('Developers') ?>:</h3>
                <ul>
                    <li>Michiel Dethmers, phpList Ltd</li>
                    <li><a href="https://twitter.com/samtuke" title="Sam Tuke's Twitter feed">Sam Tuke</a>, phpList Ltd
                    </li>
                </ul>
                <h3><?php echo s('Contributors') ?></h3>
                <ul>
                    <li><a href="http://www.dcameron.me.uk/phplist" target="_blank">Duncan Cameron</a>, Forum Moderator,
                        QA Engineer
                    </li>
                    <li><a href="http://dragonrider.co.uk/phplist" target="_blank">Dragonrider</a>, Forum Moderator</li>
                </ul>
                <h3><?php echo s('Design') ?>:</h3>
                <ul>
                    <li><a href="http://eyecatching.tn/" target="_blank">Tarek Djebali</a></li>
                    <li><a href="http://alfredomarcopradil.com/" target="_blank">Alfredo Marco Pradil</a></li>
                </ul>
                <h3><?php echo s('Design implementation') ?>:</h3>
                <ul>
                    <li><a href="http://eyecatching.tn/" target="_blank">Tarek Djebali</a></li>
                    <li><a href="http://mariela.harpo-web.com" target="_blank">Mariela Zárate</a></li>
                </ul>
                <h3><a href="https://www.phplist.org/manual/" target="_blank"><?php echo s('Manual') ?></a></h3>
                <ul>
                    <li>The phpList <a href="https://www.phplist.org/documentation/">Community Documentation Team</a>
                    </li>
                </ul>
                <h3><?php echo s('Translations') ?></h3>
                <p><?php echo s('The translations are provided by the phpList community (that includes you :-) )') ?></p>
                <p><?php echo s('The <a href="http://translate.phplist.com/" target="translate">translation site</a> runs <a href="http://translate.sourceforge.net/" target="pootle">Pootle</a> an Open Source translation tool, provided by <a href="http://translatehouse.org" target="translatehouse">Translate House</a>') ?></p>
                <h3><?php echo s('Acknowledgements') ?></h3>
                <p>
                    <?php echo s('The developers wish to thank the many contributors to this system, who have helped out with bug reports, suggestions, donations, feature requests, sponsoring, translations and many other contributions.') ?>
                </p>
                <b><?php echo s('Portions of the system include') ?></b>
                <ul>
                    <li><a href="http://www.webbler.net" target="_blank">Webbler</a> code, by <a
                            href="https://phplist.com" target="_blank">Michiel Dethmers</a></li>
                    <li><a href="http://www.fckeditor.net/" target="_blank">FCKeditor</a>, by Frederico Caldeira Knabben
                        and team
                    </li>
                    <li>the <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">phpmailer</a> class</li>
                    <li>the <a href="http://jquery.com" target="_blank">jQuery</a> Javascript library</li>
                    by the <a href="http://jquery.org/team">jQuery Team</a>
                </ul>
            </td>
        </tr>
        <?php
        $pluginsHTML = '
    <tr>
    <td width="50" valign="top">' . $GLOBALS['I18N']->get('Plugins') . '</td>
    <td valign="top">
      <ul class="aboutplugins">
';
        // list plugins and allow them to add details
        $pg = '';
        if (isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins']) && count($GLOBALS['plugins'])) {
            foreach ($GLOBALS['plugins'] as $pluginName => $plugin) {
                $pg .= '<li><strong>' . $plugin->name . '</strong> version ' . $plugin->version;
                if ($plugin->authors) {
                    $pg .= ' <span class="pluginauthor">by ' . $plugin->authors . '</span>';
                }
                if ($plugin->displayAbout()) {
                    $pg .= ' <span class="pluginabout"' . $plugin->displayAbout() . '</span>';
                }
                $pg .= '</li>';
            }
            $pluginsHTML .= $pg . '
          </ul>
        </td>
      </tr>
  ';
            if ($pg != '') {
                echo $pluginsHTML;
            }
        }
        ?>
    </table>
</div>


