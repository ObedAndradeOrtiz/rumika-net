<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // La asistencia usa rostro al marcar entrada/salida, pero no debe obligar
        // verificacion facial al iniciar sesion en equipos sin camara.
    }
};
