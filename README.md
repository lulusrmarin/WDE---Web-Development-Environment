#  WDE Web Development Environment 0.9
PHP file browser and syntax-highlighting text editor
Uses Ace.js on top of PHP/AJAX using Angular

I found myself using this file more than my IDE (especially on 1 monitor environments).  It's usually faster to have this in one tab and my webpage in the other.  Every time I need a feature I add it in.

Features:
* Ability to select all themes from Ace.JS at will
* Saves files
* Creates new files
* Creates new folders with chmod option
* Deletes FIles
* Loads a tree/nodes of directories automatically focused on where the wde.php file lives
* Recognizes some hotkeys (ctrl+s to save, ctrl+/ to comment block etc...)
* Loads tabs of multiple files
* Much better looking.  Only has a dark theme for now

Changelog
* Rewritten from the gorund up to support tabs, new folders, and directory tree structure
* Added a status bar at the bottom of the page

Future Todo's:
Bugs:
* There is currently a bug where files are not saving in the intended directory.  I have a fix but it's not in this version
* Tippy is wired in but not well syced with Angular.  As with above probably needs a directive to sync

Features
* Re-add GET param password feature
* There is a bug where Angular is not binding the status messages returned by the API since they're being accessed by Ace.  This needs to be implemented in a better way
* Currently testing and experimenting with Github API to commit files, push/pull/etc directly from WDE
* Creating a one-file build for portability as well as a modular build of components
* Adding ability to work with Composer to automatically install scripts
* Add WebSSH if possible
* Make more modular, possibly switch from Angular to Vue
* Adding ability to copy/cut/paste files, as well as drag and drop abilities
* Add more hotkeys
* Add a JS MySQL interface