

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Database layout since version 1.0
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The bib database consists of three tables which are as follows.

- tx\_bib\_domain\_model\_reference

- tx\_bib\_domain\_model\_author

- tx\_bib\_domain\_model\_authorships

*tx\_bib\_domain\_model\_reference* contains most information e. g. title,
publisher, citeid, url, etc. but not the authors.

Author names are stored in *tx\_bib\_domain\_model\_author* and authorships of a publication are
stored in *tx\_bib\_domain\_model\_authorships*. This means *tx\_bib\_domain\_model\_authorships* connects between
*tx\_bib\_domain\_model\_reference* and *tx\_bib\_domain\_model\_author*.

