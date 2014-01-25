# MageCache

The module provides an advanced integration of **Varnish** into **Magento**. 

**Smile_MageCache** module implements 2 fundamental mechanisms required for efficient cache management:
1. **Tagging**, that is used to define all possible dependencies and attach proper tags to each page
2. **Cache invalidation**, that is used to purge an object stored in 3rd party cache engine like
Varnish, CDN, etc. when one of several tags are expired.


This module contains several predefined components and strategies for each of these mechanisms. It also provides a flexible API in order to customize them adapting to the needs of specific project.

It has been developed and tested against **Magento EE 1.13**. 

## Install MageCache

### Module install

The easiest way to install the module is to use the installer, by launching the following shell command from you Magento installation root folder :

    php < <(wget -O - https://raw.github.com/Smile-SA/magecache/master/installer.php)


The installation will be processed from the master branch. If you prefer to pick a specific release (v.1.0.0 by example), you can use this syntax to specify the release :

    php -- v.1.0.0 < <(wget -O - https://raw.github.com/Smile-SA/magecache/master/installer.php)

### Documentation

Read the guide available at : 
https://github.com/Smile-SA/magecache/blob/master/docs/Smile_PageCache%20-%20Guide.pdf?raw=true