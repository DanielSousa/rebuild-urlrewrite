===================================
 Rebuild Url Rewrite for magento 2
===================================

Rebuild all product url rewrites.


Build Status
------------

+------------------------+-----------------------------------------------------------------------------------------------+
| **Master Branch**      |                                                                                               |
|                        | .. image:: https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/badges/quality-score.png?b=master |
|                        |    :target: https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/code-structure/master |
|                        | .. image:: https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/badges/build.png?b=master |
|                        |    :target: https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/code-structure/master |
+------------------------+-----------------------------------------------------------------------------------------------+


Installation
------------

```
$ composer require "danielsousa/magento2-module-urlrewrite":"^1.0"
```

Usage
-----

Rebuild all products urls

```bash
 bin/magento urlrewrite:rebuild:products
```

Rebuild one product 

```bash
 bin/magento urlrewrite:rebuild:products -p 23
```

Rebuild two or more products 

```bash
 bin/magento urlrewrite:rebuild:products -p 74,323,1234
```


Prerequisites
-------------

- PHP >= 7.0.*
- Magento >= 2.1.*