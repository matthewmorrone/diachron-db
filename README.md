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
+ [Bootstrap 5](https://getbootstrap.com/)
+ [tagify](https://yaireo.github.io/tagify/)
+ [simple-keyboard](https://hodgef.com/simple-keyboard/)

#### To-Do:
+ implement custom combobox
+ figure out "or" behavior for row filtering
+ consider adding in environment data for pairs: alternatively, encourage specific segment notation
+ consider notes field for pairs and transitions
+ expansion of abbreviations: 
  + will need to have each language's inventory (available in html but currently ignored)
  + and a method of retrieving members based on features, which will require a lookup table
+ need inventory enumeration:
  + add data to database which contains every phone that belongs to a language
  + add table that maps abbreviations to all phones they would represent
  + given a language and a category, return that set of phones within that language
  + given a language, start at its earliest ancestor with a phonological inventory and apply sound changes to get hypothetical inventory
+ if this is ever made more widely available, will need:
  + sql injection prevention / input sanitization
  + regular backups of data, data approval
  + view and edit modes
