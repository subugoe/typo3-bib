

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


Searching in pdf files
^^^^^^^^^^^^^^^^^^^^^^

Bib allows to search in the text of pdf files by caching the
textual content of the pdf file in a database field. This requires a
pdf text extraction utility called  **pdftotext** to be installed on
the server which runs the TYPO3 system. **pdftotext** is part of the
**xpdf** or **xpdf-utils** package. Neither **xpdf** nor **pdftotext**
are part or extensions of TYPO3 or PHP but independent programs that
must be installed by the administrator of the underlying operating
system.

The automatic pdf text extraction can be enabled in the TypoScript
template. In the **Configuration/Reference** section there is a
description of the neccessary variable settings for that under

::

  plugin.tx_bib_pi1.editor.full_text.

Please note that the text extraction only works when using the
frontend editor. If the backend is used to edit od add a reference
entry then the pdf-text-cache-field does not get updated or filled at
all.

