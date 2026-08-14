<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$target = $basePath.'/storage/app/public';
$link = __DIR__.'/storage';

header('Content-Type: text/plain; charset=utf-8');

if (! is_dir($target)) {
    mkdir($target, 0775, true);
}

if (is_link($link)) {
    echo "El enlace storage ya existe:\n";
    echo $link.' -> '.readlink($link).PHP_EOL;
    exit;
}

if (file_exists($link)) {
    echo "No se puede crear el enlace porque public/storage ya existe y no es un enlace.\n";
    echo "Revisa esa carpeta manualmente antes de eliminarla para no perder archivos.\n";
    echo "Ruta: ".$link.PHP_EOL;
    exit;
}

if (@symlink($target, $link)) {
    echo "Storage link creado correctamente:\n";
    echo $link.' -> '.$target.PHP_EOL;
    exit;
}

echo "No se pudo crear el symlink. Puede que el hosting no permita symlink().\n";
echo "Ejecuta por terminal:\n";
echo "cd ".$basePath." && /usr/local/bin/php artisan storage:link\n";
