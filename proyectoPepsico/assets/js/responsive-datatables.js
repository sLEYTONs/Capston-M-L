/**
 * Script global para hacer todas las DataTables responsivas
 * Se ejecuta automáticamente después de que se carguen las DataTables
 * Versión simplificada que solo aplica estilos CSS sin interferir con la inicialización
 */

(function() {
    'use strict';
    
    // Función para aplicar estilos responsivos a una DataTable ya inicializada
    function makeDataTableResponsive(settings) {
        try {
            if (!settings || !settings.nTable) {
                return;
            }
            
            const $table = jQuery(settings.nTable);
            if (!$table.length) {
                return;
            }
            
            // Esperar a que el wrapper esté disponible
            setTimeout(function() {
                const $wrapper = $table.closest('.dataTables_wrapper');
                if ($wrapper.length && !$wrapper.hasClass('dt-responsive')) {
                    $wrapper.addClass('dt-responsive');
                    
                    // Asegurar que el contenedor tenga overflow-x: auto
                    $wrapper.css({
                        'overflow-x': 'auto',
                        '-webkit-overflow-scrolling': 'touch'
                    });
                }
            }, 50);
        } catch (e) {
            // Silenciar errores para no interrumpir la funcionalidad
            console.warn('Error al hacer DataTable responsiva:', e);
        }
    }
    
    // Esperar a que jQuery y DataTables estén cargados
    function initResponsiveDataTables() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
            // Reintentar después de 100ms
            setTimeout(initResponsiveDataTables, 100);
            return;
        }
        
        // Escuchar el evento init.dt que se dispara cuando una DataTable está completamente inicializada
        jQuery(document).on('init.dt', function(e, settings) {
            if (e.namespace === 'dt') {
                // Esperar un poco más para asegurar que todo esté listo
                setTimeout(function() {
                    makeDataTableResponsive(settings);
                }, 100);
            }
        });
        
        // Aplicar estilos a DataTables que ya están inicializadas
        jQuery(document).ready(function() {
            setTimeout(function() {
                jQuery('.dataTable').each(function() {
                    const $table = jQuery(this);
                    if ($table.length && $table.data('DataTable')) {
                        try {
                            const dt = $table.DataTable();
                            if (dt && dt.settings && dt.settings()[0]) {
                                makeDataTableResponsive(dt.settings()[0]);
                            }
                        } catch (e) {
                            // Ignorar errores
                        }
                    }
                });
            }, 500);
        });
    }
    
    // Iniciar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initResponsiveDataTables);
    } else {
        initResponsiveDataTables();
    }
})();

