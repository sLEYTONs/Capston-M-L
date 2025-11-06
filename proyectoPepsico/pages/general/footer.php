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
/* Ajustes ESPECÍFICOS para 1366x768 */
@media (max-width: 1366px) and (min-width: 1200px) {
  .desarrollado-text {
    font-size: 0.8rem !important;
    white-space: normal;
    line-height: 1.2;
    word-spacing: -0.3px;
  }
  
  .footer-wrapper {
    padding: 0 10px !important;
    max-width: 95% !important;
  }
  
  .col-md-6.text-center.text-md-end {
    padding-left: 5px !important;
    padding-right: 5px !important;
  }
}

/* Para cuando la ventana es más estrecha (1366x768 en modo ventana) */
@media (max-width: 1300px) {
  .desarrollado-text {
    font-size: 0.78rem !important;
  }
  
  .desarrollado-text .d-inline-block {
    display: inline !important;
  }
}

/* Breakpoint específico para el texto problemático */
@media (max-width: 1250px) and (min-width: 1200px) {
  .desarrollado-text {
    font-size: 0.76rem !important;
  }
}

/* Si sigue sin caber, forzar dos líneas */
@media (max-width: 1280px) {
  .desarrollado-text br.d-md-none {
    display: block !important;
  }
}

/* Asegurar que el contenedor no se desborde */
.pc-footer .container-fluid {
  overflow: hidden;
}

.col-md-6.text-center.text-md-end {
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Reset para asegurar que no haya márgenes no deseados */
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

body {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Contenedor principal debe crecer para empujar el footer hacia abajo */
.pc-container {
  flex: 1 0 auto;
  display: flex;
  flex-direction: column;
}

.pc-content {
  flex: 1 0 auto;
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
  z-index: 100;
}

.footer-wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

.pc-footer a {
  color: white;
  text-decoration: none;
  transition: color 0.3s ease;
}

.pc-footer a:hover {
  color: #E21C21;
}

.pc-footer .text-muted {
  color: rgba(255, 255, 255, 0.8) !important;
}

.pc-footer .fw-bold {
  color: white;
}

/* Asegurar que el contenido no quede detrás del footer */
.pc-content > .row:last-child {
  margin-bottom: 2rem;
}

/* Ajustes responsivos mejorados */

/* Pantallas pequeñas (hasta 576px) */
@media (max-width: 576px) {
  .pc-footer {
    padding: 0.75rem 0;
  }
  
  .footer-wrapper {
    padding: 0 10px;
  }
  
  .pc-footer p {
    font-size: 0.8rem;
    line-height: 1.4;
  }
  
  .col-md-6 {
    margin-bottom: 0.5rem;
  }
  
  .col-md-6:last-child {
    margin-bottom: 0;
  }
}

/* Pantallas medianas (577px - 768px) */
@media (min-width: 577px) and (max-width: 768px) {
  .pc-footer {
    padding: 0.8rem 0;
  }
  
  .footer-wrapper {
    padding: 0 12px;
  }
  
  .pc-footer p {
    font-size: 0.85rem;
  }
}

/* Pantallas específicas para 1366x768 (769px - 1366px) */
@media (min-width: 769px) and (max-width: 1366px) {
  .footer-wrapper {
    padding: 0 15px;
  }
  
  .pc-footer p {
    font-size: 0.9rem;
  }
  
  .col-md-6 {
    padding: 0 8px;
  }
}

/* Pantallas grandes (más de 1366px) */
@media (min-width: 1367px) {
  .footer-wrapper {
    padding: 0 20px;
  }
}

/* Para evitar que tablas o elementos largos se salgan */
.table-responsive {
  margin-bottom: 1rem;
}

.card {
  margin-bottom: 1.5rem;
}
</style>

<script>
// Script para manejar el posicionamiento del footer
document.addEventListener('DOMContentLoaded', function() {
  function adjustFooter() {
    const body = document.body;
    const html = document.documentElement;
    const footer = document.querySelector('.pc-footer');
    
    // Calcular la altura del contenido
    const bodyHeight = body.scrollHeight;
    const windowHeight = window.innerHeight;
    
    // Si el contenido es más corto que la ventana, forzar el footer al fondo
    if (bodyHeight < windowHeight) {
      body.classList.add('short-content');
    } else {
      body.classList.remove('short-content');
    }
    
    // Verificar si hay contenido que se está ocultando detrás del footer
    const lastElement = body.lastElementChild;
    if (lastElement && lastElement !== footer) {
      const lastElementRect = lastElement.getBoundingClientRect();
      const footerRect = footer.getBoundingClientRect();
      
      if (lastElementRect.bottom > footerRect.top) {
        // Agregar margen inferior al último elemento
        lastElement.style.marginBottom = footer.offsetHeight + 20 + 'px';
      }
    }
  }
  
  // Ejecutar al cargar y al redimensionar
  adjustFooter();
  window.addEventListener('resize', adjustFooter);
  window.addEventListener('load', adjustFooter);
  
  // También después de operaciones AJAX que puedan cambiar el contenido
  if (typeof jQuery !== 'undefined') {
    $(document).ajaxComplete(function() {
      setTimeout(adjustFooter, 100);
    });
  }
});
</script>