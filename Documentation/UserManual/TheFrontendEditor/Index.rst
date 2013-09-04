

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


The frontend editor
^^^^^^^^^^^^^^^^^^^

The frontend editor can be either used by logged in backend users or
by frontend users that belong to a certain group which must be
configured in the TypoScript template. In either case

- the editor must be enabled in the list view plugin configuration
  sheet.


Activation and configuration in the backend FlexForm
""""""""""""""""""""""""""""""""""""""""""""""""""""

Once the list view plugin is inserted into a page open the plugin
configuration and select the sheet  **FE Editor** . Activate the check
button named  **Enable editor?** to enable the editor on this page.

The cite id generation options in the  **Fe editor** sheet control
whether the citeid of a publication should be generated automatically
on demand by a button press or not at all. This can be configured for
new and existing entries separately.

It is always a good idea to clear the page cache of the current page
after editing or inserting a reference. Only then it will show up for
all viewers of the web page immediately. To let the editor clear the
page cache of the current page automatically activate the check button
named  **Clear page cache after editing?** .


Getting to the editor as a backend user
"""""""""""""""""""""""""""""""""""""""

Simply logs in into the backend and then opens a second window or tab
for the frontend list view where the edit icons should turn up.
Sometimes a forced reload of the frontend page is required to make the
edit icons appear (caching). To do so hold down the  **Shift** key and
click the  **Reload** button of your browser.

Some notes for the administrator.

The user group must have modification rights for the bib tables.
This must be enabled in the TYPO3 group administration.

Generally the listing of bib tables should be disabled since
this would encourage users to use the backend to edit Publications.
Because of the partially normalized database layout this could mess up
the bib database.


Getting to the editor as a frontend user
""""""""""""""""""""""""""""""""""""""""

As a frontend user simply log in and go to the page where the list
view plugin is installed. The edit icons should now appear beneath the
publication reference entries. Sometimes a forced reload of the
frontend page is required to make the edit icons turn up (caching). To
do so hold down the  **Shift** key and click the  **Reload** button of
your browser.

Some notes for the administrator.

The frontend user groups that should be allowed to use the editor must
be configured in the TypoScript template by setting the variable

::

  plugin.tx_bib_pi1.FE_edit_groups

to a comma separated list of frontend user group ids or to 'all'.

Here is an example that allows users in the group 34, 76 and 145 to
use the editor.

::

  plugin.tx_bib_pi1 {
    FE_edit_groups = 34,76,145
  }


Using the editor
""""""""""""""""

Once you're logged in into the backend reload the publication page in
a second window and some edit icons should turn up.

**Creating a new reference**

In the right top of the publication page (plugin) there should turn up
a plus icon. Click it to add a new publication reference.

**Edit/Hide a reference**

Beneath each reference there should be two buttons. One is for
*hiding* the reference the other one is for  *editing* the reference.

**Deleting a references**

Only the frontend reference editor holds a button which will delete a
reference.

Both saving and deletion of a publication reference must be confirmed
in the editor.

The reference editor itself should be quite self explaining.


Custom citeid generators
""""""""""""""""""""""""

There is one default citeid generator provided with this plugin but
you can create your own as well. To do so create a copy of the example
citeid creator file
EXT:bib/res/class.tx\_bib\_citeid\_generator\_ext.phpand
adjust it to your needs. Don't edit the name of the generator class
since this is the class name bib will look for. Then tell
bib with the TypoScript variable

::

  plugin.tx_bib_pi1.editor.citeid_generator_file

where your generator is stored. Here is a TypoScript example.

::

  plugin {
    tx_bib_pi1 {
      editor {
        citeid_generator_file = /fileadmin/my_citeid_generator.php
      }
    }
  }
