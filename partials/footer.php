  </main>
  
  <!-- Footer -->
  <footer class=" container glass mx-auto mb-5 mt-16 py-12 border-t border-white/10">
    <div class=" mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <!-- Brand -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg flex items-center justify-center text-white text-lg"><i class="fas fa-palette"></i></div>
            <h3 class="font-display text-lg font-bold bg-gradient-to-r from-primary-500 to-primary-600 bg-clip-text text-transparent">Life Canvas</h3>
          </div>
          <p class="text-sm text-gray-300">
            Express yourself through daily entries. Your personal canvas for stories, thoughts, and memories.
          </p>
        </div>

        <!-- Features -->
        <div>
          <h4 class="font-semibold text-sm uppercase tracking-wide mb-4 text-white">Features</h4>
          <ul class="space-y-2 text-sm text-gray-300">
            <li><a href="#" class="hover:text-primary-600 transition">Create Entries</a></li>
            <li><a href="#" class="hover:text-primary-600 transition">Secure Storage</a></li>
            <li><a href="#" class="hover:text-primary-600 transition">User Profile</a></li>
            <li><a href="#" class="hover:text-primary-600 transition">Media Support</a></li>
          </ul>
        </div>

        <!-- Support -->
        <div>
          <h4 class="font-semibold text-sm uppercase tracking-wide mb-4 text-white">Support</h4>
          <ul class="space-y-2 text-sm text-gray-300">
            <li><a href="#" class="hover:text-primary-600 transition">Help Center</a></li>
            <li><a href="#" class="hover:text-primary-600 transition">Privacy Policy</a></li>
            <li><a href="#" class="hover:text-primary-600 transition">Terms of Service</a></li>
            <li><a href="#" class="hover:text-primary-600 transition">Contact Us</a></li>
          </ul>
        </div>

        <!-- Social -->
        <div>
          <h4 class="font-semibold text-sm uppercase tracking-wide mb-4 text-white">Follow Us</h4>
          <div class="flex gap-3">
            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition text-primary-400">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition text-primary-400">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition text-primary-400">
              <i class="fab fa-linkedin-in"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-t border-white/10 pt-8 mt-8">
        <div class="flex flex-col md:flex-row items-center justify-between text-sm text-gray-300">
          <p>&copy; 2025 Life Canvas. All rights reserved.</p>
          <p>Crafted with <i class="fas fa-palette text-primary-400"></i> for your creative expression</p>
        </div>
      </div>
    </div>
  </footer>

  <script src="<?php echo e(rtrim(app_base_url(),'/')); ?>/assets/js/app.js?v=<?php echo time(); ?>"></script>
  <script>
function togglePassword(id) {
  const input = document.getElementById(id || "password");
  input.type = (input.type === "password") ? "text" : "password";
}
</script>

</body>
</html>
