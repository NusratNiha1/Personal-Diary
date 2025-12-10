  </main>
  <script src="<?php echo e(rtrim(app_base_url(),'/')); ?>/assets/js/app.js"></script>
  <script>
function togglePassword() {
  const pass = document.getElementById("password");
  pass.type = (pass.type === "password") ? "text" : "password";
}
</script>

</body>
</html>
