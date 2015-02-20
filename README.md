= Just/ThumbnailBundle - Symfony2 Bundle for on-the-fly Thumbnails Creation =

== Overview ==

The bundle creates Thumbnails on the fly using GD for the Symfony2 framework. It uses the Symfony cache system to cache the thumbnails.
It creates a thumbnail of a image in the given size and stores it in cache for the next calls, until the image changes.

== License ==

This bundle is released under the [MIT license](Resources/meta/LICENSE)

== Installation ==

To install the plugin use `symfony plugin-install` command
{{{

symfony plugin-install http://plugins.symfony-project.com/jsTumbnailPlugin

}}}

== Using the plugin ==
Enable one or more modules in your settings.yml  * jsThumbnail
{{{

all:
  .settings:
    enabled_modules:        [ default, jsThumbnail ]
    
}}}

In your template call something like this:
{{{

&lt;?php echo thumbnail_tag(&#039;uploads/pictures/image.jpg&#039;,100, 80, &#039;crop&#039; array(&#039;style&#039; =&gt; &#039;border: 1px solid #ff0000&#039;)) ?&gt; 
//&#039;/path/to/image.jpg&#039;,maximum width, maximum height, params

}}}

The Parameter &quot;mode&quot; can be &quot;normal&quot;, &quot;crop&quot; or &quot;stretch&quot;
you can call the Thumbnail directly:
www.yourhost.com/yourapp.php/jsThumbnail/thumbnail?img=uploads/pictures/image.jpg&amp;maxx=100&amp;maxy=80&amp;mode=crop

{{{

&lt;?php use_helper(&#039;Thumbnail&#039;) ?&gt;

&lt;?php echo thumbnail_tag(&#039;uploads/pictures/offer/54fbcc52d9ec1af3decd50aeed9f5517.jpg&#039;,100, 80, &#039;stretch&#039; array(&#039;style&#039; =&gt; &#039;border: 1px solid #ff0000&#039;)) ?&gt;

}}}

The Plugin automatically checks if the Original image was modificated.
To delete the cached thumbnails call:
{{{

symfony cc

}}}

