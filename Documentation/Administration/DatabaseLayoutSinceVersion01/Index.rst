

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


Database layout since version 0.1
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The bib database consists of three tables which are as follows.

- tx\_bib\_domain\_model\_references

- tx\_bib\_domain\_model\_authors

- tx\_bib\_domain\_model\_authorships

tx\_bib\_domain\_model\_references contains most information e. g. title,
publisher, citeid, url, etc. but not the authors. Author names are
stored in tx\_bib\_domain\_model\_authors and authorships of a publication are
stored in tx\_bib\_domain\_model\_authorships. This means
tx\_bib\_domain\_model\_authorships connects between
tx\_bib\_domain\_model\_references and tx\_bib\_domain\_model\_authors.

