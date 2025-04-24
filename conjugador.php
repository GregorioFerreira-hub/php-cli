<?php
echo "Conjugador de Verbos -AR\n";
echo "Digite um verbo regular terminado em AR: ";
$verbo = strtolower(trim(fgets(STDIN)));

// Verifica se termina em 'ar'
if (substr($verbo, -2) == 'ar') {
    $radical = substr($verbo, 0, -2); // Remove 'ar' do final
    
    // Conjugação no presente
    $conjugacao = [
        'eu' => $radical . 'o',
        'tu' => $radical . 'as',
        'ele/ela' => $radical . 'a',
        'nós' => $radical . 'amos',
        'vós' => $radical . 'ais',
        'eles/elas' => $radical . 'am'
    ];
    
    echo "\nConjugação do verbo '$verbo' no presente:\n";
    echo "--------------------------------\n";
    foreach ($conjugacao as $pessoa => $forma) {
        echo str_pad($pessoa, 10) . " => " . $forma . "\n";
    }
} else {
    echo "\nErro: O verbo deve terminar em 'ar'.\n";
    echo "Exemplo: falar, cantar, estudar\n";
}
?>