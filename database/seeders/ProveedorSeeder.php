<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Comercios extraídos de COMERCIOS.xlsx (hojas mensuales Dic 2024 - Sep 2025).
     * razon_social y cuit no existen en la fuente: se completan con el nombre
     * y un CUIT ficticio trazable ('20-{codigo}-0') hasta que se cargue el dato real.
     */
    public function run(): void
    {
        $comercios = [
            ['codigo' => '14', 'nombre' => 'PALACIO FENIX SRL'],
            ['codigo' => '26', 'nombre' => 'OPTICA BARBIERI'],
            ['codigo' => '36', 'nombre' => 'OPTICA BIRLE'],
            ['codigo' => '40', 'nombre' => 'SALON BS AS'],
            ['codigo' => '95', 'nombre' => 'GABRIEL SANCHEZ'],
            ['codigo' => '151', 'nombre' => 'INDUSTRIAS SISTERNA SRL'],
            ['codigo' => '153', 'nombre' => 'TODO PEUGEOT'],
            ['codigo' => '333', 'nombre' => 'SUPER GALVEZ'],
            ['codigo' => '339', 'nombre' => 'ALHARURIN CALZADO S.A'],
            ['codigo' => '704', 'nombre' => 'OP. GAILLEZ'],
            ['codigo' => '725', 'nombre' => 'OPTICA NEO VISION S.R.L'],
            ['codigo' => '726', 'nombre' => 'OPTICA NEO VISION RAWSON'],
            ['codigo' => '727', 'nombre' => 'VISION PERFECTA S.A.S'],
            ['codigo' => '781', 'nombre' => 'EL IMPERIO FRANCISCO GIL'],
            ['codigo' => '782', 'nombre' => 'NALUX S.A'],
            ['codigo' => '789', 'nombre' => 'EMP VALLECITO'],
            ['codigo' => '791', 'nombre' => 'MAYPE S.R.L.'],
            ['codigo' => '796', 'nombre' => 'PINTURERIA ARCO IRIS'],
            ['codigo' => '838', 'nombre' => 'ACUMULADORES LASER'],
            ['codigo' => '841', 'nombre' => 'IDEAS SPORT'],
            ['codigo' => '843', 'nombre' => 'ORTOPEDIA CENTRAL'],
            ['codigo' => '845', 'nombre' => 'CONTINENTE CALZADOS S.R.L'],
            ['codigo' => '855', 'nombre' => 'OPTICA CORIA'],
            ['codigo' => '859', 'nombre' => 'DOS RUEDAS MOTOS'],
            ['codigo' => '863', 'nombre' => 'DANI MAT'],
            ['codigo' => '878', 'nombre' => 'EL CUKITO MILLAN JOSE DANIEL'],
            ['codigo' => '879', 'nombre' => 'ALMACEN DON JOSE'],
            ['codigo' => '920', 'nombre' => 'OPTICA CABELLO Y MARTINEZ'],
            ['codigo' => '923', 'nombre' => 'DISMAR CASSAB. HNOS SRL'],
            ['codigo' => '955', 'nombre' => 'PELUQUERIA MONICA LUCERO'],
            ['codigo' => '956', 'nombre' => 'COLUMPIO'],
            ['codigo' => '966', 'nombre' => 'EL CHAPULIN GIL ANGEL'],
            ['codigo' => '2063', 'nombre' => 'LAMUEBLERIA DE SAN JUAN'],
            ['codigo' => '2065', 'nombre' => 'NIKO CALZADO'],
            ['codigo' => '2090', 'nombre' => 'DISTRIB ESCAÑUELA'],
            ['codigo' => '2111', 'nombre' => 'OPTICA DE LA PLAZA'],
            ['codigo' => '2132', 'nombre' => 'ASOC FOMENTO CELESTE YBCO'],
            ['codigo' => '2156', 'nombre' => 'LA ZONA'],
            ['codigo' => '2157', 'nombre' => 'NALDO LOMBARDI S.A'],
            ['codigo' => '2176', 'nombre' => 'AUTOSERV DON DEMO'],
            ['codigo' => '2179', 'nombre' => 'EL IMBATIBLE'],
            ['codigo' => '2188', 'nombre' => 'ZEGA AUTOSERVICIO'],
        ];

        foreach ($comercios as $comercio) {
            Proveedor::updateOrCreate(
                ['codigo' => $comercio['codigo']],
                [
                    'nombre' => $comercio['nombre'],
                    'razon_social' => $comercio['nombre'],
                    'cuit' => sprintf('20-%08d-0', $comercio['codigo']),
                ]
            );
        }
    }
}
