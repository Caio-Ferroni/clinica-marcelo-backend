<?php 
    $datas = []; // array final com todas as datas daquele dia da semana no mês
    $primeiroDia = new DateTime('2027-01-01'); // primeiro dia do mês
    $ultimoDia = (clone $primeiroDia)->modify("last day of this month"); // ultimo dia do mês
    $cursor = (clone $primeiroDia); // variavel que vai passar pelo mês inteiro
    $diaAlvo = 0; // dia da semana (em int)
   
    while($cursor <= $ultimoDia){ //enquanto o cursor for menor ou igual o ultimo dia mês
        if($cursor->format('w') == $diaAlvo){ // se o dia da semana apontado pelo cursor for igual ao dia da semana alvo
            $datas[] = clone $cursor; // a data adiciona o valor do cursor (em dia do mês)
        }
        $cursor->modify('+1 day'); // cursor vai pro proximo dia do mês (até chegar no ultimo)
    }
    
    echo 'cursor: ';
    var_dump($cursor);
    echo '<br>';
    foreach ($datas as $data){
        var_dump($data);
        echo '<br>';
    }  

    // $mes = localtime(time()-9000, TRUE);
    // var_dump($mes);

?>