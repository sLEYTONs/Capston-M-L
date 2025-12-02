<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Registrar Nuevo Usuario</h5>
                </div>
            </div>
            <div class="card-body">
                <form id="form-nuevo-usuario">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nombre_usuario">Nombre de Usuario *</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario"
                                    required>
                                <div class="invalid-feedback" id="error-nombre"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="correo">Correo Electrónico *</label>
                                <input type="email" class="form-control" id="correo" name="correo" required>
                                <div class="invalid-feedback" id="error-correo"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="clave">Contraseña *</label>
                                <input type="password" class="form-control" id="clave" name="clave" required>
                                <div class="invalid-feedback" id="error-clave"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rol">Rol *</label>
                                <select class="form-control" id="rol" name="rol" required>
                                    <option value="">Seleccionar rol...</option>
                                    <option value="Administrador">Administrador</option>
                                    <option value="Asistente de Repuestos">Asistente de Repuestos</option>
                                    <option value="Chofer">Chofer</option>
                                    <option value="Guardia">Guardia</option>
                                    <option value="Jefe de Taller">Jefe de Taller</option>
                                    <option value="Mecánico">Mecánico</option>
                                    <option value="Recepcionista">Recepcionista</option>
                                    <option value="Supervisor">Supervisor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado *</label>
                                <select class="form-control" id="estado" name="estado" required>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-guardar">
                        <i class="fas fa-user-plus"></i> Ingresar Nuevo Usuario
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Limpiar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Lista de Usuarios Registrados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla-usuarios" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre de Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Último Acceso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div class="modal fade" id="modal-editar-usuario" tabindex="-1" role="dialog" aria-labelledby="modal-editar-label"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-editar-label">Editar Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-editar-usuario">
                    <input type="hidden" id="edit-usuario-id" name="usuario_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit-nombre-usuario">Nombre de Usuario *</label>
                                <input type="text" class="form-control" id="edit-nombre-usuario" name="nombre_usuario"
                                    required>
                                <div class="invalid-feedback" id="edit-error-nombre"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit-correo">Correo Electrónico *</label>
                                <input type="email" class="form-control" id="edit-correo" name="correo" required>
                                <div class="invalid-feedback" id="edit-error-correo"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit-rol">Rol *</label>
                                <select class="form-control" id="edit-rol" name="rol" required>
                                    <option value="Administrador">Administrador</option>
                                    <option value="Asistente de Repuestos">Asistente de Repuestos</option>
                                    <option value="Chofer">Chofer</option>
                                    <option value="Guardia">Guardia</option>
                                    <option value="Jefe de Taller">Jefe de Taller</option>
                                    <option value="Mecánico">Mecánico</option>
                                    <option value="Recepcionista">Recepcionista</option>
                                    <option value="Supervisor">Supervisor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit-estado">Estado *</label>
                                <select class="form-control" id="edit-estado" name="estado" required>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit-clave">Nueva Contraseña (dejar vacío para mantener la actual)</label>
                                <input type="password" class="form-control" id="edit-clave" name="clave">
                                <small class="form-text text-muted">La contraseña se encriptará automáticamente</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-actualizar-usuario">
                    <i class="fas fa-save"></i> Actualizar Usuario
                </button>
            </div>
        </div>
    </div>
</div>