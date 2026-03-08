<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cours_etudiant', function (Blueprint $table) {
           
            $table->foreignId('etudiant_id')
                  ->constrained('etudiants')
                  ->onDelete('cascade');

            
            $table->foreignId('cours_id')
                  ->constrained('cours')
                  ->onDelete('cascade');

          
            $table->primary(['etudiant_id', 'cours_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cours_etudiant');
    }
};
