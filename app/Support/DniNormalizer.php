<?php

namespace App\Support;

class DniNormalizer
{
    public static function normalizar(?string $dni): ?string
    {
        if (!$dni) {
            return null;
        }

        // quitar todo lo que no sea número
        $soloNumeros = preg_replace('/\D+/', '', $dni);

        return $soloNumeros ?: null;
    }
}