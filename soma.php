<?php
    echo "Digite o primeiro número: ";
    $num1 = (float) fgets(STDIN);

    echo "Digite o segundo número: ";
    $num2 = (float) fgets(STDIN);

    $soma = $num1 + $num2;
    echo "A soma de $num1 + $num2 é: $soma\n";
?>
