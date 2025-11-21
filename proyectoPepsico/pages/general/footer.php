<footer class="pc-footer">
  <div class="footer-wrapper container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start my-2">
        <p class="m-0 text-muted">
          &copy; <?php echo date("Y"); ?> PepsiCo. Todos los derechos reservados.
        </p>
      </div>
      <div class="col-md-6 text-center text-md-end my-2">
        <p class="m-0 text-muted desarrollado-text">
          Desarrollado por 
          <span class="d-inline-block">
            <span class="fw-bold">Matías Mora</span> y 
            <span class="fw-bold">Sebastián Leyton</span>
          </span>
        </p>
      </div>
    </div>
  </div>
</footer>

<style>
/* Reset para estructura principal */
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
  width: 100%;
  overflow-x: hidden;
}

body {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  width: 100%;
}

/* Contenedor principal debe crecer */
.pc-container {
  flex: 1 0 auto;
  display: flex;
  flex-direction: column;
  width: 100%;
  align-items: center;
}

.pc-content {
  flex: 1 0 auto;
  padding-bottom: 20px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Estilos del footer */
.pc-footer {
  background-color: #004B93;
  color: white;
  padding: 1rem 0;
  border-top: 3px solid #E21C21;
  margin-top: auto;
  flex-shrink: 0;
  width: 100%;
  position: relative;
  left: 0;
  right: 0;
}

.footer-wrapper {
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}

.pc-footer .text-muted {
  color: rgba(255, 255, 255, 0.8) !important;
}

.pc-footer .fw-bold {
  color: white;
}

/* Ajustes ESPECÍFICOS para 1366x768 */
@media (max-width: 1366px) and (min-width: 1200px) {
  .desarrollado-text {
    font-size: 0.8rem !important;
    white-space: normal;
    line-height: 1.2;
  }
  
  .footer-wrapper {
    padding: 0 10px !important;
  }
}

/* Para cuando la ventana es más estrecha */
@media (max-width: 1300px) {
  .desarrollado-text {
    font-size: 0.78rem !important;
  }
}

/* Asegurar que no haya espacios no deseados */
.pc-footer-fix {
  display: none;
}

/* Eliminar cualquier padding/margin extra del body */
body {
  overflow-x: hidden;
}

/* Centrado general para todo el contenido */
.pc-main-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  function adjustFooter() {
    const body = document.body;
    const html = document.documentElement;
    const footer = document.querySelector('.pc-footer');
    
    // Calcular si el contenido es más corto que la ventana
    const bodyHeight = body.scrollHeight;
    const windowHeight = window.innerHeight;
    
    if (bodyHeight < windowHeight) {
      // Forzar el footer al fondo si el contenido es corto
      footer.style.position = 'fixed';
      footer.style.bottom = '0';
      footer.style.left = '0';
      footer.style.right = '0';
    } else {
      // Contenido largo, footer normal
      footer.style.position = 'relative';
    }
  }
  
  // Ejecutar al cargar y al redimensionar
  adjustFooter();
  window.addEventListener('resize', adjustFooter);
  window.addEventListener('load', adjustFooter);
});
</script>