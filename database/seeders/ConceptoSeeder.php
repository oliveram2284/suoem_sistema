<?php

namespace Database\Seeders;

use App\Models\Concepto;
use Illuminate\Database\Seeder;

class ConceptoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Concepto::firstOrCreate(
            ['codigo' => '00'],
            ['nombre' => 'Código de operación provisorio', 'descripcion' => null],
        );

        // Códigos reales de operación, a completar cuando el sindicato entregue el listado.
        // $codigos = [
        //     ['codigo' => '01', 'nombre' => 'Gastos Generales'],
        // ];
        // foreach ($codigos as $codigo) {
        //     Concepto::firstOrCreate(['codigo' => $codigo['codigo']], $codigo);
        // }
    }
}
