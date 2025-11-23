document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("vehicle-entry-form");
    const modal = document.getElementById("confirmation-modal");
    const confirmBtn = document.getElementById("confirm-registration");
    const cancelBtn = document.getElementById("cancel-registration");
    const notification = document.getElementById("notification");

    let formDataCache = null;

    // Set default date for fecha solicitada (mínimo hoy)
    const fechaSolicitada = document.getElementById("fecha-solicitada");
    if (fechaSolicitada) {
        const hoy = new Date();
        fechaSolicitada.min = hoy.toISOString().split('T')[0];
    }

    // Limpieza del formulario
    document.getElementById("clear-form").addEventListener("click", () => {
        form.reset();
        // Restablecer fecha mínima
        if (fechaSolicitada) {
            const hoy = new Date();
            fechaSolicitada.min = hoy.toISOString().split('T')[0];
        }
    });

    // Envío del formulario con modal
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        if (validateForm()) {
            formDataCache = new FormData(form);
            modal.style.display = "block";
        }
    });

    cancelBtn.addEventListener("click", () => modal.style.display = "none");

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
                'year': 'anio',
                'driverName': 'conductor_nombre',
                'driverPhone': 'conductor_telefono',
                'purpose': 'proposito',
                'area': 'area',
                'contactPerson': 'persona_contacto',
                'observations': 'observaciones',
                'fechaSolicitada': 'fecha_solicitada',
                'horaSolicitada': 'hora_solicitada'
            };

            // Convertir FormData a objeto y mapear campos
            const formDataObj = {};
            formDataCache.forEach((value, key) => {
                const mappedKey = fieldMapping[key] || key;
                formDataObj[mappedKey] = value;
            });
            formDataObj['accion'] = 'crear_solicitud';

            // Convertir a FormData nuevamente
            const newFormData = new FormData();
            Object.keys(formDataObj).forEach(key => {
                newFormData.append(key, formDataObj[key]);
            });

            const response = await fetch("../app/model/agendamiento/scripts/s_agendamiento.php", {
                method: "POST",
                body: newFormData
            });

            const result = await response.json();

            if (result.status === "success") {
                showNotification("Solicitud de agendamiento enviada correctamente. El supervisor revisará su solicitud y le notificará.", "success");
                form.reset();
                // Establecer fecha mínima para el campo de fecha
                const fechaInput = document.getElementById("fecha-solicitada");
                if (fechaInput) {
                    fechaInput.min = new Date().toISOString().split('T')[0];
                }
            } else {
                showNotification("Error: " + result.message, "error");
            }
        } catch (error) {
            showNotification("Error de conexión con el servidor", "error");
        }
    });

    function validateForm() {
        const requiredFields = ['plate', 'vehicle-type', 'brand', 'model', 'driver-name', 'purpose', 'fecha-solicitada', 'hora-solicitada'];
        let valid = true;
        requiredFields.forEach(id => {
            const field = document.getElementById(id);
            if (field && !field.value.trim()) {
                field.classList.add("error");
                valid = false;
            } else if (field) {
                field.classList.remove("error");
            }
        });

        // Validar que la fecha no sea anterior a hoy
        const fechaInput = document.getElementById("fecha-solicitada");
        if (fechaInput && fechaInput.value) {
            const fechaSolicitada = new Date(fechaInput.value);
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            if (fechaSolicitada < hoy) {
                fechaInput.classList.add("error");
                showNotification("La fecha solicitada no puede ser anterior a hoy", "error");
                valid = false;
            }
        }

        if (!valid) showNotification("Por favor complete los campos obligatorios", "error");
        return valid;
    }

    function showNotification(msg, type) {
        notification.textContent = msg;
        notification.className = `notification show ${type}`;
        setTimeout(() => notification.classList.remove("show"), 3000);
    }

    // Cerrar modal con la X
    document.querySelector(".close").addEventListener("click", () => modal.style.display = "none");
});
