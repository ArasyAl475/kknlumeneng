<?php

// Hanya pemuat. Semua berkas di routes/api/ otomatis mendapat awalan /api.
foreach (glob(__DIR__.'/api/*.php') ?: [] as $berkas) {
    require $berkas;
}
