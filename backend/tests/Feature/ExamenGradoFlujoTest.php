<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\HistorialEstadoExamen;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Services\TramiteService;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Flujo de Examen de Grado gestionado por KARDEX.
 *
 * Cubre las reglas que no se deducen del resto del sistema: el límite de
 * intentos, los sorteos que registran asistencia con tema y fecha, la
 * asignación de notas de ambos exámenes en un módulo aparte y posterior a los
 * sorteos, la reprobación automática por no asistir y la reapertura del trámite
 * cuando el estudiante reprobó cualquiera de los dos exámenes.
 */
class ExamenGradoFlujoTest extends TestCase
{
    use RefreshDatabase;

    public function test_kardex_verifica_los_intentos_y_habilita_al_estudiante(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertCreated();

        $this->assertSame('intentos_verificados', $tramite->refresh()->estado_actual);

        $habilitacion = $this->modulo($kardex, $tramite, 'habilitacion_examen', [
            'numero_resolucion' => 'HCC-042',
            'fecha_resolucion' => '2026-09-01',
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('habilitado_examen', $tramite->estado_actual);
        $this->assertSame('HCC-042', $tramite->hitos['modulos']['habilitacion_examen']['numero_resolucion']);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'habilitacion_examen',
            'numero_resolucion' => 'HCC-042',
            'fecha_resolucion' => '2026-09-01',
        ]);

        // La fecha de la resolución viaja en la respuesta para la tarjeta y el
        // reporte, y no se confunde con la fecha en que se rinde el examen.
        $fila = collect($habilitacion->json('historial_examen'))
            ->firstWhere('etapa', 'habilitacion_examen');

        $this->assertSame('2026-09-01', substr((string) $fila['fecha_resolucion'], 0, 10));
        $this->assertNull($fila['fecha_examen']);
    }

    public function test_la_habilitacion_exige_el_numero_de_resolucion(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertCreated();

        $this->modulo($kardex, $tramite, 'habilitacion_examen', ['fecha_resolucion' => '2026-09-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('intentos_verificados', $tramite->refresh()->estado_actual);
    }

    public function test_se_rechaza_la_solicitud_cuando_el_estudiante_agoto_los_intentos(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 3);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        // Con los intentos agotados no se puede seguir el flujo...
        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_intentos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);

        // ...y el rechazo exige el motivo.
        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'rechazar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('observaciones');

        $this->modulo($kardex, $tramite, 'verificacion_intentos', [
            'accion' => 'rechazar',
            'observaciones' => 'Agotó los tres intentos permitidos para el examen de grado.',
        ])->assertCreated();

        $this->assertSame('rechazado', $tramite->refresh()->estado_actual);
    }

    public function test_no_se_rechaza_por_intentos_si_el_estudiante_todavia_tiene(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_intentos', [
            'accion' => 'rechazar',
            'observaciones' => 'Motivo cualquiera.',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_intentos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_el_sorteo_teorico_registra_asistencia_tema_y_fecha(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Normativa de gestión administrativa del Estado',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'sorteo_teorico',
            'resultado' => 'asistio',
            'tema' => 'Normativa de gestión administrativa del Estado',
            'nota' => null,
        ]);
    }

    public function test_la_nota_del_teorico_se_asigna_despues_de_los_dos_sorteos(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->sorteosAsistidos($tramite, $kardex);

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 74,
            'nota_especialidad' => 82,
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('aprobado', $tramite->estado_actual);
        $this->assertSame(74, $tramite->hitos['modulos']['asignacion_notas']['nota_teorico']);
        $this->assertSame(82, $tramite->hitos['modulos']['asignacion_notas']['nota_especialidad']);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'asignacion_notas',
            'resultado' => 'aprobado',
            'nota' => 74,
        ]);
    }

    public function test_una_nota_inferior_al_minimo_reprueba_al_cerrar_las_notas(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);

        // El teórico con 50 deja el intento reprobado, aunque la especialidad
        // esté aprobada: titularse exige los dos exámenes con 51 o más.
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 50,
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'asignacion_notas',
            'resultado' => 'reprobado',
        ]);
    }

    public function test_una_nota_fuera_del_rango_se_rechaza(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => 120,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);
    }

    public function test_no_asistir_al_sorteo_teorico_no_bloquea_y_reprueba_al_asignar_notas(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => false,
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        // La ausencia NO reproba todavía: el sorteo queda registrado y el
        // estudiante puede seguir al sorteo del examen de especialidad.
        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'sorteo_teorico',
            'resultado' => 'no_asistio',
            'nota' => null,
        ]);

        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);

        // Sin nota de teórico (no asistió), la asignación de notas deja el
        // trámite reabrible por el examen teórico.
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => '',
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);
    }

    public function test_no_asistir_al_sorteo_de_especialidad_reprueba_el_examen_de_grado(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => false,
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'sorteo_especialidad',
            'resultado' => 'no_asistio',
            'nota' => null,
        ]);

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => '',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_reprobado', $tramite->refresh()->estado_actual);
    }

    public function test_la_asignacion_de_notas_exige_los_dos_sorteos_previos(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        // Solo está registrado el sorteo teórico: falta el de especialidad.
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 74,
            'nota_especialidad' => 82,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);
    }

    public function test_la_asignacion_de_notas_exige_la_nota_de_un_examen_asistido(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 74,
            'nota_especialidad' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);
    }

    public function test_reprobar_el_teorico_sin_nota_no_impide_rendir_el_de_especialidad(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 50,
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        // Aprobar la especialidad no habilita el reporte: el teórico sigue
        // reprobado, así que el trámite queda reabrible para re-solicitar el
        // examen de grado.
        $this->modulo($kardex, $tramite, 'reporte')
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_aprobar_la_especialidad_con_el_teorico_reprobado_no_titula(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 40,
            'nota_especialidad' => 95,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'asignacion_notas',
            'resultado' => 'reprobado',
            'nota' => 40,
        ]);

        $this->modulo($kardex, $tramite, 'reporte')
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);
    }

    public function test_los_resultados_de_un_intento_anterior_no_se_cargan_al_siguiente(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 1);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        // Primer intento: aprueba el teórico y reproba la especialidad.
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => 30,
        ])->assertCreated();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertOk();

        $this->assertSame('habilitado_examen', $tramite->refresh()->estado_actual);

        // La reapertura deja en blanco los módulos del intento consumido.
        $this->assertArrayNotHasKey('asignacion_notas', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('sorteo_teorico', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('sorteo_especialidad', $tramite->hitos['modulos']);

        // Segundo intento: el reprobado es el teórico y el aprobado la
        // especialidad. El teórico aprobado en el intento anterior ya no
        // cuenta, así que el trámite NO puede quedar `aprobado` ni habilitar el
        // reporte.
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 45,
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        $this->modulo($kardex, $tramite, 'reporte')
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_la_reapertura_devuelve_el_tramite_a_habilitado_y_consume_un_intento(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 1);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => false,
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => false,
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => '',
            'nota_especialidad' => '',
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir", [
                'observaciones' => 'El estudiante pidió una nueva fecha para el sorteo.',
            ])
            ->assertOk();

        $this->assertSame('habilitado_examen', $tramite->refresh()->estado_actual);
        $this->assertSame(2, $estudiante->refresh()->numero_intentos);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'reapertura',
            'resultado' => 're_solicitud',
            'numero_intentos' => 2,
        ]);
    }

    public function test_no_se_reabre_un_tramite_que_no_reprobo(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado_actual');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_no_se_reabre_si_el_estudiante_ya_agoto_los_intentos(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 3);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        $tramite->update(['estado_actual' => 'examen_teorico_reprobado']);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_intentos');

        $this->assertSame(3, $estudiante->refresh()->numero_intentos);
    }

    public function test_el_flujo_completo_llega_a_titulado_y_cierra_el_tramite(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => 88,
        ])->assertCreated();

        $this->assertSame('aprobado', $tramite->refresh()->estado_actual);
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['aprobado'] ?? null);

        $this->modulo($kardex, $tramite, 'reporte', [])->assertCreated();

        // El reporte es el último paso: genera el PDF y titula al estudiante.
        $tramite->refresh();

        $this->assertSame('titulado', $tramite->estado_actual);
        // El hito de título es la fecha en que KARDEX cerró el trámite.
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['titulado'] ?? null);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'reporte',
            'nombre_estado' => 'titulado',
            'resultado' => 'titulado',
        ]);

        // Cerrado el flujo, ningún módulo admite cambios.
        $this->modulo($kardex, $tramite, 'reporte', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_el_flujo_de_examen_no_se_registra_en_otras_modalidades(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::factory()->tesis()->create()->id_modalidad,
            'estado_actual' => 'perfil_aprobado',
        ]);

        $this->modulo($this->crearUsuarioGestion(), $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('modulo');

        $this->assertSame('perfil_aprobado', $tramite->refresh()->estado_actual);
    }

    public function test_el_detalle_de_kardex_expone_el_historial_completo_del_examen(): void
    {
        // La tarjeta de habilitación y el acta del reporte necesitan la fecha de
        // la resolución: si el eager load la omite, ambos salen en blanco aunque
        // esté guardada en la base de datos.
        $tramite = $this->crearTramiteExamen();

        $this->habilitar($this->crearUsuarioGestion(), $tramite);

        $historial = $this->actingAs($this->crearUsuarioGestion())
            ->getJson("/api/kardex/postulantes/consultar?identificador={$tramite->estudiante->ci}")
            ->assertOk()
            ->json('tramite.historial_examen');

        $this->assertNotEmpty($historial);

        $habilitacion = collect($historial)->firstWhere('etapa', 'habilitacion_examen');

        $this->assertSame('HCC-001', $habilitacion['numero_resolucion']);
        $this->assertSame('2026-09-01', substr((string) $habilitacion['fecha_resolucion'], 0, 10));
    }

    public function test_un_modulo_desconocido_se_rechaza(): void
    {
        $tramite = $this->crearTramiteExamen();

        $this->modulo($this->crearUsuarioGestion(), $tramite, 'notas_finales', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_un_examen_aprobado_impide_iniciar_otra_postulacion(): void
    {
        // Aunque `aprobado` en Examen deja el trámite abierto a la espera del
        // reporte, el estudiante ya obtuvo su título: no puede volver a postular.
        $servicio = app(TramiteService::class);
        $estudiante = $this->crearEstudianteConCuenta();
        $usuario = User::where('estudiante_id', $estudiante->id_estudiante)->firstOrFail();
        $modalidad = Modalidad::factory()->examen()->create()->id_modalidad;
        $nuevaSolicitud = ['id_modalidad' => $modalidad, 'documentos' => []];

        $aprobado = $this->crearTramiteExamen($estudiante);
        $aprobado->update(['estado_actual' => 'aprobado']);

        $this->expectException(\DomainException::class);

        try {
            $servicio->crear($nuevaSolicitud, $usuario);
        } finally {
            // Reprobar, en cambio, sí permite presentar una nueva solicitud: la
            // anterior ya no lo bloquea.
            $aprobado->update(['estado_actual' => 'examen_teorico_reprobado']);

            $this->assertSame(
                $modalidad,
                $servicio->crear($nuevaSolicitud, $usuario)->id_modalidad,
            );
        }
    }

    public function test_un_examen_aprobado_pendiente_de_reporte_no_cierra_el_tramite(): void
    {
        // `aprobado` es terminal en el resto de modalidades, pero en Examen de
        // Grado solo significa que los dos exámenes se rindieron: falta el reporte
        // que titula al estudiante, así que el trámite sigue en la cola de trabajo.
        $kardex = $this->crearUsuarioGestion();

        $reprobado = $this->crearTramiteExamen();
        $reprobado->update(['estado_actual' => 'examen_especialidad_reprobado']);

        $aprobado = $this->crearTramiteExamen();
        $aprobado->update(['estado_actual' => 'aprobado']);

        $ids = collect($this->actingAs($kardex)
            ->getJson('/api/tramites/pendientes')
            ->assertOk()
            ->json('data'))
            ->pluck('id_tramite');

        $this->assertTrue($ids->contains($reprobado->id_tramite), 'El reprobado debe seguir pendiente de reapertura.');
        $this->assertTrue($ids->contains($aprobado->id_tramite), 'El aprobado debe seguir pendiente del reporte.');

        $concluidos = collect($this->actingAs($kardex)
            ->getJson('/api/tramites/concluidos')
            ->assertOk()
            ->json('data'))
            ->pluck('id_tramite');

        $this->assertFalse($concluidos->contains($reprobado->id_tramite));
        $this->assertFalse($concluidos->contains($aprobado->id_tramite));
    }

    public function test_el_historial_registra_cada_paso_del_flujo(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 70,
            'nota_especialidad' => 75,
        ])->assertCreated();

        $this->assertSame(
            [
                'verificacion_intentos',
                'habilitacion_examen',
                'sorteo_teorico',
                'sorteo_especialidad',
                'asignacion_notas',
            ],
            HistorialEstadoExamen::where('id_tramite', $tramite->id_tramite)
                ->orderBy('id_historial_examen')
                ->pluck('etapa')
                ->all(),
        );
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /**
     * Ejecuta un módulo del flujo de Examen de Grado como KARDEX.
     *
     * @param  array<string, mixed>  $datos  El módulo de reporte no pide campos,
     *                                       así que puede omitirse.
     */
    private function modulo(User $kardex, Tramite $tramite, string $modulo, array $datos = [])
    {
        return $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/{$modulo}", ['datos' => $datos]);
    }

    /** Deja el trámite habilitado para rendir el examen. */
    private function habilitar(User $kardex, Tramite $tramite): void
    {
        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertCreated();

        $this->modulo($kardex, $tramite, 'habilitacion_examen', [
            'numero_resolucion' => 'HCC-001',
            'fecha_resolucion' => '2026-09-01',
        ])->assertCreated();
    }

    /** Registra los dos sorteos con asistencia; las notas se asignan por separado. */
    private function sorteosAsistidos(Tramite $tramite, User $kardex): void
    {
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema teórico',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();
    }

    private function crearTramiteExamen(?Estudiante $estudiante = null): Tramite
    {
        $estudiante ??= $this->crearEstudianteConCuenta();

        return Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::factory()->examen()->create()->id_modalidad,
            'estado_actual' => 'solicitud_presentada',
        ]);
    }

    private function crearEstudiante(?int $numero_intentos = null): Estudiante
    {
        return Estudiante::factory()->create(
            $numero_intentos === null ? [] : ['numero_intentos' => $numero_intentos],
        );
    }

    private function crearEstudianteConCuenta(): Estudiante
    {
        return Estudiante::factory()->conCuenta()->create();
    }

    private function crearUsuarioGestion(): User
    {
        return User::factory()->role(Roles::GESTION[0])->create();
    }
}
