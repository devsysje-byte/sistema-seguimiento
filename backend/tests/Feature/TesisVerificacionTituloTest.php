<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Notificacion;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verificación de títulos en el MÓDULO 1 del flujo de titulación (Perfil de
 * Tesis): el estudiante presenta su título, KARDEX lo aprueba o lo rechaza, y
 * tras el rechazo el estudiante debe presentar un título diferente.
 */
class TesisVerificacionTituloTest extends TestCase
{
    use RefreshDatabase;

    private const TITULO = 'Sistemas de riego eficientes para cultivos de altura en el altiplano';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_el_estudiante_registra_el_titulo_con_su_solicitud(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $this->crearModalidadTesis();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/tesis/solicitud', $this->datosSolicitud(self::TITULO));

        $response->assertCreated();

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();

        $this->assertSame(
            self::TITULO,
            $tramite->hitos['modulos']['perfil_tesis']['titulo'],
        );
    }

    public function test_no_se_acepta_un_titulo_ya_registrado_por_otro_postulante(): void
    {
        $this->crearModalidadTesis();
        $otro = $this->crearTramiteConTitulo(self::TITULO);
        $estudiante = $this->crearEstudianteConCuenta();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/tesis/solicitud', $this->datosSolicitud(self::TITULO));

        $response->assertStatus(400);
        $this->assertStringContainsString('mismo título', $response->json('message'));
        $this->assertDatabaseCount('tramites', 1);
        $this->assertNotNull($otro);
    }

    public function test_kardex_rechaza_el_perfil_y_el_estudiante_reenvia_otro_titulo(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $kardex = $this->crearUsuarioGestion();

        // Rechazar el perfil exige la observación del motivo.
        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => ['accion' => 'rechazar'],
            ])
            ->assertStatus(422);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => [
                    'accion' => 'rechazar',
                    'observaciones' => 'El título repite un trabajo ya registrado. Presente otro.',
                ],
            ])
            ->assertCreated();

        $tramite->refresh();
        $this->assertSame('perfil_rechazado', $tramite->estado_actual);
        $this->assertSame(
            self::TITULO,
            $tramite->hitos['modulos']['perfil_tesis']['titulo'],
            'Al rechazar se conserva el título presentado por el estudiante.',
        );
        $this->assertStringContainsString(
            'El título repite un trabajo ya registrado',
            (string) $tramite->estados()->latest('id_estado')->first()->observaciones,
        );

        // El mismo título no sirve para corregir: debe ser otro.
        $this->actingAs($estudiante->user)
            ->postJson("/api/tesis/{$tramite->id_tramite}/reenviar", ['titulo' => self::TITULO])
            ->assertStatus(422)
            ->assertJsonValidationErrors('titulo');

        $nuevoTitulo = 'Modelo predictivo de demanda eléctrica en campuses universitarios';

        $this->actingAs($estudiante->user)
            ->postJson("/api/tesis/{$tramite->id_tramite}/reenviar", [
                'titulo' => $nuevoTitulo,
                'perfil' => UploadedFile::fake()->create('perfil.pdf', 120, 'application/pdf'),
                'observaciones' => 'Perfil corregido.',
            ])
            ->assertOk();

        $tramite->refresh();
        $this->assertSame('pendiente_concejo_universitario', $tramite->estado_actual);
        $this->assertSame($nuevoTitulo, $tramite->hitos['modulos']['perfil_tesis']['titulo']);
    }

    public function test_el_estudiante_recibe_la_observacion_del_rechazo(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);

        $this->actingAs($this->crearUsuarioGestion())
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => [
                    'accion' => 'rechazar',
                    'observaciones' => 'El título repite un trabajo ya registrado.',
                ],
            ])
            ->assertCreated();

        $notificacion = Notificacion::where('id_usuario', $estudiante->user->id_usuario)->latest('id_notificacion')->first();

        $this->assertNotNull($notificacion, 'El estudiante debe ser notificado del rechazo.');
        $this->assertStringContainsString('Perfil de tesis rechazado', (string) $notificacion->titulo);
        $this->assertStringContainsString('El título repite un trabajo ya registrado.', (string) $notificacion->mensaje);
    }

    public function test_kardex_no_puede_aprobar_un_perfil_con_titulo_duplicado(): void
    {
        $this->crearModalidadTesis();
        $this->crearTramiteConTitulo(self::TITULO);
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => $this->datosApruebaPerfil(self::TITULO),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('pendiente_concejo_universitario', $tramite->refresh()->estado_actual);
    }

    public function test_kardex_aproba_el_perfil_y_el_titulo_no_se_compara_consigo_mismo(): void
    {
        $this->crearModalidadTesis();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => $this->datosApruebaPerfil(self::TITULO),
            ])
            ->assertCreated();

        $tramite->refresh();
        $this->assertSame('perfil_aprobado', $tramite->estado_actual);
        $this->assertSame(self::TITULO, $tramite->hitos['modulos']['perfil_tesis']['titulo']);
    }

    public function test_la_solicitud_acepta_el_titulo_sin_documentos_pdf(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $this->crearModalidadTesis();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/tesis/solicitud', ['titulo' => self::TITULO]);

        $response->assertCreated();

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();
        $this->assertSame(self::TITULO, $tramite->hitos['modulos']['perfil_tesis']['titulo']);
        $this->assertCount(0, $tramite->documentos);
    }

    public function test_kardex_aproba_el_perfil_sin_pdf_adjunto(): void
    {
        $this->crearModalidadTesis();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();
        $datos = $this->datosApruebaPerfil(self::TITULO);

        // La verificación es obligatoria, pero el PDF adjunto es opcional.
        foreach ($datos['documentos'] as $tipo => $_) {
            $datos['documentos'][$tipo]['archivo'] = null;
        }

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => $datos,
            ])
            ->assertCreated();

        $this->assertSame('perfil_aprobado', $tramite->refresh()->estado_actual);
    }

    public function test_la_verificacion_de_kardex_omite_el_tramite_consultado(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->getJson('/api/kardex/titulos-tesis?'.http_build_query([
                'titulo' => self::TITULO,
                'excluir_tramite' => $tramite->id_tramite,
            ]))
            ->assertOk()
            ->assertJsonPath('verificacion.unico', true);
    }

    /**
     * Datos de la fase de solicitud: título y los 3 documentos en PDF
     * (opcionales, aquí se adjuntan los tres).
     *
     * @return array<string, mixed>
     */
    private function datosSolicitud(string $titulo): array
    {
        $documentos = [];

        foreach (array_keys(config('tesis.tipos_documento')) as $tipo) {
            $documentos[] = [
                'archivo' => UploadedFile::fake()->create("{$tipo}.pdf", 100, 'application/pdf'),
                'tipo' => $tipo,
            ];
        }

        return ['titulo' => $titulo, 'documentos' => $documentos];
    }

    /**
     * Datos con los que KARDEX aprueba el perfil (título y documentos verificados).
     *
     * @return array<string, mixed>
     */
    private function datosApruebaPerfil(string $titulo): array
    {
        $documentos = [];

        foreach (array_keys(config('tesis.tipos_documento')) as $tipo) {
            $documentos[$tipo] = [
                'marcado' => true,
                'archivo' => ['nombre' => "{$tipo}.pdf", 'tamanio' => 100],
            ];
        }

        return [
            'accion' => 'aprobar',
            'titulo' => $titulo,
            'documentos' => $documentos,
            'observaciones' => 'Documentación verificada.',
        ];
    }

    private function crearModalidadTesis(): Modalidad
    {
        return Modalidad::factory()->tesis()->create();
    }

    private function crearEstudianteConCuenta(): Estudiante
    {
        return Estudiante::factory()->conCuenta()->create();
    }

    private function crearUsuarioGestion(): User
    {
        return User::factory()->role(Roles::GESTION[0])->create();
    }

    /**
     * Trámite de tesis con el título ya registrado en su módulo de perfil.
     */
    private function crearTramiteConTitulo(string $titulo, ?Estudiante $estudiante = null): Tramite
    {
        $estudiante ??= $this->crearEstudianteConCuenta();

        return Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::where('nombre', 'Tesis de Grado')->value('id_modalidad'),
            'estado_actual' => 'pendiente_concejo_universitario',
            'hitos' => [
                'solicitud_presentada' => now()->toDateString(),
                'modulos' => [
                    'perfil_tesis' => [
                        'titulo' => $titulo,
                        'titulo_actualizado' => now()->toDateString(),
                    ],
                ],
            ],
        ]);
    }
}
