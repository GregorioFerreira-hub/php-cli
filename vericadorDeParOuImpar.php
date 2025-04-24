<?php
    echo "Digite um número: ";
    $numero = (int) fgets(STDIN);

    if ($numero % 2 == 0) 
    {
        echo "O número $numero é PAR\n";
    } 
    else 
    {
        echo "O número $numero é ÍMPAR\n";
    }
?>