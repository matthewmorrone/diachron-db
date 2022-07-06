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
+ move modals to html imports
+ figure out "or" behavior for row filtering
+ consider adding in environment for pairs, and citation and notes fields for transitions
+ show charts and graphs, for example directed graph of languages
  + https://js.cytoscape.org/
  + https://www.graphdracula.net/
  + https://www.sigmajs.org/
  + https://visjs.org/#gallery
  + https://bl.ocks.org/cjrd/6863459
+ if this is ever made more widely available, will need:
  + sql injection prevent / input sanitization
  + regular backups of data, data approval
  + view and edit modes
