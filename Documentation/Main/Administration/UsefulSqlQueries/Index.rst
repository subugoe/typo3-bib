

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
  FROM tx_bib_domain_model_author
  ORDER BY pid,surname;

- *Show the authors of a publication with id XXX*

::

  SELECT tx_bib_domain_model_author.surname,tx_bib_domain_model_author.forename
  FROM tx_bib_domain_model_author
  JOIN tx_bib_domain_model_authorships
  ON tx_bib_domain_model_authorships.author_id=tx_bib_domain_model_author.uid
  JOIN tx_bib_domain_model_reference
  ON tx_bib_domain_model_authorships.pub_id=tx_bib_domain_model_reference.uid
  WHERE tx_bib_domain_model_reference.uid=XXX
  ORDER BY tx_bib_domain_model_authorships.sorting;

- *Show the number of authors per publication*

::

  SELECT tx_bib_domain_model_reference.uid,count(tx_bib_domain_model_author.uid)
  FROM tx_bib_domain_model_author
  JOIN tx_bib_domain_model_authorships
  ON tx_bib_domain_model_authorships.author_id=tx_bib_domain_model_author.uid
  JOIN tx_bib_domain_model_reference
  ON tx_bib_domain_model_authorships.pub_id=tx_bib_domain_model_reference.uid
  WHERE tx_bib_domain_model_reference.deleted=0
  GROUP BY tx_bib_domain_model_reference.uid;

- *Show the number of publications per author*

::

  SELECT tx_bib_domain_model_author.pid,
  tx_bib_domain_model_author.surname,
  tx_bib_domain_model_author.forename,
  count(tx_bib_domain_model_reference.uid)
  FROM tx_bib_domain_model_author
  JOIN tx_bib_domain_model_authorships
  ON tx_bib_domain_model_authorships.author_id=tx_bib_domain_model_author.uid
  JOIN tx_bib_domain_model_reference
  ON tx_bib_domain_model_authorships.pub_id=tx_bib_domain_model_reference.uid
  WHERE tx_bib_domain_model_reference.deleted=0
  GROUP BY tx_bib_domain_model_author.uid
  ORDER BY tx_bib_domain_model_author.pid,
  tx_bib_domain_model_author.surname;

