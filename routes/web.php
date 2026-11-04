<?php

// Hanya pemuat. Tambah rute di routes/web/<modul>.php, jangan di sini.
foreach (glob(__DIR__.'/web/*.php') ?: [] as $berkas) {
    require $berkas;
}
