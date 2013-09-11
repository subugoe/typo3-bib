

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


Useful SQL queries
^^^^^^^^^^^^^^^^^^

The following SQL queries may be handy to analyse or modify the
publication reference database.

- *Show all author names*

::

  SELECT pid,surname,forename
  FROM tx_bib_domain_model_authors
  ORDER BY pid,surname;

- *Show the authors of a publication with id XXX*

::

  SELECT tx_bib_domain_model_authors.surname,tx_bib_domain_model_authors.forename
  FROM tx_bib_domain_model_authors
  JOIN tx_bib_domain_model_authorships
  ON tx_bib_domain_model_authorships.author_id=tx_bib_domain_model_authors.uid
  JOIN tx_bib_domain_model_references
  ON tx_bib_domain_model_authorships.pub_id=tx_bib_domain_model_references.uid
  WHERE tx_bib_domain_model_references.uid=XXX
  ORDER BY tx_bib_domain_model_authorships.sorting;

- *Show the number of authors per publication*

::

  SELECT tx_bib_domain_model_references.uid,count(tx_bib_domain_model_authors.uid)
  FROM tx_bib_domain_model_authors
  JOIN tx_bib_domain_model_authorships
  ON tx_bib_domain_model_authorships.author_id=tx_bib_domain_model_authors.uid
  JOIN tx_bib_domain_model_references
  ON tx_bib_domain_model_authorships.pub_id=tx_bib_domain_model_references.uid
  WHERE tx_bib_domain_model_references.deleted=0
  GROUP BY tx_bib_domain_model_references.uid;

- *Show the number of publications per author*

::

  SELECT tx_bib_domain_model_authors.pid,
  tx_bib_domain_model_authors.surname,
  tx_bib_domain_model_authors.forename,
  count(tx_bib_domain_model_references.uid)
  FROM tx_bib_domain_model_authors
  JOIN tx_bib_domain_model_authorships
  ON tx_bib_domain_model_authorships.author_id=tx_bib_domain_model_authors.uid
  JOIN tx_bib_domain_model_references
  ON tx_bib_domain_model_authorships.pub_id=tx_bib_domain_model_references.uid
  WHERE tx_bib_domain_model_references.deleted=0
  GROUP BY tx_bib_domain_model_authors.uid
  ORDER BY tx_bib_domain_model_authors.pid,
  tx_bib_domain_model_authors.surname;

