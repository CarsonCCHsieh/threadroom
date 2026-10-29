<?php
declare(strict_types=1);
require dirname(__DIR__) . '/server/bootstrap.php';
$dir = $argv[1] ?? '';
if (!is_dir($dir) || !str_starts_with(basename(dirname($dir)), 'threadroom-tests-')) exit(2);
$store = new \Threadroom\Store($dir);
for ($i = 0; $i < 20; $i++) {
    $store->locked(static function(array &$s) { usleep(random_int(100, 3000)); $s['unitReservedTotal']++; });
}
