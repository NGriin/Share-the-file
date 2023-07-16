<?php
require_once 'User.php';

$usersBase = new User();

$result = $usersBase->baseList();

var_dump($result);


foreach ($result as $row) {
    echo
    "<form action='index.php' method='post'> <input type='text' name='id' value = $row[0] readonly>",
    "<input type='text' name='email' value = $row[1]>",
    "</form> <br>";
}