<?php

$connectionString = "host=db.sgydpinsexpnqrzvymvj.supabase.co
port=5432
dbname=postgres
user=postgres
password=FatecItaquera
sslmode=require";

$connection = @pg_connect($connectionString);

if ($connection === false) {
    echo 'Não foi possível conectar ao banco de dados do Supabase.';
} else {
    echo 'Conectado ao banco de dados com sucesso!';
}

return $connection;
?>


