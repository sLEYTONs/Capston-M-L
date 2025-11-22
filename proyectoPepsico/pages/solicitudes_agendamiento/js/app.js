// Función para obtener la URL base del script
function getBaseUrl() {
    const currentPath = window.location.pathname;
    // Si estamos en pages/, construir la ruta relativa
    if (currentPath.includes('/pages/')) {
        // Obtener la parte antes de /pages/
        const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
        const url = basePath + '/app/model/agendamiento/scripts/s_agendamiento.php';
        return url;
    }
    // Fallback: ruta relativa desde pages/
    return '../../app/model/agendamiento/scripts/s_agendamiento.php';
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("solicitud-agendamiento-form");
    const modal = document.getElementById("confirmation-modal");
    const confirmBtn = document.getElementById("confirm-registration");
    const cancelBtn = document.getElementById("cancel-registration");
    const notification = document.getElementById("notification");
    const plateInput = document.getElementById("plate");
    const vehicleInfo = document.getElementById("vehicle-info");

    let formDataCache = null;
    const baseUrl = getBaseUrl();
    const fotosInput = document.getElementById("fotos");
    const fotosPreview = document.getElementById("fotos-preview");
    const fotosPreviewContainer = document.getElementById("fotos-preview-container");

    // Set default date for fecha solicitada (mínimo hoy)
    const fechaSolicitada = document.getElementById("fecha-solicitada");
    if (fechaSolicitada) {
        const hoy = new Date();
        fechaSolicitada.min = hoy.toISOString().split('T')[0];
    }

    // Manejar preview de imágenes
    if (fotosInput) {
        fotosInput.addEventListener("change", (e) => {
            const files = Array.from(e.target.files);
            fotosPreviewContainer.innerHTML = '';
            
            if (files.length > 0) {
                fotosPreview.style.display = 'block';
                
                files.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            const col = document.createElement('div');
                            col.className = 'col-md-3 mb-2';
                            col.innerHTML = `
                                <div class="card">
                                    <img src="${event.target.result}" class="card-img-top" style="max-height: 150px; object-fit: cover;">
                                    <div class="card-body p-2">
                                        <small class="text-muted">${file.name}</small>
                                    </div>
                                </div>
                            `;
                            fotosPreviewContainer.appendChild(col);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            } else {
                fotosPreview.style.display = 'none';
            }
        });
    }

    // Letras permitidas en placas chilenas (solo consonantes)
    const letrasPermitidas = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z'];
    
    // Función para validar formato de placa chilena
    function validarFormatoPlaca(placa) {
        // Convertir a mayúsculas y eliminar espacios
        placa = placa.toUpperCase().replace(/\s/g, '');
        
        // Validar longitud
        if (placa.length !== 6) {
            return { valido: false, mensaje: 'La placa debe tener 6 caracteres' };
        }
        
        // Validar formato: 4 letras + 2 números o 2 letras + 4 números
        const formato1 = /^[BCDFGHJKLMNPRSTVWXYZ]{4}[0-9]{2}$/; // 4 letras + 2 números
        const formato2 = /^[BCDFGHJKLMNPRSTVWXYZ]{2}[0-9]{4}$/; // 2 letras + 4 números
        
        if (!formato1.test(placa) && !formato2.test(placa)) {
            return { valido: false, mensaje: 'Formato inválido. Use: 4 letras + 2 números (BCDF12) o 2 letras + 4 números (BC1234)' };
        }
        
        // Validar que todas las letras sean permitidas
        for (let i = 0; i < placa.length; i++) {
            const char = placa[i];
            if (isNaN(char)) { // Es una letra
                if (!letrasPermitidas.includes(char)) {
                    return { valido: false, mensaje: `La letra "${char}" no está permitida. Solo consonantes: ${letrasPermitidas.join(', ')}` };
                }
            }
        }
        
        return { valido: true, placa: placa };
    }
    
    // Función para filtrar caracteres no permitidos mientras el usuario escribe
    function filtrarCaracteresPlaca(input) {
        let valor = input.value.toUpperCase();
        let valorFiltrado = '';
        
        for (let i = 0; i < valor.length; i++) {
            const char = valor[i];
            // Permitir letras permitidas y números
            if (letrasPermitidas.includes(char) || /[0-9]/.test(char)) {
                valorFiltrado += char;
            }
        }
        
        // Limitar a 6 caracteres
        valorFiltrado = valorFiltrado.substring(0, 6);
        
        input.value = valorFiltrado;
        return valorFiltrado;
    }
    
    // Buscar vehículo por patente cuando se ingrese la placa
    if (plateInput) {
        const plateError = document.getElementById("plate-error");
        let timeoutId;
        
        // Convertir a mayúsculas y filtrar caracteres mientras escribe
        plateInput.addEventListener("input", (e) => {
            const valorOriginal = e.target.value;
            const valorFiltrado = filtrarCaracteresPlaca(e.target);
            
            // Limpiar mensaje de error si hay cambios
            if (plateError) {
                plateError.style.display = 'none';
                plateError.textContent = '';
            }
            plateInput.classList.remove("error");
            
            // Validar formato cuando tenga 6 caracteres
            if (valorFiltrado.length === 6) {
                const validacion = validarFormatoPlaca(valorFiltrado);
                if (!validacion.valido) {
                    if (plateError) {
                        plateError.textContent = validacion.mensaje;
                        plateError.style.display = 'block';
                    }
                    plateInput.classList.add("error");
                    ocultarInfoVehiculo();
                    return;
                } else {
                    // Formato válido, buscar vehículo
                    if (plateError) {
                        plateError.style.display = 'none';
                    }
                    plateInput.classList.remove("error");
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        buscarVehiculoPorPatente(validacion.placa);
                    }, 500);
                }
            } else if (valorFiltrado.length > 0) {
                // Si tiene caracteres pero no 6, validar solo letras permitidas
                for (let i = 0; i < valorFiltrado.length; i++) {
                    const char = valorFiltrado[i];
                    if (isNaN(char) && !letrasPermitidas.includes(char)) {
                        if (plateError) {
                            plateError.textContent = `La letra "${char}" no está permitida. Solo consonantes: ${letrasPermitidas.join(', ')}`;
                            plateError.style.display = 'block';
                        }
                        plateInput.classList.add("error");
                        break;
                    }
                }
            } else {
                // Si está vacío, ocultar información del vehículo
                ocultarInfoVehiculo();
            }
        });
        
        // Validar al perder el foco
        plateInput.addEventListener("blur", (e) => {
            const patente = e.target.value.trim();
            if (patente.length > 0 && patente.length !== 6) {
                const validacion = validarFormatoPlaca(patente);
                if (!validacion.valido) {
                    if (plateError) {
                        plateError.textContent = validacion.mensaje;
                        plateError.style.display = 'block';
                    }
                    plateInput.classList.add("error");
                }
            }
        });
    }

    // Función para buscar vehículo por patente
    async function buscarVehiculoPorPatente(patente) {
        try {
            // Asegurar que la patente esté en mayúsculas y validada
            const validacion = validarFormatoPlaca(patente);
            if (!validacion.valido) {
                ocultarInfoVehiculo();
                return;
            }
            
            const patenteValidada = validacion.placa;
            
            const formData = new FormData();
            formData.append('accion', 'obtener_vehiculo_por_patente');
            formData.append('patente', patenteValidada);

            const response = await fetch(baseUrl, {
                method: "POST",
                body: formData
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }

            const result = await response.json();

            if (result.status === 'success' && result.data) {
                llenarDatosVehiculo(result.data);
            } else {
                ocultarInfoVehiculo();
                if (result.message) {
                    // Mostrar error en el campo de placa
                    const plateError = document.getElementById("plate-error");
                    if (plateError) {
                        plateError.textContent = result.message;
                        plateError.style.display = 'block';
                    }
                    plateInput.classList.add("error");
                    showNotification(result.message, "error");
                }
            }
        } catch (error) {
            console.error("Error al buscar vehículo:", error);
            ocultarInfoVehiculo();
        }
    }

    // Función para llenar los datos del vehículo en el formulario
    function llenarDatosVehiculo(vehiculo) {
        const vehicleType = document.getElementById("vehicle-type");
        const brand = document.getElementById("brand");
        const model = document.getElementById("model");
        const color = document.getElementById("color");
        const year = document.getElementById("year");

        if (vehicleType) vehicleType.value = vehiculo.TipoVehiculo || '';
        if (brand) brand.value = vehiculo.Marca || '';
        if (model) model.value = vehiculo.Modelo || '';
        if (color) color.value = vehiculo.Color || '';
        if (year) year.value = vehiculo.Anio || '';

        // Mostrar la sección de información del vehículo
        if (vehicleInfo) {
            vehicleInfo.style.display = 'block';
        }

        showNotification("Datos del vehículo cargados correctamente", "success");
    }

    // Función para ocultar información del vehículo
    function ocultarInfoVehiculo() {
        if (vehicleInfo) {
            vehicleInfo.style.display = 'none';
        }
    }

    // Limpieza del formulario
    const clearBtn = document.getElementById("clear-form");
    if (clearBtn) {
        clearBtn.addEventListener("click", () => {
            form.reset();
            ocultarInfoVehiculo();
            if (fotosPreview) {
                fotosPreview.style.display = 'none';
                fotosPreviewContainer.innerHTML = '';
            }
            if (fechaSolicitada) {
                const hoy = new Date();
                fechaSolicitada.min = hoy.toISOString().split('T')[0];
            }
        });
    }

    // Envío del formulario con modal
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            if (validateForm()) {
                formDataCache = new FormData(form);
                modal.style.display = "block";
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", () => modal.style.display = "none");
    }

    if (confirmBtn) {
        confirmBtn.addEventListener("click", async () => {
            if (!formDataCache) return;
            modal.style.display = "none";

            try {
                // Mapear nombres de campos del formulario a los nombres esperados por el backend
                const fieldMapping = {
                    'plate': 'placa',
                    'vehicleType': 'tipo_vehiculo',
                    'brand': 'marca',
                    'model': 'modelo',
                    'color': 'color',
                    'year': 'anio',
                    'purpose': 'proposito',
                    'observations': 'observaciones',
                    // Fecha y hora se asignarán cuando el supervisor apruebe
                    'fechaSolicitada': 'fecha_solicitada',
                    'horaSolicitada': 'hora_solicitada'
                };

                // Crear nuevo FormData con los datos mapeados y las imágenes
                const newFormData = new FormData();
                
                // Mapear campos de texto
                formDataCache.forEach((value, key) => {
                    const mappedKey = fieldMapping[key] || key;
                    if (mappedKey && value && key !== 'fotos[]') {
                        // Asegurar que la placa esté en mayúsculas y validada
                        if (key === 'plate') {
                            const validacion = validarFormatoPlaca(value);
                            if (validacion.valido) {
                                newFormData.append(mappedKey, validacion.placa);
                            } else {
                                showNotification("Error: La placa no tiene un formato válido", "error");
                                return;
                            }
                        } else {
                            newFormData.append(mappedKey, value);
                        }
                    }
                });
                
                // Agregar imágenes si existen
                const fotosFiles = formDataCache.getAll('fotos[]');
                fotosFiles.forEach(file => {
                    if (file instanceof File) {
                        newFormData.append('fotos[]', file);
                    }
                });
                
                // Si no hay fecha/hora, usar valores por defecto (serán actualizados por el supervisor)
                if (!formDataCache.get('fechaSolicitada')) {
                    newFormData.append('fecha_solicitada', new Date().toISOString().split('T')[0]);
                }
                if (!formDataCache.get('horaSolicitada')) {
                    newFormData.append('hora_solicitada', '08:00');
                }
                
                newFormData.append('accion', 'crear_solicitud');

                const response = await fetch(baseUrl, {
                    method: "POST",
                    body: newFormData
                });

                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Respuesta no JSON recibida:', text);
                    showNotification("Error: El servidor devolvió una respuesta no válida", "error");
                    return;
                }

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error HTTP:', response.status, errorText);
                    showNotification("Error del servidor: " + response.status, "error");
                    return;
                }

                const result = await response.json();

                if (result.status === "success") {
                    showNotification("Solicitud de agendamiento enviada correctamente. El supervisor revisará su solicitud y le notificará la respuesta.", "success");
                    form.reset();
                    if (fotosPreview) {
                        fotosPreview.style.display = 'none';
                        fotosPreviewContainer.innerHTML = '';
                    }
                    if (fechaSolicitada) {
                        const hoy = new Date();
                        fechaSolicitada.min = hoy.toISOString().split('T')[0];
                    }
                } else {
                    // Verificar si es el error de solicitud pendiente
                    if (result.message && result.message.includes("Ya existe una solicitud pendiente")) {
                        mostrarModalSolicitudPendiente();
                    } else {
                        showNotification("Error: " + result.message, "error");
                    }
                }
            } catch (error) {
                console.error("Error:", error);
                showNotification("Error de conexión con el servidor", "error");
            }
        });
    }

    function validateForm() {
        const requiredFields = ['plate', 'vehicle-type', 'brand', 'model', 'driver-name', 'purpose'];
        let valid = true;
        const plateError = document.getElementById("plate-error");
        
        // Validar placa primero
        if (plateInput) {
            const patente = plateInput.value.trim().toUpperCase();
            const validacion = validarFormatoPlaca(patente);
            
            if (!patente) {
                plateInput.classList.add("error");
                if (plateError) {
                    plateError.textContent = 'La placa es obligatoria';
                    plateError.style.display = 'block';
                }
                valid = false;
            } else if (!validacion.valido) {
                plateInput.classList.add("error");
                if (plateError) {
                    plateError.textContent = validacion.mensaje;
                    plateError.style.display = 'block';
                }
                valid = false;
            } else {
                plateInput.classList.remove("error");
                if (plateError) {
                    plateError.style.display = 'none';
                }
            }
        }
        
        // Validar otros campos requeridos
        requiredFields.forEach(id => {
            const field = document.getElementById(id);
            if (field && !field.value.trim()) {
                field.classList.add("error");
                valid = false;
            } else if (field) {
                field.classList.remove("error");
            }
        });

        // Verificar que se haya encontrado el vehículo
        if (valid && vehicleInfo && vehicleInfo.style.display === 'none') {
            showNotification("Por favor ingrese una patente válida y espere a que se carguen los datos del vehículo", "error");
            if (plateInput) {
                plateInput.classList.add("error");
            }
            valid = false;
        }

        if (!valid) {
            const mensaje = plateInput && plateInput.classList.contains("error") 
                ? "Por favor corrija los errores en el formulario" 
                : "Por favor complete los campos obligatorios";
            showNotification(mensaje, "error");
        }
        return valid;
    }

    function showNotification(msg, type) {
        if (!notification) return;
        notification.textContent = msg;
        notification.className = `notification show ${type}`;
        setTimeout(() => notification.classList.remove("show"), 5000);
    }

    // Cerrar modal con la X
    const closeBtn = document.querySelector(".close");
    if (closeBtn) {
        closeBtn.addEventListener("click", () => modal.style.display = "none");
    }

    // Modal de solicitud pendiente
    const solicitudPendienteModal = document.getElementById("solicitud-pendiente-modal");
    const closePendienteModal = document.getElementById("close-pendiente-modal");
    const aceptarPendienteModal = document.getElementById("aceptar-pendiente-modal");

    function mostrarModalSolicitudPendiente() {
        if (solicitudPendienteModal) {
            solicitudPendienteModal.style.display = "block";
        }
    }

    function cerrarModalSolicitudPendiente() {
        if (solicitudPendienteModal) {
            solicitudPendienteModal.style.display = "none";
        }
    }

    if (closePendienteModal) {
        closePendienteModal.addEventListener("click", cerrarModalSolicitudPendiente);
    }

    if (aceptarPendienteModal) {
        aceptarPendienteModal.addEventListener("click", cerrarModalSolicitudPendiente);
    }

    // Cerrar modal al hacer clic fuera de él
    if (solicitudPendienteModal) {
        window.addEventListener("click", (event) => {
            if (event.target === solicitudPendienteModal) {
                cerrarModalSolicitudPendiente();
            }
        });
    }
});

