

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


PublicationByCiteId
^^^^^^^^^^^^^^^^^^^

This Fluid ViewHelper returns an array of a publication by a given citation id.

Usage:

First: Declare the namespace for this extension:

::

  {namespace bib=Ipf\Bib\ViewHelpers}

Than create an alias block for the result and call the properties inside this block:

::

  <f:alias map="{bib:\"{bib:publicationByCiteId(citeId:'2r')}\"}">
    {bib.publisher}
  </f:alias>

