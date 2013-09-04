.. include:: Images.txt

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


The list view plugin
^^^^^^^^^^^^^^^^^^^^

|img-7|

The frontend list view plugin shows a list of publication references
and optionally offers an editor for adding new and editing existing
publication references.

Before inserting the plugin into a page the default configuration and
the default style should be loaded into the page template. To do so
open the TypoScript template of the base page and go to the field
**Include static (from extensions)** . Here the following two items
should be listed.

- Publication list defaults (bib)

- Publication list CSS (bib)

Select both to get the default style.

|img-8|

Now go the page where the list should appear and insert the
**Publication list** frontend plugin. The most important thing to
configure afterwards is the storage folder for publication references.
Simply chose a storage folder in the  **Startingpoint** field of the
plugin. You can also select multiple storage folders or use recursive
folder lookup (enable secondary options). If no folder is selected the
current page will be used.

|img-9|


The "General" configuration sheet
"""""""""""""""""""""""""""""""""

|img-10|

The general arrangement of the references in the list is defined by
the  **List type** .

**TypoScript (default)**

The list type is read from the
variableplugin.tx\_bib\_pi1.display\_mode

**Simple**

A simple top down list.

**Year split**

A simple top down list with year separators that show the year of the
following references respectively.

**Year navigation**

This shows only publications of a selected year and a navigation bar
where the year can be chosen.

The  **Enumeration style** defines the symbol or number that will show
up in front of each references.

**TypoScript (default)**

The enumeration style is determined from the
variableplugin.tx\_bib\_pi1.enum\_style

**Count (page)**

The running number of all references on the current page. In the
simple mode this means all references that pass the filter whereas in
the Year navigation mode this means all publications in the selected
year.

**Count (all)**

In the simple mode this equivalent to  **Count (page)** whereas in the
**Year navigation** mode this is the running number of all
publications in  *all* years.

**Bullet**

This shows a single bullet.

**None**

This shows no symbol at all.

**File Icon**

This a file icon which is a link when file\_url is set

**Show search** enables the search bar.

**Show author browser** enables the author browser bar.

**Show preferences bar** enables the preferences bar which e. g.
allows to select the number of displayed references per page.

By default the references are sorted with respect to the following
database fields.

#. year

#. bibtype

#. month

#. day
   
   If  **Split up bibliography types** is selected the sorting is
   performed in th following order

#. bibtype

#. year

#. month

#. day

and a bibliography type separator is inserted for each bibliography
type. The  **Date sorting** determines if the sorting by the date
fields is performed ascending or descending. **TypoScript (default)**

The date sorting order is read from the
variableplugin.tx\_bib\_pi1.date\_sorting

**Descending**

Descending sorting (newest on top)

**Ascending**

Ascending sorting (oldest on top)

Only if  **Show abstracts** is selected abstracts of references will
be displayed.

**Nr. of references per page** can be set to a value greater than zero
to not show all references at once but to show them page wise. The
value determines the number of references on each page. A value of
zero means to show all references and if the value is -1 then the
number will be read from the TS variable
plugin.tx\_bib\_pi1.items\_per\_page.

Some publications have a long authors list which can be stripped down
to only the first nauthors by  **Nr. of authors to display** . In case
a publication has more authors than that the exceeding authors will be
abbreviated with  *et al.* . A value of zero means to show all authors
and if the value is -1 then the number will be read from the TS
variable plugin.tx\_bib\_pi1.max\_authors.

Some statisticscan be displayed at the bottom of the publication list.
The  **Statistic mode** determines what should be displayed.

**TypoScript (default)**

The statistic mode is read from the
variableplugin.tx\_bib\_pi1.stat\_mode

**None**

No statistics

**Total**

Show the total number of publications

**Year / Total**

Show number of publications in this year and the total number of
publications. This mode only works in the **Year Navigation** list
type. It falls back to **Total** in all other list types.

The  **Enable export links** switch enables the export links at the
bottom of a publication list.


The "Filter" configuration sheet
""""""""""""""""""""""""""""""""

|img-11|

In the  **Filter** sheet several filters can be applied to the
publication list to e. g. select only publications of a certain author
in a certain year range. Thereby each filter has to be enabled with a
check button and configured by one or more rule fields. Most of the
filter rule fields are quite self explaining.

**Year(s)**

Years and year ranges must be comma separated. A year is simly a year
e. g. 2008but a year range can have one of three different
appearances.

- 2001-2012: All references from inclusive 2001 to inclusive 2012.

- -2002: All references up to inclusive 2002.

- 2004-: All references from inclusive 2004 on.

Year matches are interpreted as "The publication was in one of the
given years". A year filter rule could look like this

2003, -2002, 2004, 2005-2006

which is an inefficient version of

-2006

**Author(s)**

Author names must be separated by a new line.

- If no comma is present in a line then the string is interpreted as the
  surname.

- If a comma is present then the string before the comma is interpreted
  as the surnameand the string behind the comma is interpreted as the
  forenameor given name.

If the  **Author filter rule** is  **OR** a publication must have at
least one of the authors to be displayed. If it is  **AND** then the
publication list must include all of the authors to be displayed.

The selected authors can be highlighted if  **Highlight selected
authors** is activated. This even works when the author filter is not
enabled.


The "FE editor" configuration sheet
"""""""""""""""""""""""""""""""""""

|img-12|

This sheets configures the frontend editor which is described in more
detail later.

