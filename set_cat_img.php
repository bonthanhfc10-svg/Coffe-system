<?php
require_once 'D:/Full Stack Developer/XAMPP/htdocs/Coffee POS/config/database.php';
$pdo = getPDO();
$images = ['Espresso'=>'espresso.png','Latte'=>'latte.png','Cappuccino'=>'cappuccino.png','Cold Drinks'=>'cold-drinks.png','Pastries'=>'pastries.png','Tea'=>'tea.png','Sandwiches'=>'sandwiches.png'];
foreach($images as $name=>$img){
    $pdo->prepare("UPDATE categories SET image = ? WHERE name = ?")->execute([$img,$name]);
}
echo "Done";
