#  Browzar v0.3
PHP file browser and syntax-highlighting text editor
Uses Ace.js on top of PHP/AJAX using Angular

I found myself using this file more than my IDE (especially on 1 monitor environments).  It's usually faster to have this in one tab and my webpage in the other.  Every time I need a feature I add it in.

Features:
* Ability to select all themes from Ace.JS at will
* Saves files
* Creates new files
* Deletes FIles
* Browses directory structure
* Takes a URL GET param for password to control access
* Recognizes some hotkeys (ctrl+s to save, ctrl+/ to comment block etc...)

Changelog
* Re-Enginered with Angular as opposed to JQuery for code simplicity
* Added ability to delete files

Future Todo's:
* Use a more Angular appropriate way to bind Ctrl + S hotkey
* Rmdir functionality
* Auotmatically support all available syntax highlighting types by default
* Minor optimizations
* Add directory tree view instead of entering/exiting directories
* Tabs with namespaced editors to work on multiple files at once
* Sync up with Github API
* Add more hotkey support
* Better security
* Way more other, better features
* Two versions, inline and modular

Notes:
This file will have trouble creating/deleting files in a directory that isn't set to chmod 0755 or 0777
The two css files are necessary to keep the page orderly, but will probably be included in an inline version in the future
