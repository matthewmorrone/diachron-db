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
