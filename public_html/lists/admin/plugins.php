
<h3>Extend phpList by creating your own plugins</h3>

<p>Plugins are a powerful method to add functionality to phpList, without changing the core phpList code.
That way your changes will continue to work, when you need to upgrade to a newer version of phpList.</p>

<p>Particularly when you want to integrate phpList into your own systems, plugins are the way to go.</p>

<h4>How to start writing your own plugins.</h4>
<i>Knowledge of PHP required.</i>
<p>Plugins can influence the functionality in a variety of places. If necessary, you can create pages that will show up in the backend interface, but in many cases, this may not be necessary.</p>
<ul>
<li>Create a class file "myplugin.php" and store it in the plugins folder in the phpList "admin" folder.
<p><b>Note:</b> the ClassName needs to be the same as the file it is stored in, with the php extension. Eg "myplugin" is stored as "myplugin.php".</p>
<pre class="examplecode">
&lt;?php
class myplugin extends phplistPlugin {
  public $name = "This is my plugin";     ## name of the plugin
  public $coderoot = "myplugin";          ## location of files for the plugin, relative to the plugin root 
  public $enabled = 1;                    ## enable this plugin
  public $version = "0.1";                ## version of this plugin
  public $authors = "Your Name";          ## who made it

  function myplugin() {
  }

  function adminmenu() {
    return array(
      "myplugin" => "View my plugin"
    );
  }

}
?&gt;
</pre>
</li>
<li>Find the file "defaultplugin.php" in the "admin" folder and you will find the methods you can implement in your plugin. The methods are commented, so you have an idea of which one is triggered where.
</li>
<li>Sign up to the <a href="http://www.phplist.com/developers">Developers Mailinglist</a> and start discussing your plugin and how to achieve it.
</li>
</ul>


