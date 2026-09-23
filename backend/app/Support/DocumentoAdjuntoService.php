<?php

namespace App\Support;

use App\Models\DocumentoAdjunto;
use Illuminate\Http\UploadedFile;

/**
 * Servicio compartido de documentos adjuntos (capa transversal).
 *
 * Centraliza la persistencia de archivos subidos a trámites y tesis: guarda el
 * archivo en el disco público y crea el registro en `documentos_adjuntos`.
 *
 * Es consumido por los módulos Tramites y Tesis para evitar repetir el bloque
 * de "subir archivo + crear DocumentoAdjunto" en cada caso de uso (principio DRY).
 */
final class DocumentoAdjuntoService
{
    /** Carpeta de almacenamiento de documentos dentro del disco público. */
    private const RUTA_STORAGE = 'documentos_tramites';

    /**
     * Guarda un archivo adjunto de un trámite y registra sus metadatos.
     *
     * @param int             $idTramite     Trámite al que pertenece el documento.
     * @param int             $idUsuario     Usuario que sube el archivo.
     * @param UploadedFile    $archivo       Archivo (PDF) recibido en el request.
     * @param string          $tipoDocumento Tipo de documento (p. ej. `perfil_tesis`).
     *
     * @return DocumentoAdjunto Registro persistido.
     */
    public function guardar(
        int $idTramite,
        int $idUsuario,
        UploadedFile $archivo,
        string $tipoDocumento,
    ): DocumentoAdjunto {
        $ruta = $archivo->store(self::RUTA_STORAGE, 'public');

        return DocumentoAdjunto::create([
            'id_tramite' => $idTramite,
            'id_usuario_subio' => $idUsuario,
            'tipo_documento' => $tipoDocumento,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'tamanio_kb' => round($archivo->getSize() / 1024, 2),
        ]);
    }
}