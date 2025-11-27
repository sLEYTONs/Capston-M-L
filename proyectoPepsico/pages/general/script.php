<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Dropzone JS -->
<script src="https://unpkg.com/dropzone@5/dist/dropzone.js"></script>

<!-- Bootstrap 5 + Popper -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>

<!-- SimpleBar -->
<script src="../assets/js/plugins/simplebar.min.js"></script>

<!-- PCoded -->
<script src="../assets/js/pcoded.js"></script>

<!-- Feather Icons -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- DataTables para Bootstrap 5 -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- DataTables Buttons (si los necesitas) -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<!-- Fuentes personalizadas -->
<script src="../assets/js/fonts/custom-font.js"></script>

<!-- Inicialización de layout -->
<script>
  // Esperar a que todo esté cargado
  document.addEventListener('DOMContentLoaded', function() {
    layout_change('light');
    layout_theme_contrast_change('false');
    change_box_container('false');
    layout_caption_change('true');
    layout_rtl_change('false');
    preset_change("preset-1");
    
    // Calcular y establecer la altura del header principal para posicionar el custom-page-header
    function updatePageHeaderPosition() {
      const pcHeader = document.querySelector('.pc-header');
      const pageHeader = document.querySelector('.custom-page-header');
      
      if (pcHeader && pageHeader) {
        const headerHeight = pcHeader.offsetHeight;
        document.documentElement.style.setProperty('--header-height', headerHeight + 'px');
        
        // Calcular altura del custom-page-header
        const pageHeaderHeight = pageHeader.offsetHeight;
        document.documentElement.style.setProperty('--page-header-height', pageHeaderHeight + 'px');
        
        // Establecer posición del custom-page-header pegado al header (sin espacio)
        pageHeader.style.top = headerHeight + 'px';
      }
    }
    
    // Actualizar posición al cargar
    setTimeout(updatePageHeaderPosition, 100);
    
    // Actualizar posición al redimensionar la ventana
    window.addEventListener('resize', updatePageHeaderPosition);
    
    // Observar cambios en el header (por si cambia de tamaño)
    const headerObserver = new MutationObserver(updatePageHeaderPosition);
    const pcHeader = document.querySelector('.pc-header');
    if (pcHeader) {
      headerObserver.observe(pcHeader, {
        attributes: true,
        attributeFilter: ['class', 'style'],
        childList: true,
        subtree: true
      });
    }
    
    // Reinicializar dropdowns para asegurar funcionamiento
    setTimeout(() => {
      const dropdowns = document.querySelectorAll('.dropdown-toggle');
      dropdowns.forEach(dropdown => {
        new bootstrap.Dropdown(dropdown);
      });
    }, 500);
  });
</script>