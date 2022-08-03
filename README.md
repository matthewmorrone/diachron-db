# diachron-db
Database of Historical Sound Changes

#### Setup:
+ You'll need a localhost; I use [XAMPP](https://www.apachefriends.org/)
+ Find the htdocs folder (for me it's in <code>C:\xampp\htdocs</code>) and clone this project there
+ Open the XAMPP panel and start Apache and MySQL
+ Go to localhost/phpmyadmin in your browser, and import diachron.sql
+ You'll need a file in the project directory that defines the variables "\$hostname", "\$username", "\$password" and "\$database". Mine looks like this:
```
<?php
$hostname = (isset($hostname) ? $hostname : "localhost");
$username = (isset($username) ? $username : "root");
$password = (isset($password) ? $password : "");

$database = (isset($database) ? $database : "diachron");
```

+ Navigate to <code>localhost/diachron-db</code> in your browser

#### Uses:
+ Data comes from [Index Diachronica](https://chridd.nfshost.com/diachronica/all)
+ [Bootstrap 5](https://getbootstrap.com/)
+ [tagify](https://yaireo.github.io/tagify/)
+ [simple-keyboard](https://hodgef.com/simple-keyboard/)
+ [cytoscape.js](https://js.cytoscape.org/)

#### Enhancements: 
+ more robust url routing
+ use url routing to consolidate view and edit into a single file
+ open graphs in iframe on same page rather than new tab
+ merge graph viewers, enhance graphs
+ option to toggle between showing transition and source language → target language
+ add depth option (will definitely need a circularity check, see segment "an")
+ for a specific segment, show all sources for which it's a target on the left, and all targets for which it's a source on the right: aka, all arrows point rightwards
+ sql injection barriers / input sanitization
+ regular backups of data, data approval
+ languages and transitions need both citation and notes fields

### Inventory Calculation
+ edit inventories through data interface
+ when calculating an inventory, include segments in source column of rules
+ expansion of abbreviations and inventory enumeration
+ will need to have each language's inventory (available in html but currently ignored)
+ add data to database which contains every phone that belongs to a language
+ and a method of retrieving members based on features, which will require a lookup table
+ lookup table maps abbreviations to all phones they would represent
+ given a language and a category, return that set of phones within that language
+ given a language, start at its earliest inventoried ancestor apply enumerate sound changes
+ differences from actual inventory will elucidate missing data
+ create look up table that returns all phones of a given abbreviation
+ then filter for only those that belong to a specific language

#### Refactoring:
+ when modifying something and that something already exists, merge rather than reject

#### Known Bugs:
+ figure out what's going on with the tagify error (open segments modal, close, then open languages modal)

### Possible Enhancements:
+ calculate inventories from ancestral inventory and rules through data interface
+ editing nodes and vertices updates database?
+ draggable columns, if saveable to localStorage even better
+ consider adding in environment data for pairs: alternatively, encourage specific segment notation
+ dedicated ANTLR parser for phonological rules: better data import from index diachronica, easier input later on down the line
+ consider having separate phone table rather than just segment table
