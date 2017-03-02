===================================
 Rebuild Url Rewrite for magento 2
===================================

Rebuild all product url rewrites.


Build Status
------------
**Master Branch**  
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/badges/build.png?b=master)](https://scrutinizer-ci.com/g/DanielSousa/rebuild-urlrewrite/build-status/master)


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

Rebuild all products urls from one store

```bash
 bin/magento urlrewrite:rebuild:products -s4
```

Rebuild one product url for one store

```bash
 bin/magento urlrewrite:rebuild:products -s1 -p 23
```

Rebuild one product url for all stores

```bash
 bin/magento urlrewrite:rebuild:products -p 23
```


Rebuild two or more products urls by store

```bash
 bin/magento urlrewrite:rebuild:products -s5 -p 74,323,1234
```


Prerequisites
-------------

- PHP >= 5.6.0
- Magento >= 2.1.*