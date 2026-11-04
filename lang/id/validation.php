<?php

return [
    'accepted' => ':attribute harus disetujui.',
    'array' => ':attribute harus berupa daftar.',
    'between' => [
        'numeric' => ':attribute harus antara :min dan :max.',
        'string' => ':attribute harus antara :min dan :max karakter.',
        'array' => ':attribute harus berisi :min sampai :max item.',
    ],
    'boolean' => ':attribute harus benar atau salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'after_or_equal' => ':attribute harus sama dengan atau setelah :date.',
    'before_or_equal' => ':attribute harus sama dengan atau sebelum :date.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'gt' => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih dari :value karakter.',
        'array' => ':attribute harus berisi lebih dari :value item.',
    ],
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'json' => ':attribute harus berupa JSON yang valid.',
    'max' => [
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
        'array' => ':attribute tidak boleh lebih dari :max item.',
    ],
    'min' => [
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
        'array' => ':attribute minimal :min item.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'required' => ':attribute wajib diisi.',
    'required_without' => ':attribute wajib diisi jika :values kosong.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah dipakai.',

    'custom' => [],

    'attributes' => [
        'email' => 'email',
        'password' => 'kata sandi',
        'nama' => 'nama',
    ],
];
