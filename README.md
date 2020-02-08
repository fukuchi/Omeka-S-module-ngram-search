N-gram search (module for Omeka S)
==================================

N-gram search is a module for [Omeka S](https://omeka.org/s/) that enables
CJK-ready full-text search using MySQL's n-gram tokenizer.

The default installation of the full-text search feature of the Omeka-S is not
CJK (Chinese, Japanese, Korean) ready because the apropriate tokenizer is not
used. This module simply activates n-gram tokenizer by modifying the table
information that internally used by Omeka-S.


Installation
------------

### Preparation

First of all, **backup the database**. This module modifies the table schema,
and that may cause unrecoverable failure.

This modules requires MySQL 5.6 or later. MariaDB currently does not provide
n-gram tokenizer. If you want to enable CJK-ready search with MariaDB, try
[Mroonga search](https://github.com/fukuchi/Omeka-S-module-mroonga-search)
instead.


### From ZIP

See the [release page](https://github.com/fukuchi/Omeka-S-module-ngram-search/releases)
and download the latest `NgramSearch.zip` from the list. Then unzip it in the
`modules` directory of Omeka-S, then enable the module from the admin
dashboard. Read the
[user manual of Omeka-S](https://omeka.org/s/docs/user-manual/modules/)
for further information.

### From GitHub

Please do not forget to rename the directory from `Omeka-S-ngram-search` to
`NgramSearch` in the `modules` directory.


Notes
-----

This module highly depends on the database structure of Omeka-S 2.x. If you are
upgrading Omeka-S from 2.x to 3.x or later, we highly recommend you to
uninstall this module **before upgrading**.

We have not heavily tested MySQL's n-gram tokenizer with large sized data yet.
For advanced full-text search, we recommend you to check the
[Solr module](https://omeka.org/s/modules/Solr/).


Licensing information
---------------------

Copyright (c) 2020 Kentaro Fukuchi

This module is released under the MIT License. See the `LICENSE` file for the
details.
