<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Estudiante;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $usuarios = [
            ['ci' => '1234567', 'nombres' => 'Administrador', 'apellidos' => 'Sistema', 'email' => 'admin@example.com', 'telefono' => '59170000001', 'rol' => 'admin'],
            ['ci' => '2345678', 'nombres' => 'Kardex', 'apellidos' => 'Encargado', 'email' => 'kardex@example.com', 'telefono' => '59170000002', 'rol' => 'kardex'],
            ['ci' => '3456789', 'nombres' => 'Secretaria', 'apellidos' => 'Académica', 'email' => 'secretaria@example.com', 'telefono' => '59170000003', 'rol' => 'secretaria'],
            ['ci' => '4567890', 'nombres' => 'Dirección', 'apellidos' => 'Carrera', 'email' => 'direccion@example.com', 'telefono' => '59170000004', 'rol' => 'direccion'],
            ['ci' => '5678901', 'nombres' => 'Concejo', 'apellidos' => 'Facultativo', 'email' => 'concejo@example.com', 'telefono' => '59170000005', 'rol' => 'concejo'],
            ['ci' => '6789012', 'nombres' => 'Docente', 'apellidos' => 'Tutor', 'email' => 'docente@example.com', 'telefono' => '59170000006', 'rol' => 'docente'],
            ['ci' => '7890123', 'nombres' => 'Estudiante', 'apellidos' => 'Prueba', 'email' => 'estudiante@example.com', 'telefono' => '59170000007', 'rol' => 'estudiante'],
            ['ci' => '8901234', 'nombres' => 'Estudiante', 'apellidos' => 'Dos', 'email' => 'estudiante2@example.com', 'telefono' => '59170000008', 'rol' => 'estudiante'],
        ];

        $password = 'password';

        foreach ($usuarios as $usuario) {
            User::updateOrCreate(
                ['email' => $usuario['email']],
                array_merge($usuario, ['password' => $password])
            );
        }

        // Perfil académico de los estudiantes de prueba
        $estudiante = User::where('email', 'estudiante@example.com')->first();
        Estudiante::updateOrCreate(
            ['id_usuario' => $estudiante->id_usuario],
            [
                'codigo_universitario' => '2020-0001',
                'plan_estudios' => '2020',
                'fecha_conclusion_plan' => '2025-12-15',
                'promedio_global' => 85.5,
                'estado' => 'activo',
            ]
        );

        $estudiante2 = User::where('email', 'estudiante2@example.com')->first();
        Estudiante::updateOrCreate(
            ['id_usuario' => $estudiante2->id_usuario],
            [
                'codigo_universitario' => '2020-0002',
                'plan_estudios' => '2020',
                'fecha_conclusion_plan' => '2025-11-30',
                'promedio_global' => 78.0,
                'estado' => 'activo',
            ]
        );

        $this->call(ModalidadSeeder::class);
    }
}