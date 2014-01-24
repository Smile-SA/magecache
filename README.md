# MageCache

The module provides an integration of **MongoDB** into **Magento**. The first version handle product attributes and media galleries.

It has been developed and tested against **Magento EE 1.13**.


This module should be deployed on new project with huge catalog (> 100,000 products) since it allows significant reduction of the performance inpact of the EAV model by reducing dramatically the number of attributes stored into the database.


## Install MageCache

### Module install

The easiest way to install the module is to use the installer, by launching the following shell command from you Magento installation root folder :

    php < <(wget -O - https://raw.github.com/Smile-SA/magecache/master/installer.php)


The installation will be processed from the master branch. If you prefer to pick a specific release (v.1.0.0 by example), you can use this syntax to specify the release :

    php -- v.1.0.0 < <(wget -O - https://raw.github.com/Smile-SA/magecache/master/installer.php)

### Documentation

Read the guide available at : 
https://github.com/Smile-SA/magecache/blob/master/docs/Smile_PageCache%20-%20Guide.pdf?raw=true