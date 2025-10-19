<footer class="pc-footer">
  <div class="footer-wrapper container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start my-2">
        <p class="m-0 text-muted">
          &copy; <?php echo date("Y"); ?> PepsiCo. Todos los derechos reservados.
        </p>
      </div>
      <div class="col-md-6 text-center text-md-end my-2">
        <p class="m-0 text-muted">
          Desarrollado por <span class="fw-bold">Matías Mora</span> y <span class="fw-bold">Sebastián Leyton</span>
        </p>
      </div>
    </div>
  </div>
</footer>

<style>
.pc-footer {
  background-color: #004B93; /* Azul corporativo de PepsiCo */
  color: white;
  padding: 1rem 0;
  border-top: 3px solid #E21C21; /* Rojo corporativo de PepsiCo */
  margin-top: auto; /* Para que se mantenga al fondo si usas flexbox */
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
  color: #E21C21; /* Rojo corporativo al hacer hover */
}

.text-muted {
  color: rgba(255, 255, 255, 0.8) !important;
}

.fw-bold {
  color: white;
}
</style>