<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario que realizó la acción
            $table->string('accion'); // Acción realizada (crear, actualizar, eliminar, etc.)
            $table->string('tabla_afectada'); // Tabla afectada
            $table->unsignedBigInteger('registro_id')->nullable(); // ID del registro afectado
            $table->json('cambios')->nullable(); // Cambios realizados (antes/después)
            $table->timestamp('fecha_evento')->useCurrent(); // Fecha y hora del evento
            $table->timestamps();

            // Opcional: Si tienes tabla users y quieres la relación
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
