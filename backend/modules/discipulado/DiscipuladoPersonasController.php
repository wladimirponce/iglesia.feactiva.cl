<?php

declare(strict_types=1);

final class DiscipuladoPersonasController
{
    private DiscipuladoValidator $validator;
    private DiscipuladoService $service;

    public function __construct()
    {
        $this->validator = new DiscipuladoValidator();
        $this->service = new DiscipuladoService(new DiscipuladoRepository());
    }

    public function assignRuta(string $personaId): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateAssignRuta($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $id = $this->service->assignRuta((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $personaId, (int) $input['ruta_id'], $input);
            Response::success(['id' => $id], 'Persona asignada a ruta correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            $status = in_array($exception->getMessage(), ['CRM_PERSON_NOT_FOUND', 'DISC_RUTA_NOT_FOUND', 'DISC_MENTOR_NOT_FOUND'], true) ? 404 : 409;
            Response::error($exception->getMessage(), 'No fue posible asignar la ruta.', [], $status);
        } catch (Throwable) {
            Response::error('DISC_ASSIGN_ROUTE_ERROR', 'No fue posible asignar la ruta.', [], 500);
        }
    }

    public function avance(string $personaId): void
    {
        try {
            Response::success($this->service->avance((int) AuthContext::tenantId(), (int) $personaId));
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible obtener el avance.', [], 404);
        }
    }

    public function completeEtapa(string $id): void
    {
        try {
            $this->service->completeEtapa((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $id, $this->jsonInput());
            Response::success(['id' => (int) $id], 'Etapa completada correctamente.');
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible completar la etapa.', [], 404);
        } catch (Throwable) {
            Response::error('DISC_COMPLETE_STAGE_ERROR', 'No fue posible completar la etapa.', [], 500);
        }
    }

    public function mentorias(string $personaId): void
    {
        try {
            Response::success($this->service->mentorias((int) AuthContext::tenantId(), (int) $personaId));
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible obtener mentorias.', [], 404);
        }
    }

    public function storeMentoria(string $personaId): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateMentoriaCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $id = $this->service->createMentoria((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $personaId, $input);
            Response::success(['id' => $id], 'Mentoria registrada correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible registrar la mentoria.', [], 404);
        } catch (Throwable) {
            Response::error('DISC_MENTORIA_CREATE_ERROR', 'No fue posible registrar la mentoria.', [], 500);
        }
    }

    public function registros(string $personaId): void
    {
        try {
            Response::success($this->service->registros((int) AuthContext::tenantId(), (int) $personaId));
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible obtener registros espirituales.', [], 404);
        }
    }

    public function storeRegistro(string $personaId): void
    {
        $input = $this->jsonInput();
        $errors = $this->validator->validateRegistroCreate($input);
        if ($errors !== []) {
            Response::error('VALIDATION_ERROR', 'Datos invalidos.', $errors, 422);
            return;
        }
        try {
            $id = $this->service->createRegistro((int) AuthContext::tenantId(), (int) AuthContext::userId(), (int) $personaId, $input);
            Response::success(['id' => $id], 'Registro espiritual creado correctamente.', [], 201);
        } catch (RuntimeException $exception) {
            Response::error($exception->getMessage(), 'No fue posible crear el registro espiritual.', [], 404);
        } catch (Throwable) {
            Response::error('DISC_REGISTRO_CREATE_ERROR', 'No fue posible crear el registro espiritual.', [], 500);
        }
    }

    private function jsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $decoded = $rawBody === false ? null : json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : [];
    }
}
