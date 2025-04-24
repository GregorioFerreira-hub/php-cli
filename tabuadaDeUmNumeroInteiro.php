<?php
    echo "Digite um número para ver sua tabuada: ";
    $numero = (int) fgets(STDIN);

    echo "\nTabuada do $numero:\n";
    echo "-----------------\n";

    for ($i = 1; $i <= 10; $i++) {
        $resultado = $numero * $i;
        echo "$numero × $i = $resultado\n";
    }

?>