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
+ [Index Diachronica](https://chridd.nfshost.com/diachronica/all)
+ [Bootstrap 5](https://getbootstrap.com/)
+ [tagify](https://yaireo.github.io/tagify/)
+ [simple-keyboard](https://hodgef.com/simple-keyboard/)
+ [cytoscape.js](https://js.cytoscape.org/)

#### To-Do:
+ consider having separate phone table rather than just segment table
+ create many-to-many table languages_segments or languages_phones
+ store segments parsed from index diachronica in database
  + include segments in source column of rules
+ create look up table that returns all phones of a given abbreviation
  + then filter for only those that belong to a specific language
+ languages and transitions need both citation and notes fields

#### Refactoring:
+ switch all php functions to accepting $data
+ when modifying something and that something already exists, merge rather than reject

#### Known Bugs:
+ opening and closing the modal causes loadPairs to be called too many times
+ notes about languages currently tossed out: need to aggregate tags
+ figure out what's going on with the tagify error

#### Enhancements: 
+ consider adding in environment data for pairs: alternatively, encourage specific segment notation
+ expansion of abbreviations and inventory enumeration:
  + will need to have each language's inventory (available in html but currently ignored)
  + add data to database which contains every phone that belongs to a language
  + and a method of retrieving members based on features, which will require a lookup table
  + lookup table maps abbreviations to all phones they would represent
  + given a language and a category, return that set of phones within that language
  + given a language, start at its earliest inventoried ancestor apply enumerate sound changes:
  + differences from actual inventory will elucidate missing data
+ if this is ever made more widely available, will need:
  + sql injection prevention / input sanitization
  + regular backups of data, data approval
  + view and edit modes
