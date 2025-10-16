document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("vehicle-entry-form");
    const modal = document.getElementById("confirmation-modal");
    const confirmBtn = document.getElementById("confirm-registration");
    const cancelBtn = document.getElementById("cancel-registration");
    const notification = document.getElementById("notification");

    let formDataCache = null;

    // Set default date/time
    const entryTime = document.getElementById("entry-time");
    if (entryTime) {
        const now = new Date();
        entryTime.value = now.toISOString().slice(0, 16);
    }

    // Limpieza del formulario
    document.getElementById("clear-form").addEventListener("click", () => {
        form.reset();
        const now = new Date();
        entryTime.value = now.toISOString().slice(0, 16);
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
            const response = await fetch("../app/model/index/scripts/s_ingresoVehiculo.php", {
                method: "POST",
                body: formDataCache
            });

            const result = await response.json();

            if (result.status === "success") {
                showNotification("Vehículo registrado correctamente", "success");
                form.reset();
                const now = new Date();
                entryTime.value = now.toISOString().slice(0, 16);
            } else {
                showNotification("Error: " + result.message, "error");
            }
        } catch (error) {
            showNotification("Error de conexión con el servidor", "error");
        }
    });

    function validateForm() {
        const requiredFields = ['plate', 'vehicle-type', 'brand', 'model', 'driver-name', 'driver-id', 'company', 'entry-time', 'purpose'];
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
