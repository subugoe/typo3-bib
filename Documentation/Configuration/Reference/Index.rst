

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


Reference
^^^^^^^^^


plugin.tx\_bib\_pi1
"""""""""""""""""""""""""

There are many TypoScript variables that adjust the behaviour of the
list view plugin. At this place only the most important will be
listed. For a more complete list check the static include file
EXT:bib/pi1/static/default/setup.txt.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         authors
   
   b
         array
   
   c
         -> authors.
         
         Author string configuration
   
   d


.. container:: table-row

   a
         authorNav
   
   b
         Array
   
   c
         Configures the author navigation bar
         
         -> authorNav
   
   d


.. container:: table-row

   a
         charset
   
   b
         String
   
   c
         The character set that will be used in some operations
   
   d
         UTF-8


.. container:: table-row

   a
         debug
   
   b
         boolean
   
   c
         Enables the debug mode.
         
         This prints a lot debug information in the frontend.
   
   d


.. container:: table-row

   a
         display\_mode
   
   b
         integer
   
   c
         The list display mode.
         
         0: Simple1: Year split2: Year navigation
         
         This is only used if “ *TypoScript* ”is selected in the FlexForm.
   
   d
         0


.. container:: table-row

   a
         classes
   
   b
         Array
   
   c
         -> classes.
         
         Some CSS classes
   
   d


.. container:: table-row

   a
         date\_sorting
   
   b
         integer
   
   c
         The date sorting rule.
         
         0: descending1: ascending
         
         This is only used if “ *TypoScript* ”is selected in the FlexForm.
   
   d
         0


.. container:: table-row

   a
         editor
   
   b
         Array
   
   c
         -> editor.
         
         The FE editor configuration.
   
   d


.. container:: table-row

   a
         enum
   
   b
         Array
   
   c
         -> enum.
         
         Enumeration wraps
   
   d


.. container:: table-row

   a
         enum\_style
   
   b
         integer
   
   c
         The enumeration style.
         
         1: Count all on page2: Count all3: Bullet4: None5: File Icon
         
         This is only used if “ *TypoScript* ”is selected in the FlexForm.
   
   d
         2


.. container:: table-row

   a
         export
   
   b
         Array
   
   c
         -> export.
         
         The export link configuration.
   
   d


.. container:: table-row

   a
         FE\_edit\_groups
   
   b
         String
   
   c
         A comma separated list of frontend user group ids that are allowed to
         access the frontend editor. Also can be 'all' to allow all fe groups.
   
   d


.. container:: table-row

   a
         field
   
   b
         Array
   
   c
         -> field.
         
         Contains stdWraps for all printable database fields.
   
   d


.. container:: table-row

   a
         import
   
   b
         Array
   
   c
         -> import.
         
         The import link configuration.
   
   d


.. container:: table-row

   a
         items\_per\_page
   
   b
         integer
   
   c
         The maximal number of items (references) that should be displayed on a
         page at once.
         
         0 means all.
         
         It is used only if the FlexForm option is set to -1.
   
   d
         12


.. container:: table-row

   a
         max\_authors
   
   b
         integer
   
   c
         The maximal number of authors to display.
         
         0 means all
         
         It is used only if the FlexForm option is set to -1.
   
   d
         0


.. container:: table-row

   a
         pageNav
   
   b
         Array
   
   c
         -> pageNav
         
         Configures the page navigation bar
   
   d


.. container:: table-row

   a
         prefNav
   
   b
         Array
   
   c
         -> prefNav
         
         Configures the preferences navigation bar
   
   d


.. container:: table-row

   a
         reference
   
   b
         stdWrap
   
   c
         A wrap around the reference data parts. Replaces the
         ###REFERENCE\_WRAP### markers.
   
   d


.. container:: table-row

   a
         restrictions
   
   b
         Array
   
   c
         -> restrictions
         
         Restricts some content to user groups and content type
   
   d


.. container:: table-row

   a
         searchNav
   
   b
         Array
   
   c
         Configures the search navigation bar
         
         -> searchNav
   
   d


.. container:: table-row

   a
         single\_view
   
   b
         Array
   
   c
         Configures the single view
         
         -> single\_view
   
   d


.. container:: table-row

   a
         stat\_mode
   
   b
         integer
   
   c
         The statistics mode
         
         0: None1: Total2: Year / Total
         
         This is only used if “ *TypoScript* ”is selected in the FlexForm.
   
   d
         0


.. container:: table-row

   a
         template
   
   b
         resource
   
   c
         The HTML template file for the list view
   
   d


.. container:: table-row

   a
         yearNav
   
   b
         Array
   
   c
         The (visual) year navigation configuration. See the static include
         file for more details.
   
   d


.. ###### END~OF~TABLE ######

Examples:

- Set the html-template
  
  plugin.tx\_bib\_pi1 {
  
  template = /fileadmin/templates/my\_bib.tmpl
  
  }

- Change the all number wrap

plugin.tx\_bib\_pi1 {

enum.all.wrap = <span>\|</span>

}


plugin.tx\_bib\_pi1.authors
"""""""""""""""""""""""""""""""""

This array contains some values that influence the author string
generation.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         forename
   
   b
         stdWrap
   
   c
         Forename wrap
   
   d


.. container:: table-row

   a
         highlight
   
   b
         stdWrap
   
   c
         Author highlightning wrap
   
   d


.. container:: table-row

   a
         separator
   
   b
         string/stdWrap
   
   c
         Author separator and its wrap
   
   d


.. container:: table-row

   a
         surname
   
   b
         stdWrap
   
   c
         Surname wrap
   
   d


.. container:: table-row

   a
         template
   
   b
         string
   
   c
         Full author name template string. Should contain ###FORENAME### and
         ###SURNAME###. The default string expects a spacing wrap of around the
         surname.
   
   d
         ###FORENAME######SURNAME###


.. container:: table-row

   a
         url\_icon\_fields
   
   b
         string
   
   c
         A comma separated list of fields that must be set before a link can be
         created.
   
   d
         url


.. container:: table-row

   a
         url\_icon\_file
   
   b
         Resource
   
   c
         The image file to use as a link icon
   
   d


.. container:: table-row

   a
         url\_icon
   
   b
         stdWrap
   
   c
         The wrap around the icon image.
   
   d


.. ###### END~OF~TABLE ######

In this example the author field fe\_user\_id is used to create a link

plugin.tx\_bib\_pi1.authors {

url\_icon\_fields = fe\_user\_id

url\_icon.typolink {

\# Unset default value

parameter.field >

\# A Page id

parameter = 42

\# Append the fe\_user\_id parameter

additionalParams.field = fe\_user\_id

additionalParams.wrap = &tx\_feuserlisting\_pi1[showUid]=\|

}

}


plugin.tx\_bib\_pi1.authorNav
"""""""""""""""""""""""""""""""""""

Here the author navigation bar is configured. Not all values are
listed.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         template
   
   b
         Resource
   
   c
         The HTML template
   
   d


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.pageNav
"""""""""""""""""""""""""""""""""

Here the page navigation bar is configured. Not all values are listed.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         template
   
   b
         Resource
   
   c
         The HTML template
   
   d


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.prefNav
"""""""""""""""""""""""""""""""""

Here the preferences navigation bar is configured. Not all values are
listed.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         template
   
   b
         Resource
   
   c
         The HTML template
   
   d


.. container:: table-row

   a
         ipp\_values
   
   b
         String
   
   c
         A comma separated listof available 'items per page' options
   
   d
         5,10,20,30,40,50


.. container:: table-row

   a
         ipp\_default
   
   b
         Integer
   
   c
         The default items per page value
   
   d
         10


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.searchNav
"""""""""""""""""""""""""""""""""""

Here the search navigation bar is configured. Only the most important
values are listed.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         template
   
   b
         Resource
   
   c
         The HTML template
   
   d


.. container:: table-row

   a
         abstracts.def
   
   b
         Bool
   
   c
         Default value for 'Search in abstract'
   
   d
         0


.. container:: table-row

   a
         clear\_start
   
   b
         Bool
   
   c
         If set to 1 no references will be shown when no search phrase is given
   
   d
         0


.. container:: table-row

   a
         extra.def
   
   b
         Bool
   
   c
         Default value for 'Advanced search'
   
   d
         0


.. container:: table-row

   a
         full\_text.def
   
   b
         Bool
   
   c
         Default value for 'Search in Full text'
   
   d
         0


.. container:: table-row

   a
         rule.def
   
   b
         String
   
   c
         Default value for the search rule. Can be'AND' or 'OR'
   
   d
         AND


.. container:: table-row

   a
         separator.def
   
   b
         String
   
   c
         Default value for the search string separator. Can be 'none', 'space',
         'semi' or 'pipe'
   
   d
         space


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.field
"""""""""""""""""""""""""""""""

Most fields can be wrapped with stdWrap. Additionally this default
wrap can be overridden for specific bibtypes with a
plugin.tx\_bib\_pi1.field.BIBTYPE.FIELDstatement.

The following example wraps the organization field with a <span> but
(and only) in case of a book with a <div>.

plugin.tx\_bib\_pi1.field {

organization.wrap = <span>\|</span>

book.organization.wrap = <div>\|</div>

}

Basically every field can be wrapped. Here is a list of fields that
are created from the database or have a different name there.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         author
   
   b
         stdWrap
   
   c
         Each author
   
   d


.. container:: table-row

   a
         authors
   
   b
         stdWrap
   
   c
         All authors
   
   d


.. container:: table-row

   a
         auto\_url
   
   b
         stdWrap
   
   c
         The automatically generated url
   
   d


.. container:: table-row

   a
         auto\_url\_short
   
   b
         stdWrap
   
   c
         The automatically generated shortened url
   
   d


.. container:: table-row

   a
         editor\_each
   
   b
         stdWrap
   
   c
         Each editor
   
   d


.. container:: table-row

   a
         file\_url\_short
   
   b
         stdWrap
   
   c
         The short file url
   
   d


.. container:: table-row

   a
         web\_url\_short
   
   b
         stdWrap
   
   c
         The short web url
   
   d


.. container:: table-row

   a
         web\_url2\_short
   
   b
         stdWrap
   
   c
         The short web url 2
   
   d


.. ###### END~OF~TABLE ######

To wrap the forename or surname check the
plugin.tx\_bib\_pi1.authorsarray.

To avoid (or create?) confusion it may happen that in the output
something looks like a field wrap but actually is defined in the html
templated used by bib.

More examples:

- Wrap  **title** and the whole  **authors** string and each  **author**

plugin.tx\_bib\_pi1 {

field.title.wrap = TITLE --- \| --- TITLE

field.authors.wrap = AUTHORS --- \| --- AUTHORS

field.author.wrap = <b>\|</b>

}


plugin.tx\_bib\_pi1.restrictions.TABLE.FIELD\_NAME
""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Each field from each bib tables can be restricted to specific FE
user groups. The table names are not literally but abbreviated with

- 'ref' for 'tx\_bib\_references' and

- 'authors' for 'tx\_bib\_authors'

The interpreted restriction values are listed below.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         hide\_all
   
   b
         Bool
   
   c
         Allways hide this field. The fields is revealed when FE\_user\_groups
         is set and matches.
   
   d


.. container:: table-row

   a
         hide\_file\_ext
   
   b
         String
   
   c
         Hide on string ending. Comma separated values of string endings. E.g.
         '.pdf,.ppt'
   
   d


.. container:: table-row

   a
         FE\_user\_groups
   
   b
         String
   
   c
         'all' or a comma separated list of fe\_user group ids. E.g. '12,45,76'
   
   d


.. ###### END~OF~TABLE ######

Examples:

- file\_url only gets displayed if it does not end with '.pdf' or '.ppt'

plugin.tx\_bib\_pi1 {

restrictions.ref {

file\_url.hide\_file\_ext = .pdf,.ppt

}

}

- The same but now all logged in frontend users are allowed to see the
  file links

plugin.tx\_bib\_pi1 {

restrictions.ref {

file\_url.hide\_file\_ext = .pdf,.ppt

FE\_user\_groups = all

}

}

- This is a more elaborated example where many fields are hidden for
  users that are not logged in.

temp.bib\_fe\_grp = 2

temp.bib\_restrict {

hide\_all = 1

FE\_user\_groups < temp.bib\_fe\_grp

}

plugin.tx\_bib\_pi1 {

restrictions {

ref {

file\_url < temp.bib\_restrict

DOI < temp.bib\_restrict

web\_url < temp.bib\_restrict

web\_url2 < temp.bib\_restrict

}

authors {

url < temp.bib\_restrict

fe\_user\_id < temp.bib\_restrict

}

}

}


plugin.tx\_bib\_pi1.classes
"""""""""""""""""""""""""""""""""

Here some CSS classes are defined.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         author\_highlight
   
   b
         CSS class
   
   c
         CSS class for (filtered) author highlightning.
   
   d
         tx\_bib-author\_hl


.. container:: table-row

   a
         even
   
   b
         CSS class
   
   c
         CSS class for even rows.
   
   d
         tx\_bib-item\_even


.. container:: table-row

   a
         odd
   
   b
         CSS class
   
   c
         CSS class for odd rows.
   
   d
         tx\_bib-item\_odd


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.single\_view
""""""""""""""""""""""""""""""""""""""

This array configures the single view.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         all\_labels
   
   b
         stdWrap
   
   c
         Wraps the field labels
   
   d
         wrap = \|:


.. container:: table-row

   a
         dont\_show
   
   b
         String
   
   c
         A list of comma separated field names that should not be displayed
   
   d
         uid,pid,in\_library,...
         
         Check the static include file


.. container:: table-row

   a
         field\_wrap
   
   b
         Array
   
   c
         This is the same as the tx\_bib\_pi1.field array but overrides
         the values from there for the dingle view.
   
   d


.. container:: table-row

   a
         template
   
   b
         Resource
   
   c
         Defines the HTML template file
   
   d
         EXT:bib/res/templates/single\_view.html


.. container:: table-row

   a
         title
   
   b
         stdWrap
   
   c
         Wrap for the title string in the HTML template
         ###SINGLE\_VIEW\_TITLE###
   
   d


.. container:: table-row

   a
         post\_text
   
   b
         String / stdWrap
   
   c
         Some text & wrap can be set here. It will be inserted in the HTML
         template for the marker ###POST\_TEXT###
   
   d
         empty


.. container:: table-row

   a
         pre\_text
   
   b
         String / stdWrap
   
   c
         The same as post\_text but for the marker ###PRE\_TEXT###
   
   d
         empty


.. ###### END~OF~TABLE ######

The following example wraps the title in the list view with a link to
the single view.

Inside the single view the wrap gets overridden with a wrap that does
nothing since a link there would not make too much sense.


plugin.tx\_bib\_pi1 {

\# Wraps the title with a link to the single view

field {

title.single\_view\_link = 1

}

single\_view {

\# Overrides any wrap from field.title

field\_wrap.title.wrap = \|

}

}


plugin.tx\_bib\_pi1.editor
""""""""""""""""""""""""""""""""

These options configure the frontend editor and the list view in edit
mode.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         default\_pid
   
   b
         integer
   
   c
         The uid of the default publication storage folder. This is useful if
         there are multiple storages to read from but only one into which new
         publications should go.
   
   d


.. container:: table-row

   a
         delete\_no\_ref\_authors
   
   b
         Bool
   
   c
         Delete authors without publications after a publication save
   
   d
         1


.. container:: table-row

   a
         field\_default.XXX
   
   b
         Array
   
   c
         This array defines the default values for new entries.
         
         E.g.
         
         field\_default.in\_library = 1
         
         sets all new entries to be in the library. This works for most fields
         but not for the authors field.
   
   d


.. container:: table-row

   a
         full\_text
   
   b
         Array
   
   c
         Configures the full text extraction
         
         ->full\_text
   
   d


.. container:: table-row

   a
         list
   
   b
         Array
   
   c
         Configures the list view in edit mode
   
   d


.. container:: table-row

   a
         no\_edit
   
   b
         Array
   
   c
         Configures which fields should not be allowed to be edited -> no\_edit
   
   d


.. container:: table-row

   a
         no\_show
   
   b
         Array
   
   c
         Configures which fields should not be shown
         
         -> no\_show
   
   d


.. container:: table-row

   a
         warnings
   
   b
         Array
   
   c
         Which data checks should be performed before save -> warnings
   
   d


.. ###### END~OF~TABLE ######

Here is an example

plugin.tx\_bib\_pi1.editor {

\# This is convenient when multiple storages are selected

default\_pid = 12345

field\_default {

note = This book is in the library

in\_library = 1

}

}


plugin.tx\_bib\_pi1.editor.full\_text
"""""""""""""""""""""""""""""""""""""""""""

These options configure the full text extraction from PDF files to the
full\_text field which can be searched.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         pdftotext\_bin
   
   b
         Resource
   
   c
         Absolute path to the pdftotext binary
   
   d
         /usr/bin/pdftotext


.. container:: table-row

   a
         tmp\_dir
   
   b
         Directory
   
   c
         Directory where temporary text files will be created
   
   d
         /tmp


.. container:: table-row

   a
         max\_num
   
   b
         Integer
   
   c
         The maximal number of caches to update in one turn.
   
   d
         100


.. container:: table-row

   a
         max\_sec
   
   b
         Integer
   
   c
         The maximal number of seconds to spend for updating full text caches.
   
   d
         5


.. container:: table-row

   a
         update
   
   b
         Bool
   
   c
         Enables automatic text extraction from PDFs during save or import of
         publication references by the FE editor.
   
   d
         0


.. ###### END~OF~TABLE ######

Here is an example


plugin.tx\_bib\_pi1.editor.full\_text {

\# Activate text extraction for full\_text field

update = 1

\# Set a custom pdftotext binary

pdftotext\_bin = /usr/local/bin/pdftotext

}


plugin.tx\_bib\_pi1.editor.list
"""""""""""""""""""""""""""""""""""""

Here the behaviour of the list view in the edit mode is configured

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         warnings.file\_nexist
   
   b
         Bool
   
   c
         Enable a 'file does not exist' warning if a local file does not exist
   
   d
         1


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.editor.no\_edit
"""""""""""""""""""""""""""""""""""""""""

With no\_edit fields can be set not editable in the FE editor. The
fields and values still get displayed. Have a look at the no\_show
variable if fields should be hidden completely.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         FIELD\_NAME
   
   b
         Bool
   
   c
         If set to 1 then the field FIELD\_NAME can't be edited in the FE
         editor
   
   d
         0


.. ###### END~OF~TABLE ######

Here is an example


plugin.tx\_bib\_pi1.editor.no\_edit {

\# The citeid should not be touched by editors

citeid = 1

}


plugin.tx\_bib\_pi1.editor.no\_show
"""""""""""""""""""""""""""""""""""""""""

no\_show allows to hide fields in the FE editor completely.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         FIELD\_NAME
   
   b
         Bool
   
   c
         If set to 1 then the field FIELD\_NAME doesn't show up in the FE
         editor
   
   d
         0


.. ###### END~OF~TABLE ######

An example


plugin.tx\_bib\_pi1.editor.no\_show {

\# We don't use the library fields, so please hide them

extern = 1

reviewed = 1

in\_library = 1

borrowed\_by = 1

}


plugin.tx\_bib\_pi1.editor.warnings
"""""""""""""""""""""""""""""""""""""""""

Before saving the FE editor performs some data checks. These can be
enabled/disabled here.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         empty\_fields
   
   b
         Bool
   
   c
         Check if all required fields have a value
   
   d
         1


.. container:: table-row

   a
         file\_nexist
   
   b
         Bool
   
   c
         Check if a local file in 'file\_url' exists
   
   d
         1


.. container:: table-row

   a
         double\_citeid
   
   b
         Bool
   
   c
         Check if an other reference with the same citeid exists
   
   d
         1


.. ###### END~OF~TABLE ######

Here is an example


plugin.tx\_bib\_pi1.editor.warnings {

\# Disable the 'empty fields' check

empty\_fields = 0

}


plugin.tx\_bib\_pi1.enum
""""""""""""""""""""""""""""""

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         all
   
   b
         String/stdWrap
   
   c
         Wrap for 'Count all' enumeration
   
   d
         String: ###I\_ALL###
         
         Wrap: \|.


.. container:: table-row

   a
         bullet
   
   b
         String/stdWrap
   
   c
         Wrap for 'Bullet' enumeration
   
   d
         String: &bull;
         
         Wrap: \|


.. container:: table-row

   a
         empty
   
   b
         String/stdWrap
   
   c
         Wrap for 'Empty' enumeration. (Not used)
   
   d


.. container:: table-row

   a
         file\_icon
   
   b
         String/stdWrap
   
   c
         Wrap for 'File Icon' enumeration
   
   d
         String: ###FILE\_URL\_ICON###
         
         Wrap: <div class="tx\_bib-file\_url\_icon">\|</div>


.. container:: table-row

   a
         file\_icon\_image
   
   b
         stdWrap
   
   c
         Wrap for the link image
   
   d
         A typolink for 'file\_url'


.. container:: table-row

   a
         page
   
   b
         String/stdWrap
   
   c
         Wrap for 'Count page' enumeration
   
   d
         String: ###I\_PAGE###
         
         Wrap: \|.


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.export
""""""""""""""""""""""""""""""""

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         enable\_export
   
   b
         Comma separated values
   
   c
         Which export links should be available in the list view:
         
         'bibtex' - BibTeX
         
         'xml' - Bib XML
   
   d
         bibtex,xml


.. container:: table-row

   a
         FE\_groups\_only
   
   b
         String
   
   c
         This can be a comma separated list of FE user group uids which are
         allowed to export only.
         
         Can also be 'all' to allow any logged in user.
   
   d


.. container:: table-row

   a
         bibtex
   
   b
         stdWrap
   
   c
         The wrap for the BibTeX export link
   
   d


.. container:: table-row

   a
         label
   
   b
         String & stdWrap
   
   c
         The export label and its wrap
   
   d


.. container:: table-row

   a
         path
   
   b
         resource
   
   c
         The path where the export files will be stored
   
   d


.. container:: table-row

   a
         separator
   
   b
         String & stdWrap
   
   c
         The export link separator string and its wrap
   
   d


.. container:: table-row

   a
         xml
   
   b
         stdWrap
   
   c
         The wrap for the XML export link
   
   d


.. ###### END~OF~TABLE ######


plugin.tx\_bib\_pi1.import
""""""""""""""""""""""""""""""""

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   a
         Property:
   
   b
         Data type:
   
   c
         Description:
   
   d
         Default:


.. container:: table-row

   a
         bibtex
   
   b
         stdWrap
   
   c
         The wrap for the BibTeX import link
   
   d


.. container:: table-row

   a
         label
   
   b
         String & stdWrap
   
   c
         The import label and its wrap
   
   d


.. container:: table-row

   a
         separator
   
   b
         String & stdWrap
   
   c
         The import link separator string and its wrap
   
   d


.. container:: table-row

   a
         xml
   
   b
         stdWrap
   
   c
         The wrap for the XML import link
   
   d


.. ###### END~OF~TABLE ######

