----------------------------------------------------
  
            WikiCrowd installation guide

----------------------------------------------------

CONTENTS:
  I. SYSTEM REQUIREMENTS
 II. INSTALLATION
III. RELEASE NOTES
 IV. SUPPORT & CONTACTS
  V. LICENSE

I. SYSTEM REQUIREMENTS: 
  PHP 5.2 or higher with DOM, XSL and iconv support.
  

II. INSTALLATION:

  1. Copy install.php file to the path
     where you want to install WikiCrowd.

     For example: /var/www/wiki/install.php

  2. Access it from your browser.

  	 For example: http://mysite.com/wiki/install.php

  3. If your system environment doesn't fit to 
     WikiCrowd needs, installation will warn you.

  4. Correct web-site title, home page and support 
     e-mail address if required.
     
  5. Press "Install" button and enjoy :).


III. RELEASE NOTES:
  0.0.19  Fixed installation bug from 0.0.18.
  
  0.0.18  Fixed very specific bug with linking to a URL with UTF-8 characters.

  0.0.17  Fixed error detecting for installation on PHP less than 5.2.
          Fixed layout error.
          Wiki syntax update: replace -- with mdash.

  0.0.16  Error log for unexpected errors now stores to ./errors.txt -
          it will be helpful for researching errors on client installations.
          Rename pages feature (issue #21).
          Generate sitemap.xml file (issue #37).
          Additional syntax for links: [[link to somewhere]] (issue #35).
          New syntax for italic: //italic//. 
          Improved HTTP/404 page (now it's customizable by editing page ./error/404).
          Themes support.

  0.0.15  Paging on 'All changes' page.

  0.0.14  List unauthorized users in admin interface and
  		  grant them authorization (issue #29).
  		  Bugfix.

  0.0.13  New content block type @html for embed HTML support.
  		  Bugfix.

  0.0.12  List of all registered users.
		  Initial support of wiki syntax for bold, italic, 
		  subscript and superscript text (issue #9).
		  Added config property for changing license.
		  Page hierarchy implemented (issue #6).
		  Handle @subtitle and @subsubtitle (issue #14).
		  Simple image handling added with @img url (issue #15).

  0.0.11  Bugfix: @page[Internal link] doesn't work (issue #11).

  0.0.10  Bugfix of installation script and configuration panel.

  0.0.9   List of all changes.
          RSS channel for changes log.
          Usable wiki configuration for administrators.
          Bugfix.

  0.0.8   New positive logo :)
          Changed format of localization data.
          Access rights customization.
          Anonymous editing.
          Detect previous installation and update it.

  0.0.7   Localization (Russian and English languages impleented).
          E-mail change confirmation re-implemented to prevent account blocking.
          Improve installation with checking of newer version of WikiCrowd.
          Bugfix.

  0.0.6   Initial public version.

IV. SUPPORT & CONTACTS
  If you have any questions, feel free to ask me
  Stas Davydov <davidovsv@yandex.ru> or check
  the WikiCrowd FAQ for help on 
  http://code.google.com/p/wikicrowd/

  Submit feature requests and bugs to
  http://code.google.com/p/wikicrowd/issues/list

V. LICENSE
  WikiCrowd is under LGPL. http://www.gnu.org/licenses/lgpl.html

