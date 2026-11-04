<?php

// Hanya pemuat. Semua berkas di routes/admin/ otomatis mendapat awalan /admin, nama admin.*,
// dan middleware web + auth + admin (lihat bootstrap/app.php).
foreach (glob(__DIR__.'/admin/*.php') ?: [] as $berkas) {
    require $berkas;
}
