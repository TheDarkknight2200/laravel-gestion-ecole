<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();                                      
            $table->string('prenom');                          
            $table->string('nom');                             
            $table->string('email')->unique();                 
            $table->date('date_naissance');                    
            $table->timestamps();                             
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};