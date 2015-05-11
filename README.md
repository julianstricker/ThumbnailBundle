Just/ThumbnailBundle - Symfony2 Bundle for on-the-fly Thumbnails Creation
=========================================================================


Overview
========

This is a bundle for the Symfony2 framework that creates Thumbnails on first demand. The thumbnails then are stored using the Symfony cache system.
It creates a thumbnail of a image in the given size and stores it in cache for the next calls, until the image changes.

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f97fef15-6eb9-45e2-9973-5948514a4864/big.png)](https://insight.sensiolabs.com/projects/f97fef15-6eb9-45e2-9973-5948514a4864)


License
=======

This bundle is released under the [MIT license](Resources/meta/LICENSE)

Installation
============

## Step1: Using Composer

Add the following line to your composer.json require block:

```js
// composer.json
{
    // ...
    require: {
        // ...
        "just/thumbnailbundle": "dev-master"
    }
}
```
    
The standard symfony 2.2 composer.json file has a branch alias that interferes with installing this bundle.  You can work around by removing the lines
 
```js
 "branch-alias": {
            "dev-master": "2.2-dev"
        }
```

Then, you can install the new dependencies by running Composer's ``update``
command from the directory where your ``composer.json`` file is located:

```bash
$ php composer.phar update
```

### Step 2: Register the Bundle

Modify your AppKernel with the following line:
```php
<?php
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Just\ThumbnailBundle\JustThumbnailBundle(),
    // ...
);
```

### Step 3: Configure the bundle

``` yaml
# app/config/config.yml

just_thumbnail:
    imagesrootdir: "/path/to/the/images/root/dir/on/server/"
    placeholder: "/path/to/a/placeholder/image.jpg"
    expiretime: 86400
```
All parameters are optional. 
The default imagesrootdir is the Symfony web-directory. 
The placeholder-Image will be showen if the original image is not readable or not found. If the placeholder parameter is not set then the Controller will return a "404 Not found" message.
"expiretime" is maximum age of a thumbnail in cache in seconds. After this time the thumbnail will be regenerated. Default value is 86400 (one day).

The thumbnail is stored in cache by a key that is generated using the image-name, the maxx-parameter, the maxy-parameter, the mode-parameter and the creationtime. If one of those parameters changes than the thumbnail will be regenerated.

**Note:**
> The JustThumbnailBundle needs to have gd.jpeg_ignore_warning set to "1". Set gd.jpeg_ignore_warning to "1" in your php.ini, and restart your webserver.


### Step 4: Import JustThumbnailBundle routing file

In YAML:

``` yaml
# app/config/routing.yml
just_thumbnail_bundle:
    resource: "@JustThumbnailBundle/Resources/config/routing.yml"
```

Or if you prefer XML:

``` xml
<!-- app/config/routing.xml -->
<import resource="@JustThumbnailBundle/Resources/config/routing.yml"/>
```

### Usage:

The thumbnail will be generated just in time when the url is called:

``` 
/thumbnails/{mode}/{maxx}x{maxy}/{img}.{extension}
/thumbnails/{mode}/{maxx}x/{img}.{extension}
/thumbnails/{mode}/x{maxy}/{img}.{extension}
/thumbnails/{mode}/{maxx}x{maxy}/{img}
/thumbnails/{mode}/{maxx}x/{img}
/thumbnails/{mode}/x{maxy}/{img}
```

#### Parameter "mode"
This parameter can be set to "normal", "crop", "stretch" and "max".
normal: Sets the image to the maximum height or width, without changing the width/height proportion;
crop: The image will be cropped exactly to the given height and width without changing the width/height proportion;
stretch: Sets the image to the maximum height or width, the width/height proportion changes.
max: Sets the image to the maximum height or width, without changing the width/height proportion;

#### Parameter maxx and maxy
"maxx" is the maximum width and "maxy" the maximun height of the generated thumbnail.

#### Placeholder
You can overwrite the placeholder-file-config by adding a "placeholder"-Parameter to the url. Example: www.yourdomain.tld/thumbnails/stretch/800x225/path/to/image?placeholder=new_placeholder_image.jpg

#### Twig Helper
You can use the twig helper to generate the URL :
``` 
{{thumbnail({'img':'a_image','maxx':320,'maxy':240,'mode':'stretch'})}}
``` 
will generate something like:
``` 
/thumbnails/stretch/300x240/hotel/a_image
``` 

[![knpbundles.com](http://knpbundles.com/julianstricker/ThumbnailBundle/badge)](http://knpbundles.com/julianstricker/ThumbnailBundle)

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/julianstricker/thumbnailbundle/trend.png)](https://bitdeli.com/free "Bitdeli Badge")
